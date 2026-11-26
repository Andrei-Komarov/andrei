<?php

namespace App\Console\Commands;

use App\Models\SearchProfile;
use App\Models\SiteSetting;
use App\Services\EbayBrowseService;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportEbayLaptops extends Command
{
    protected $signature = 'ebay:import-laptops {profile? : ID профиля поиска} {--scheduled : Import profiles scheduled for the current time}';

    protected $description = 'Imports laptop listings from eBay Browse API';

    public function handle(EbayBrowseService $ebay, TelegramNotificationService $telegram): int
    {
        // Check if we need to wait after a 429 error before starting
        if ($this->shouldWaitAfter429()) {
            return self::SUCCESS;
        }

        // Kill any hung import processes before starting
        $this->killHungProcesses();

        $failed = false;
        $now = Carbon::now('Asia/Bishkek');
        $now->startOfMinute();
        $scheduled = (bool) $this->option('scheduled');
        $profileId = $this->argument('profile');

        // If a specific profile is given, run only that profile with its own PID file
        if ($profileId) {
            $profile = SearchProfile::query()->findOrFail($profileId);
            $this->writePidFile($scheduled, $profile->id, $profile->name);
            $this->logToScheduler("=== Импорт профиля #{$profile->id} ({$profile->name}) запущен ===");

            try {
                $result = $ebay->import($profile);
                $msg = "{$profile->name}: {$result['imported']} listings processed, {$result['new_listings']} new";
                $this->info($msg);
                $this->logToScheduler($msg);
            } catch (ConnectionException|RequestException|RuntimeException $exception) {
                report($exception);
                $failed = true;
                $msg = "{$profile->name}: parsing failed: {$exception->getMessage()}";
                $this->error($msg);
                $this->logToScheduler($msg);
                $telegram->sendSearchFailure($profile->user, $profile, $exception->getMessage());

                if ($exception instanceof RequestException && str_contains($exception->getMessage(), '429')) {
                    $this->record429Error();
                }
            }

            $this->logToScheduler("=== Импорт профиля #{$profile->id} завершён (статус: ".($failed ? 'error' : 'ok').") ===\n");
            $this->removePidFile($profile->id);

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $this->writePidFile($scheduled);

        $this->logToScheduler('=== Импорт запущен (scheduled: '.($scheduled ? 'yes' : 'no').') ===');

        $query = SearchProfile::query()
            ->where('active', true)
            ->when($scheduled, function ($query) use ($now): void {
                $query->whereTime('schedule_time', '<=', $now->format('H:i:s'));
            })
            ->orderBy('id');
        $query->chunkById(100, function ($profiles) use ($ebay, $telegram, $now, $scheduled, &$failed): void {
            foreach ($profiles as $profile) {
                if ($scheduled) {
                    $scheduledSlot = $this->scheduledSlot($profile, $now);
                    if ($scheduledSlot === null || ! $this->claimScheduledProfile($profile, $scheduledSlot)) {
                        continue;
                    }
                }

                // Check for and kill any hung processes before starting this profile
                $this->killHungProcesses();

                // Update general PID file with current profile name
                $this->updatePidProfileName($profile->name);

                try {
                    $this->logToScheduler("Запуск профиля #{$profile->id} ({$profile->name})...");
                    $result = $ebay->import($profile);
                    $msg = "{$profile->name}: {$result['imported']} listings processed, {$result['new_listings']} new";
                    $this->info($msg);
                    $this->logToScheduler($msg);
                } catch (ConnectionException|RequestException|RuntimeException $exception) {
                    report($exception);
                    $failed = true;
                    $msg = "{$profile->name}: parsing failed: {$exception->getMessage()}";
                    $this->error($msg);
                    $this->logToScheduler($msg);
                    $telegram->sendSearchFailure($profile->user, $profile, $exception->getMessage());

                    if ($exception instanceof RequestException && str_contains($exception->getMessage(), '429')) {
                        $this->record429Error();
                    }
                }

                if (
                    $scheduled
                    && $profile->notification_frequency === 'daily'
                    && isset($scheduledSlot)
                    && $scheduledSlot->format('H:i') === substr($profile->schedule_time, 0, 5)
                ) {
                    $telegram->sendDailyDigest($profile);
                }

                // Add a delay between profiles to avoid eBay API rate limiting when running multiple profiles
                $lastProfile = $profiles->last();
                if ($lastProfile !== null && $lastProfile->id !== $profile->id) {
                    $delay = SiteSetting::query()->value('ebay_profile_delay_seconds') ?? config('services.ebay.profile_delay_seconds', 5);
                    $this->logToScheduler("Задержка {$delay} сек перед следующим профилем...");
                    sleep((int) $delay);
                }
            }
        });

        $this->logToScheduler('=== Импорт завершён (статус: '.($failed ? 'errors' : 'ok').") ===\n");

        $this->removePidFile();

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function writePidFile(bool $scheduled, ?int $profileId = null, ?string $profileName = null): void
    {
        if ($profileId !== null) {
            $pidFile = storage_path("logs/ebay-import-{$profileId}.pid");
        } else {
            $pidFile = storage_path('logs/ebay-import.pid');
        }

        $pid = getmypid();
        $startedAt = Carbon::now('Asia/Bishkek')->format('Y-m-d H:i:s');
        File::put($pidFile, json_encode([
            'pid' => $pid,
            'scheduled' => $scheduled,
            'started_at' => $startedAt,
            'profile_id' => $profileId,
            'profile_name' => $profileName,
        ]));
    }

    private function updatePidProfileName(string $profileName): void
    {
        $pidFile = storage_path('logs/ebay-import.pid');

        if (File::exists($pidFile)) {
            $pidData = json_decode(File::get($pidFile), true);
            if (is_array($pidData)) {
                $pidData['profile_name'] = $profileName;
                File::put($pidFile, json_encode($pidData));
            }
        }
    }

    private function removePidFile(?int $profileId = null): void
    {
        if ($profileId !== null) {
            $pidFile = storage_path("logs/ebay-import-{$profileId}.pid");
        } else {
            $pidFile = storage_path('logs/ebay-import.pid');
        }

        if (File::exists($pidFile)) {
            File::delete($pidFile);
        }
    }

    private function logToScheduler(string $message): void
    {
        $logPath = storage_path('logs/ebay-scheduler.log');
        $timestamp = Carbon::now('Asia/Bishkek')->format('Y-m-d H:i:s');
        File::append($logPath, "[{$timestamp}] {$message}".PHP_EOL);
    }

    private function scheduledSlot(SearchProfile $profile, Carbon $now): ?Carbon
    {
        $startTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $now->toDateString().' '.substr($profile->schedule_time, 0, 8),
            'Asia/Bishkek',
        );

        if ($startTime === false || $now->lessThan($startTime)) {
            return null;
        }

        $intervalMinutes = (int) $profile->schedule_interval_hours * 60;
        $elapsedMinutes = $startTime->diffInMinutes($now);
        $elapsedIntervals = intdiv($elapsedMinutes, $intervalMinutes);

        return $startTime->addMinutes($elapsedIntervals * $intervalMinutes);
    }

    private function claimScheduledProfile(SearchProfile $profile, Carbon $scheduledSlot): bool
    {
        $scheduledAt = $scheduledSlot->copy()->utc()->format('Y-m-d H:i:s');

        return SearchProfile::query()
            ->whereKey($profile->id)
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('last_scheduled_at')
                ->orWhere('last_scheduled_at', '<', $scheduledAt))
            ->update(['last_scheduled_at' => $scheduledAt]) === 1;
    }

    /**
     * Check if we should wait before starting due to a recent 429 error.
     */
    private function shouldWaitAfter429(): bool
    {
        $last429 = Cache::get('ebay_429_last_error_at');

        if ($last429 === null) {
            return false;
        }

        $delayMinutes = SiteSetting::query()->value('ebay_429_delay_minutes') ?? 30;
        $lastErrorTime = Carbon::parse($last429);
        $waitUntil = $lastErrorTime->copy()->addMinutes($delayMinutes);

        if (Carbon::now('Asia/Bishkek')->lessThan($waitUntil)) {
            $remaining = $lastErrorTime->diffInMinutes($waitUntil, false);
            $this->logToScheduler("Последняя ошибка 429 была {$lastErrorTime->format('H:i:s')}. Ожидание {$remaining} мин перед запуском следующей задачи (задержка: {$delayMinutes} мин).");
            $this->info("Ожидание после ошибки 429: осталось {$remaining} мин.");

            return true;
        }

        // Enough time has passed — clear the cache
        Cache::forget('ebay_429_last_error_at');

        return false;
    }

    /**
     * Record a 429 error timestamp in cache.
     */
    private function record429Error(): void
    {
        Cache::put('ebay_429_last_error_at', Carbon::now('Asia/Bishkek')->toDateTimeString(), now()->addHour());
        $this->logToScheduler('Зафиксирована ошибка 429. Следующая задача будет отложена на '.(SiteSetting::query()->value('ebay_429_delay_minutes') ?? 30).' мин.');
    }

    /**
     * Check for and kill any hung import processes.
     */
    private function killHungProcesses(): void
    {
        $autoKill = SiteSetting::query()->value('ebay_auto_kill_hung') ?? true;
        if (! $autoKill) {
            return;
        }

        $thresholdMinutes = SiteSetting::query()->value('ebay_stuck_threshold_minutes') ?? 180;
        $pidDir = storage_path('logs');

        if (! is_dir($pidDir)) {
            return;
        }

        // Check all PID files for hung processes
        $pidFiles = array_merge(
            glob($pidDir.'/ebay-import-*.pid'),
            [storage_path('logs/ebay-import.pid')],
        );

        foreach (array_unique($pidFiles) as $pidFile) {
            if (! File::exists($pidFile)) {
                continue;
            }

            $pidData = json_decode(File::get($pidFile), true);
            if (! is_array($pidData) || ! isset($pidData['pid'])) {
                continue;
            }

            $pid = (int) $pidData['pid'];

            // Skip our own process
            if ($pid === getmypid()) {
                continue;
            }

            // Check if process is still running
            exec("ps -p {$pid} -o pid= 2>&1", $output, $exitCode);
            if ($exitCode !== 0) {
                File::delete($pidFile);

                continue;
            }

            // Check if process is hung (running longer than threshold)
            $startedAt = isset($pidData['started_at']) ? Carbon::parse($pidData['started_at']) : null;
            if ($startedAt === null) {
                continue;
            }

            if ($startedAt->diffInMinutes() < (int) $thresholdMinutes) {
                continue;
            }

            // Kill the hung process
            $profileName = $pidData['profile_name'] ?? 'неизвестный';
            $this->logToScheduler("Обнаружен зависший процесс #{$pid} ({$profileName}), работает более {$thresholdMinutes} мин. Принудительная остановка...");

            exec("kill -9 {$pid} 2>&1", $killOutput, $killExitCode);
            if ($killExitCode === 0) {
                $this->logToScheduler("Процесс #{$pid} ({$profileName}) успешно завершён.");
            } else {
                $this->logToScheduler("Не удалось завершить процесс #{$pid} ({$profileName}).");
            }

            File::delete($pidFile);
        }
    }
}

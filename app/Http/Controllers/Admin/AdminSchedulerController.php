<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchProfile;
use App\Models\SiteSetting;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class AdminSchedulerController extends Controller
{
    public function index(Request $request): View
    {
        $profiles = SearchProfile::query()
            ->orderBy('schedule_time')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($profile) {
                return [
                    $profile->id => [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'active' => $profile->active,
                        'schedule_time' => $profile->schedule_time,
                        'schedule_interval_hours' => $profile->schedule_interval_hours,
                        'last_scheduled_at' => $profile->last_scheduled_at,
                        'last_synced_at' => $profile->last_synced_at,
                        'user_name' => $profile->user->name ?? '—',
                    ],
                ];
            });

        $schedulerLog = $this->getSchedulerLog();
        $cronStatus = $this->getCronStatus();
        $settings = SiteSetting::first();
        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        return view('admin.scheduler.index', [
            'profiles' => $profiles,
            'schedulerLog' => $schedulerLog,
            'cronStatus' => $cronStatus,
            'settings' => $settings,
            'timezones' => $timezones,
        ]);
    }

    public function runImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_id' => ['nullable', 'integer', 'exists:search_profiles,id'],
        ]);

        $profileId = $validated['profile_id'] ?? null;

        if ($profileId) {
            Artisan::call('ebay:import-laptops', ['profile' => $profileId]);

            return to_route('admin.scheduler.index')->with('success', "Импорт для профиля #{$profileId} запущен.");
        }

        Artisan::call('ebay:import-laptops', ['--scheduled' => true]);

        return to_route('admin.scheduler.index')->with('success', 'Запланированные импорты запущены.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_type' => ['required', 'in:cron,scheduled'],
            'schedule_frequency' => ['required_if:schedule_type,scheduled', 'in:everyMinute,everyFiveMinutes,everyTenMinutes,everyFifteenMinutes,everyThirtyMinutes,hourly'],
            'schedule_timezone' => ['required_if:schedule_type,scheduled', 'timezone'],
            'schedule_without_overlapping' => ['boolean'],
        ]);

        $settings = SiteSetting::first();
        $settings->update([
            'schedule_type' => $validated['schedule_type'],
            'schedule_frequency' => $validated['schedule_type'] === 'scheduled' ? ($validated['schedule_frequency'] ?? $settings->schedule_frequency) : $settings->schedule_frequency,
            'schedule_timezone' => $validated['schedule_type'] === 'scheduled' ? ($validated['schedule_timezone'] ?? $settings->schedule_timezone) : $settings->schedule_timezone,
            'schedule_without_overlapping' => (bool) ($validated['schedule_without_overlapping'] ?? false),
        ]);

        return to_route('admin.scheduler.index')->with('success', 'Настройки расписания обновлены.');
    }

    public function clearLog(): RedirectResponse
    {
        $logPath = storage_path('logs/ebay-scheduler.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return to_route('admin.scheduler.processes-page')->with('success', 'Лог планировщика очищен.');
    }

    public function processes(): View
    {
        $schedulerLog = $this->getSchedulerLog();

        return view('admin.scheduler.processes', [
            'schedulerLog' => $schedulerLog,
        ]);
    }

    public function getRunningProcesses(): JsonResponse
    {
        try {
            // Return only import processes, not the dispatcher
            $allProcesses = $this->detectRunningProcesses();
            $importProcesses = array_values(array_filter($allProcesses, fn ($p) => $p['type'] !== 'dispatcher'));

            return response()->json($importProcesses);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getScheduleDispatcher(): JsonResponse
    {
        try {
            $dispatcher = $this->detectScheduleDispatcher();

            return response()->json($dispatcher);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stopSchedule(): RedirectResponse
    {
        // Find and gracefully stop the schedule:work process using SIGTERM
        exec('ps aux | grep "schedule:work" | grep -v grep 2>&1', $psOutput, $psExitCode);
        if ($psExitCode === 0 && trim(implode("\n", $psOutput))) {
            foreach (explode("\n", trim(implode("\n", $psOutput))) as $line) {
                $parts = preg_split('/\s+/', trim($line), 11);
                if (count($parts) >= 11) {
                    $pid = (int) $parts[1];
                    exec("kill -15 {$pid} 2>&1");
                }
            }

            return to_route('admin.scheduler.processes-page')->with('success', 'Диспетчер расписания остановлен.');
        }

        return to_route('admin.scheduler.processes-page')->with('error', 'Диспетчер расписания не запущен.');
    }

    public function clearScheduleMutex(): RedirectResponse
    {
        Artisan::call('schedule:clear-cache');

        return to_route('admin.scheduler.processes-page')->with('success', 'Блокировка расписания снята (если она существовала).');
    }

    public function restartSchedule(): RedirectResponse
    {
        // Find and kill the current schedule:work process
        exec('ps aux | grep "schedule:work" | grep -v grep 2>&1', $psOutput, $psExitCode);
        if ($psExitCode === 0 && trim(implode("\n", $psOutput))) {
            foreach (explode("\n", trim(implode("\n", $psOutput))) as $line) {
                $parts = preg_split('/\s+/', trim($line), 11);
                if (count($parts) >= 11) {
                    $pid = (int) $parts[1];
                    exec("kill -9 {$pid} 2>&1");
                }
            }
        }

        // Restart schedule:work via Herd-lite — it auto-restarts, so just killing is enough
        // The process will be restarted by Herd-lite automatically

        return to_route('admin.scheduler.processes-page')->with('success', 'Диспетчер расписания перезапущен.');
    }

    public function killProcess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pid' => ['required', 'integer'],
            'profile_id' => ['nullable', 'integer'],
        ]);

        $pid = $validated['pid'];
        $profileId = $validated['profile_id'] ?? null;
        exec("kill -9 {$pid} 2>&1", $killOutput, $killExitCode);

        if ($killExitCode === 0) {
            // Delete the specific PID file for this profile, or the general one
            if ($profileId !== null) {
                $pidFile = storage_path("logs/ebay-import-{$profileId}.pid");
            } else {
                $pidFile = storage_path('logs/ebay-import.pid');
            }

            if (File::exists($pidFile)) {
                File::delete($pidFile);
            }

            return to_route('admin.scheduler.processes-page')->with('success', "Процесс #{$pid} принудительно завершён.");
        }

        return to_route('admin.scheduler.processes-page')->with('error', "Не удалось завершить процесс #{$pid}: ".trim(implode("\n", $killOutput)));
    }

    private function detectRunningProcesses(): array
    {
        $processes = [];
        $thresholdMinutes = SiteSetting::query()->value('ebay_stuck_threshold_minutes') ?? 180;

        // Check all profile-specific PID files (ebay-import-{profile_id}.pid)
        $pidDir = storage_path('logs');
        if (is_dir($pidDir)) {
            foreach (glob($pidDir.'/ebay-import-*.pid') as $pidFile) {
                $pidData = json_decode(File::get($pidFile), true);
                if (! is_array($pidData) || ! isset($pidData['pid'])) {
                    continue;
                }

                $pid = (int) $pidData['pid'];
                exec("ps -p {$pid} -o pid,etime,cmd 2>&1", $psCheckOutput, $psCheckExitCode);
                $isRunning = $psCheckExitCode === 0;

                if ($isRunning) {
                    $startedAt = isset($pidData['started_at']) ? Carbon::parse($pidData['started_at']) : null;
                    $elapsed = $startedAt ? $startedAt->diffForHumans() : 'неизвестно';

                    exec("ps -p {$pid} -o etime= 2>&1", $psElapsedOutput, $psElapsedExitCode);
                    $elapsedTime = trim(implode("\n", $psElapsedOutput)) ?: $elapsed;

                    $processes[] = [
                        'pid' => $pid,
                        'profile_id' => $pidData['profile_id'] ?? null,
                        'profile_name' => $pidData['profile_name'] ?? '—',
                        'type' => $pidData['scheduled'] ?? false ? 'scheduled' : 'manual',
                        'started_at' => $pidData['started_at'] ?? null,
                        'elapsed' => $elapsedTime,
                        'is_running' => true,
                        'is_stuck' => $startedAt && $startedAt->diffInMinutes() > (int) $thresholdMinutes,
                    ];
                } else {
                    // Process is not running, clean up stale PID file
                    File::delete($pidFile);
                }
            }
        }

        // Check general PID file (ebay-import.pid) for batch imports
        $generalPidFile = storage_path('logs/ebay-import.pid');
        if (File::exists($generalPidFile)) {
            $pidData = json_decode(File::get($generalPidFile), true);
            if (is_array($pidData) && isset($pidData['pid'])) {
                $pid = (int) $pidData['pid'];

                // Skip if already found in profile-specific files
                if (collect($processes)->contains('pid', $pid)) {
                    return $processes;
                }

                exec("ps -p {$pid} -o pid,etime,cmd 2>&1", $psCheckOutput2, $psCheckExitCode2);
                $isRunning = $psCheckExitCode2 === 0;

                if ($isRunning) {
                    $startedAt = isset($pidData['started_at']) ? Carbon::parse($pidData['started_at']) : null;
                    $elapsed = $startedAt ? $startedAt->diffForHumans() : 'неизвестно';

                    exec("ps -p {$pid} -o etime= 2>&1", $psElapsedOutput2, $psElapsedExitCode2);
                    $elapsedTime = trim(implode("\n", $psElapsedOutput2)) ?: $elapsed;

                    $processes[] = [
                        'pid' => $pid,
                        'profile_id' => $pidData['profile_id'] ?? null,
                        'profile_name' => $pidData['profile_name'] ?? 'Все профили',
                        'type' => $pidData['scheduled'] ?? false ? 'scheduled' : 'manual',
                        'started_at' => $pidData['started_at'] ?? null,
                        'elapsed' => $elapsedTime,
                        'is_running' => true,
                        'is_stuck' => $startedAt && $startedAt->diffInMinutes() > (int) $thresholdMinutes,
                    ];
                }
            }
        }

        // Check for any processes without PID files (lost PID file but still running)
        exec('ps aux | grep "ebay:import-laptops" | grep -v grep 2>&1', $psAllOutput, $psAllExitCode);
        if ($psAllExitCode === 0 && trim(implode("\n", $psAllOutput))) {
            foreach (explode("\n", trim(implode("\n", $psAllOutput))) as $line) {
                $parts = preg_split('/\s+/', trim($line), 11);
                if (count($parts) >= 2) {
                    $pid = (int) $parts[1];

                    // Skip if already in list from PID files
                    if (collect($processes)->contains('pid', $pid)) {
                        continue;
                    }

                    // Skip sh -c wrapper processes (Herd-lite spawns sh as parent of php artisan)
                    $cmd = $parts[10] ?? '';
                    if (str_contains($cmd, 'sh -c')) {
                        continue;
                    }

                    // Process is running but PID file is missing - recreate it
                    $pidFile = storage_path('logs/ebay-import.pid');
                    File::put($pidFile, json_encode([
                        'pid' => $pid,
                        'scheduled' => true,
                        'started_at' => null,
                        'profile_id' => null,
                        'profile_name' => null,
                    ]));

                    $processes[] = [
                        'pid' => $pid,
                        'profile_id' => null,
                        'profile_name' => '—',
                        'type' => 'scheduled',
                        'started_at' => null,
                        'elapsed' => $parts[9] ?? 'неизвестно',
                        'is_running' => true,
                        'is_stuck' => false,
                    ];
                }
            }
        }

        return $processes;
    }

    private function getCronStatus(): array
    {
        exec('crontab -l 2>&1', $cronOutputArr, $cronExitCode);
        $cronOutput = implode("\n", $cronOutputArr);

        $hasEbaySchedule = str_contains($cronOutput, 'ebay:import-laptops')
            || str_contains($cronOutput, 'schedule:run');

        return [
            'configured' => $hasEbaySchedule,
            'output' => trim($cronOutput),
        ];
    }

    private function detectScheduleDispatcher(): ?array
    {
        exec('ps aux | grep "schedule:work" | grep -v grep 2>&1', $scheduleWorkOutput, $scheduleWorkExitCode);
        if ($scheduleWorkExitCode !== 0 || ! trim(implode("\n", $scheduleWorkOutput))) {
            return null;
        }

        foreach (explode("\n", trim(implode("\n", $scheduleWorkOutput))) as $line) {
            $parts = preg_split('/\s+/', trim($line), 11);
            if (count($parts) < 11) {
                continue;
            }

            $schedulePid = (int) $parts[1];

            // Get real uptime from /proc/{pid}/stat instead of CPU time from ps aux
            $elapsed = 'неизвестно';
            $startedAtTimestamp = null;

            if (File::exists("/proc/{$schedulePid}/stat")) {
                $statContent = File::get("/proc/{$schedulePid}/stat");
                // Use strrpos to find the last ")" which ends the comm field, then parse fields after it
                $parenEnd = strrpos($statContent, ')');
                if ($parenEnd !== false) {
                    $rest = substr($statContent, $parenEnd + 2);
                    $fields = preg_split('/\s+/', trim($rest));
                    // Field index 19 (0-based) is starttime (field 22 in /proc/pid/stat, minus 3 fields before comm)
                    if (isset($fields[19])) {
                        $startTime = (int) $fields[19];
                        exec('getconf CLK_TCK 2>&1', $clkTckOutput, $clkTckExitCode);
                        $clkTck = (int) trim(implode("\n", $clkTckOutput));
                        $clkTck = $clkTck > 0 ? $clkTck : 100;

                        exec("awk '/^btime/ {print $2}' /proc/stat 2>&1", $bootTimeOutput, $bootTimeExitCode);
                        $bootTime = (int) trim(implode("\n", $bootTimeOutput));

                        if ($bootTime > 0 && $startTime > 0) {
                            $processStartTime = $bootTime + intdiv($startTime, $clkTck);
                            $now = time();
                            $secondsRunning = $now - $processStartTime;

                            $hours = intdiv($secondsRunning, 3600);
                            $minutes = intdiv($secondsRunning % 3600, 60);
                            $elapsed = sprintf('%d:%02d', $hours, $minutes);

                            $startedAtTimestamp = date('Y-m-d H:i:s', $processStartTime);
                        }
                    }
                }
            }

            return [
                'pid' => $schedulePid,
                'started_at' => $startedAtTimestamp,
                'elapsed' => $elapsed,
                'is_running' => true,
            ];
        }

        return null;
    }

    private function getSchedulerLog(): array
    {
        $logPath = storage_path('logs/ebay-scheduler.log');

        if (! File::exists($logPath)) {
            return ['available' => false, 'lines' => []];
        }

        $lines = File::lines($logPath)->values()->all();
        $lines = array_reverse($lines);
        $last50 = array_slice($lines, 0, 50);

        return [
            'available' => true,
            'lines' => $last50,
        ];
    }
}

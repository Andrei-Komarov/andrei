<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Run database and files backup';

    public function handle(): int
    {
        $settings = SiteSetting::query()->first();
        $backupPath = $settings->backup_path ?? base_path('backups');
        $timestamp = now()->format('Y-m-d_His');

        $this->info("Starting backup at {$timestamp}...");

        $success = true;
        $messages = [];

        // Create backup directory if not exists
        if (! File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        // Database backup
        if ($settings->backup_database) {
            try {
                $dbConfig = config('database.connections.'.config('database.default'));
                $dbName = $dbConfig['database'] ?? 'unknown';
                $dbUser = $dbConfig['username'] ?? 'root';
                $dbPass = $dbConfig['password'] ?? '';
                $dbHost = $dbConfig['host'] ?? '127.0.0.1';

                $dumpFile = $backupPath.'/'.$dbName.'_'.$timestamp.'.dump';
                $command = "PGPASSWORD={$dbPass} pg_dump -h {$dbHost} -U {$dbUser} -d {$dbName} -F c -f {$dumpFile}";

                $process = Process::fromShellCommandline($command);
                $process->run();

                if ($process->isSuccessful()) {
                    $size = round(File::size($dumpFile) / 1024 / 1024, 2);
                    $messages[] = "Database backup: {$size} MB";
                    $this->info("Database backed up: {$size} MB");
                } else {
                    $messages[] = 'Database backup failed: '.$process->getErrorOutput();
                    $this->error('Database backup failed');
                    $success = false;
                }
            } catch (\Exception $e) {
                $messages[] = 'Database backup error: '.$e->getMessage();
                $this->error('Database backup error: '.$e->getMessage());
                $success = false;
            }
        }

        // Files backup
        if ($settings->backup_files) {
            try {
                $filesBackup = $backupPath.'/files_'.$timestamp.'.tar.gz';
                $storagePath = storage_path('app/public');

                $files = File::allFiles($storagePath);
                if ($files !== []) {
                    $process = Process::fromShellCommandline("tar -czf {$filesBackup} -C {$storagePath} .");
                    $process->run();

                    if ($process->isSuccessful()) {
                        $size = round(File::size($filesBackup) / 1024, 2);
                        $messages[] = "Files backup: {$size} KB";
                        $this->info("Files backed up: {$size} KB");
                    }
                } else {
                    $messages[] = 'No files to backup';
                    $this->info('No files to backup');
                }
            } catch (\Exception $e) {
                $messages[] = 'Files backup error: '.$e->getMessage();
                $this->error('Files backup error: '.$e->getMessage());
                $success = false;
            }
        }

        // Clean old backups
        $this->cleanupOldBackups($backupPath, $settings->backup_retain_count);

        // Log result
        $logMessage = implode("\n", $messages);
        if ($success) {
            Log::channel('ebay-backup')->info("Backup completed successfully:\n{$logMessage}");
            $this->info('Backup completed successfully.');
        } else {
            Log::channel('ebay-backup')->error("Backup failed:\n{$logMessage}");
            $this->error('Backup failed.');
        }

        // Telegram notification
        if ($settings->backup_notify_telegram) {
            try {
                $admin = User::query()->whereNotNull('telegram_chat_id')->first();
                if ($admin) {
                    $token = config('services.telegram.bot_token');
                    if ($token && $token !== '') {
                        $status = $success ? '✅ Успешно' : '❌ Ошибка';
                        Http::timeout(10)
                            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                                'chat_id' => $admin->telegram_chat_id,
                                'text' => "Резервное копирование: {$status}\n\n{$logMessage}",
                                'disable_web_page_preview' => false,
                            ])
                            ->throw();
                    }
                }
            } catch (\Exception $e) {
                $this->error('Telegram notification failed: '.$e->getMessage());
            }
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    protected function cleanupOldBackups(string $backupPath, int $retainCount): void
    {
        $files = File::files($backupPath);
        usort($files, function ($a, $b) {
            return $b->getMTime() <=> $a->getMTime();
        });

        foreach (array_slice($files, $retainCount) as $file) {
            File::delete($file->getPathname());
            $this->info("Removed old backup: {$file->getFilename()}");
        }
    }
}

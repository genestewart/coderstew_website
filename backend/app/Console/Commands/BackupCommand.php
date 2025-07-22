<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Commands\BackupCommand as SpatieBackupCommand;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coderstew:backup 
                            {--type=full : Type of backup (full, db, files)}
                            {--force : Force backup even if one exists}
                            {--notify : Send notification after backup}';

    /**
     * The console command description.
     */
    protected $description = 'Run CoderStew website backup with enhanced logging and monitoring';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $force = $this->option('force');
        $notify = $this->option('notify');

        $this->info("🚀 Starting CoderStew backup...");
        $this->info("Backup type: {$type}");

        try {
            // Log backup start
            Log::info('Backup started', [
                'type' => $type,
                'forced' => $force,
                'timestamp' => now()->toISOString(),
            ]);

            // Check if backup is needed (unless forced)
            if (!$force && $this->shouldSkipBackup()) {
                $this->info('✅ Recent backup exists, skipping. Use --force to override.');
                return Command::SUCCESS;
            }

            // Pre-backup checks
            $this->performPreBackupChecks();

            // Run the actual backup
            $this->info('📦 Running backup process...');
            
            $exitCode = match ($type) {
                'db' => $this->runDatabaseBackup(),
                'files' => $this->runFilesBackup(),
                'full' => $this->runFullBackup(),
                default => $this->runFullBackup(),
            };

            if ($exitCode === 0) {
                $this->info('✅ Backup completed successfully');
                
                // Post-backup tasks
                $this->performPostBackupTasks();
                
                if ($notify) {
                    $this->sendNotification('success');
                }
                
                Log::info('Backup completed successfully', [
                    'type' => $type,
                    'timestamp' => now()->toISOString(),
                ]);
                
                return Command::SUCCESS;
            } else {
                throw new \Exception("Backup process failed with exit code: {$exitCode}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            
            Log::error('Backup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
            
            if ($notify) {
                $this->sendNotification('failure', $e->getMessage());
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Check if backup should be skipped
     */
    private function shouldSkipBackup(): bool
    {
        try {
            // Check for recent backups (within 6 hours)
            $backupDisk = Storage::disk('backup');
            $files = $backupDisk->files('');
            
            if (empty($files)) {
                return false;
            }
            
            // Find most recent backup
            $mostRecentTime = 0;
            foreach ($files as $file) {
                $time = $backupDisk->lastModified($file);
                if ($time > $mostRecentTime) {
                    $mostRecentTime = $time;
                }
            }
            
            // Skip if backup is less than 6 hours old
            return (now()->timestamp - $mostRecentTime) < 6 * 3600;
            
        } catch (\Exception $e) {
            // If we can't check, proceed with backup
            Log::warning('Could not check for recent backups', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Perform pre-backup checks
     */
    private function performPreBackupChecks(): void
    {
        $this->info('🔍 Performing pre-backup checks...');

        // Check database connection
        try {
            \DB::connection()->getPdo();
            $this->line('✅ Database connection: OK');
        } catch (\Exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }

        // Check disk space
        $freeSpace = disk_free_space(storage_path());
        $requiredSpace = 1024 * 1024 * 1024; // 1GB
        
        if ($freeSpace < $requiredSpace) {
            throw new \Exception("Insufficient disk space. Available: " . $this->formatBytes($freeSpace));
        }
        
        $this->line('✅ Disk space: ' . $this->formatBytes($freeSpace) . ' available');

        // Check S3 connection (if configured)
        if (config('backup.backup.destination.disks')[0] === 'backup') {
            try {
                Storage::disk('backup')->files('');
                $this->line('✅ S3 backup storage: OK');
            } catch (\Exception $e) {
                $this->warn('⚠️  S3 backup storage: ' . $e->getMessage());
            }
        }
    }

    /**
     * Run database-only backup
     */
    private function runDatabaseBackup(): int
    {
        return Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => !$this->option('notify'),
        ]);
    }

    /**
     * Run files-only backup
     */
    private function runFilesBackup(): int
    {
        return Artisan::call('backup:run', [
            '--only-files' => true,
            '--disable-notifications' => !$this->option('notify'),
        ]);
    }

    /**
     * Run full backup (database + files)
     */
    private function runFullBackup(): int
    {
        return Artisan::call('backup:run', [
            '--disable-notifications' => !$this->option('notify'),
        ]);
    }

    /**
     * Perform post-backup tasks
     */
    private function performPostBackupTasks(): void
    {
        $this->info('🧹 Performing post-backup cleanup...');

        // Clean up old backups
        try {
            Artisan::call('backup:clean', [
                '--disable-notifications' => true,
            ]);
            $this->line('✅ Cleanup completed');
        } catch (\Exception $e) {
            $this->warn('⚠️  Cleanup failed: ' . $e->getMessage());
        }

        // Monitor backup health
        try {
            Artisan::call('backup:monitor', [
                '--disable-notifications' => true,
            ]);
            $this->line('✅ Health check completed');
        } catch (\Exception $e) {
            $this->warn('⚠️  Health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Send backup notification
     */
    private function sendNotification(string $status, string $message = ''): void
    {
        try {
            // You can implement custom notification logic here
            // For now, just log it
            $logLevel = $status === 'success' ? 'info' : 'error';
            
            Log::$logLevel("Backup {$status}" . ($message ? ": {$message}" : ''), [
                'notification' => true,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes into human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
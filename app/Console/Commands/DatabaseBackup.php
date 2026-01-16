<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    // The signature allows a --type flag (defaults to 'auto')
    protected $signature = 'db:backup {--type=auto : The type of backup (manual/auto)}';
    protected $description = 'Dump the database to a SQL file';

    public function handle()
    {
        $type = $this->option('type');
        
        // 1. Determine Folder based on Type
        if ($type === 'manual') {
            $relativePath = rtrim(env('URI_FICHIER_GENERES'), '/\\');
            $logPrefix = '[MANUAL]';
        } else {
            $relativePath = rtrim(env('URI_DUMP_SAUVEGARDE'), '/\\');
            $logPrefix = '[AUTO]';
        }

        $directory = str_starts_with($relativePath, '/') 
            ? $relativePath 
            : base_path($relativePath);

        // 2. Build Filename
        // Format: sauvegarde-2026-01-16_14-30-00.sql
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $extension = env('SUFFIXE_FICHIER_DUMP_SAUVEGARDE', '.sql');
        $filename = "sauvegarde-{$timestamp}{$extension}";
        
        $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;

        // 3. Prepare Database Credentials
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // 4. Construct mysqldump command
        $dumpBinary = '/usr/bin/mysqldump';
        // Note: Using --no-tablespaces is often needed for cloud/modern MySQL permissions
        $command = "{$dumpBinary} --skip-ssl --user=\"{$dbUser}\" --password=\"{$dbPass}\" --host=\"{$dbHost}\" --port=\"{$dbPort}\" --no-tablespaces \"{$dbName}\" > \"{$fullPath}\" 2>&1";

        // 5. Execute
        $this->info("{$logPrefix} Starting backup to: $fullPath");
        
        // Ensure directory exists
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $returnVar = null;
        $output = [];
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("{$logPrefix} Backup successful!");
            Log::info("Database backup created: $filename");
            return 0;
        } else {
            $this->error("{$logPrefix} Backup failed!");
            Log::error("Database backup failed. Return code: $returnVar");
            return 1;
        }
    }
}
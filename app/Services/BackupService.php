<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    /**
     * Get all backup files with metadata.
     */
    public function listBackups(): array
    {
        $disk = $this->getBackupDisk();
        $directory = $this->getBackupDirectory();

        if (!Storage::disk($disk)->exists($directory)) {
            return [];
        }

        return collect(Storage::disk($disk)->files($directory))
            ->filter(fn ($path) => Str::endsWith(Str::lower($path), '.zip'))
            ->map(function ($path) use ($disk) {
                $size = Storage::disk($disk)->size($path);
                $modified = Storage::disk($disk)->lastModified($path);

                return [
                    'filename' => basename($path),
                    'size' => $size,
                    'created_at' => date('Y-m-d H:i:s', $modified),
                    'size_human' => $this->formatBytes($size),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * Create a new database backup and return the filename.
     *
     * @throws \Exception
     */
    public function createBackup(): string
    {
        $disk = $this->getBackupDisk();
        $directory = $this->getBackupDirectory();

        if (!Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->makeDirectory($directory);
        }

        $connectionName = config('database.default');
        $config = config('database.connections.' . $connectionName);

        if (!$config) {
            throw new \RuntimeException('Database configuration not found for connection: ' . $connectionName);
        }

        $tempDir = storage_path('app/backup-temp/create-' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        $sqlRelativePath = null;

        try {
            if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
                $dumpDir = $tempDir . DIRECTORY_SEPARATOR . 'db-dumps';
                File::ensureDirectoryExists($dumpDir);

                $sqlRelativePath = 'db-dumps/mysql-' . $config['database'] . '.sql';
                $sqlPath = $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sqlRelativePath);
                File::ensureDirectoryExists(dirname($sqlPath));

                $this->createMySqlBackup($config, $sqlPath);
            } elseif ($config['driver'] === 'sqlite') {
                $dumpDir = $tempDir . DIRECTORY_SEPARATOR . 'db-dumps';
                File::ensureDirectoryExists($dumpDir);

                $sqlRelativePath = 'db-dumps/sqlite-database.sql';
                $sqlPath = $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sqlRelativePath);
                File::ensureDirectoryExists(dirname($sqlPath));

                $this->createSqliteBackup($config, $sqlPath);
            } else {
                throw new \RuntimeException('Unsupported database driver: ' . $config['driver']);
            }

            $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.zip';
            $destinationPath = Storage::disk($disk)->path($directory . '/' . $filename);

            $zip = new ZipArchive();
            if ($zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Failed to create backup archive.');
            }

            foreach (File::allFiles($tempDir) as $file) {
                $relativeName = Str::after($file->getPathname(), $tempDir . DIRECTORY_SEPARATOR);
                $zip->addFile($file->getPathname(), str_replace('\\', '/', $relativeName));
            }

            $zip->close();

            Log::info('Database backup created', ['filename' => $filename]);

            return $filename;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Restore the database using an uploaded temporary file.
     */
    public function restoreFromUploadedFile(string $tempPath): void
    {
        $this->runRestoreCommand($tempPath);
    }

    /**
     * Restore the database from a stored backup filename.
     */
    public function restoreFromExisting(string $filename): void
    {
        $disk = $this->getBackupDisk();
        $relativePath = $this->buildBackupRelativePath($filename);

        if (!Storage::disk($disk)->exists($relativePath)) {
            throw new \RuntimeException('Backup file not found: ' . $filename);
        }

        $archivePath = Storage::disk($disk)->path($relativePath);
        $tempDir = storage_path('app/backup-temp/restore-' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('Unable to open backup archive: ' . $filename);
        }

        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('Unable to extract backup archive: ' . $filename);
        }

        $zip->close();

        $sqlDumpPath = $this->findDatabaseDump($tempDir);

        if (!$sqlDumpPath) {
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('Database dump not found in backup archive.');
        }

        try {
            $this->runRestoreCommand($sqlDumpPath);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Delete a stored backup file.
     */
    public function deleteBackup(string $filename): void
    {
        $disk = $this->getBackupDisk();
        $relativePath = $this->buildBackupRelativePath($filename);

        if (!Storage::disk($disk)->exists($relativePath)) {
            throw new \RuntimeException('Backup file not found: ' . $filename);
        }

        if (!Storage::disk($disk)->delete($relativePath)) {
            throw new \RuntimeException('Failed to delete backup file: ' . $filename);
        }
    }

    public function getBackupAbsolutePath(string $filename): string
    {
        $disk = $this->getBackupDisk();
        $relativePath = $this->buildBackupRelativePath($filename);

        if (!Storage::disk($disk)->exists($relativePath)) {
            throw new \RuntimeException('Backup file not found: ' . $filename);
        }

        return Storage::disk($disk)->path($relativePath);
    }

    /**
     * Create a MySQL/MariaDB backup by exporting the structure and data.
     */
    private function createMySqlBackup(array $config, string $path): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );

            $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);

            $sql = "-- SDMD Database Backup\n";
            $sql .= '-- Generated on ' . now() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            // Get all tables first
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $table = trim($table);

                if (empty($table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                    $sql .= "-- Skipping invalid table name: `$table`\n\n";
                    continue;
                }

                // Check if it's a view, skip views to avoid reference issues
                $isView = false;
                try {
                    $result = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                    if ($result && isset($result['Create Table'])) {
                        $createView = $result['Create Table'] ?? '';
                        if (stripos($createView, 'CREATE VIEW') === 0) {
                            $isView = true;
                            $sql .= "-- Skipping view: `$table`\n\n";
                        }
                    }
                } catch (\Exception $e) {
                    // If we can't check if it's a view, assume it's a table and continue
                    Log::warning("Could not determine if `$table` is a view: " . $e->getMessage());
                }

                if ($isView) {
                    continue;
                }

                $sql .= "-- Table structure for `$table`\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";

                try {
                    $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                    $createSql = array_values($createTable)[1] ?? null;

                    if (!$createSql) {
                        $sql .= "-- Failed to retrieve CREATE statement for `$table`\n\n";
                        continue;
                    }

                    $sql .= $createSql . ";\n\n";

                    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $sql .= "-- Data for `$table`\n";
                        $sql .= 'INSERT INTO `' . $table . '` (`' . implode('`, `', array_keys($rows[0])) . '`) VALUES' . "\n";

                        $valueLines = [];
                        foreach ($rows as $row) {
                            $values = [];
                            foreach ($row as $value) {
                                $values[] = $value === null ? 'NULL' : $pdo->quote($value);
                            }
                            $valueLines[] = '(' . implode(', ', $values) . ')';
                        }

                        $sql .= implode(",\n", $valueLines) . ";\n\n";
                    } else {
                        $sql .= "\n";
                    }
                } catch (\Exception $e) {
                    $sql .= "-- Failed to backup table `$table`: " . $e->getMessage() . "\n\n";
                    Log::warning("Failed to backup table `$table`: " . $e->getMessage());
                    continue;
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            file_put_contents($path, $sql);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('MySQL backup failed: ' . $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Create a SQLite backup using the sqlite3 CLI.
     */
    private function createSqliteBackup(array $config, string $path): void
    {
        $command = sprintf('sqlite3 %s .dump > %s', escapeshellarg($config['database']), escapeshellarg($path));
        $this->runProcess($command, 'SQLite backup failed');
    }

    /**
     * Run restore command depending on database driver.
     */
    private function runRestoreCommand(string $sourcePath): void
    {
        $connectionName = config('database.default');
        $config = config('database.connections.' . $connectionName);

        if (!$config) {
            throw new \RuntimeException('Database configuration not found for connection: ' . $connectionName);
        }

        if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
            $this->restoreMySqlDatabase($config, $sourcePath);
        } elseif ($config['driver'] === 'sqlite') {
            $this->restoreSqliteDatabase($config, $sourcePath);
        } else {
            throw new \RuntimeException('Unsupported database driver: ' . $config['driver']);
        }

        Log::info('Database restore completed', ['source' => $sourcePath]);
    }

    private function getBackupDisk(): string
    {
        $disks = (array) config('backup.backup.destination.disks', ['local']);

        return $disks[0] ?? 'local';
    }

    private function getBackupDirectory(): string
    {
        $name = config('backup.backup.name', config('app.name', 'laravel-backup'));

        return Str::slug($name ?: 'laravel-backup');
    }

    private function buildBackupRelativePath(string $filename): string
    {
        return $this->getBackupDirectory() . '/' . basename($filename);
    }

    private function getLatestBackupPath(string $disk, string $directory): ?string
    {
        if (!Storage::disk($disk)->exists($directory)) {
            return null;
        }

        return collect(Storage::disk($disk)->files($directory))
            ->filter(fn ($path) => Str::endsWith(Str::lower($path), '.zip'))
            ->sortByDesc(fn ($path) => Storage::disk($disk)->lastModified($path))
            ->first();
    }

    private function findDatabaseDump(string $directory): ?string
    {
        foreach (File::allFiles($directory) as $file) {
            if (strtolower($file->getExtension()) === 'sql') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Restore a MySQL/MariaDB database by executing the SQL file via PDO.
     *
     * This avoids relying on the external mysql CLI, which can be fragile on
     * Windows/XAMPP and cause TCP/IP socket errors. Before replaying the backup,
     * all existing tables and views in the target database are dropped so that
     * the schema matches the backup exactly.
     */
    private function restoreMySqlDatabase(array $config, string $sourcePath): void
    {
        if (!is_readable($sourcePath)) {
            throw new \RuntimeException('Backup file not found or not readable: ' . $sourcePath);
        }

        // First, rebuild the schema from migrations so that all expected tables
        // and database objects (views, procedures, etc.) exist.
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Database migration (migrate:fresh) failed before restore.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        try {
            $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);

            // Disable foreign key checks so we can freely truncate and insert
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Replay only INSERT statements from the backup file line by line
            $statement = '';
            $truncatedTables = [];

            foreach (file($sourcePath) as $line) {
                $trimmed = trim($line);

                // Skip empty lines and comment lines
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                    continue;
                }

                $statement .= $line . "\n";

                // Execute when we reach the end of a statement
                if (substr(rtrim($line), -1) === ';') {
                    $sql = trim($statement);

                    // Only process INSERT statements; ignore DDL and other commands
                    if (stripos($sql, 'INSERT INTO ') === 0) {
                        $tableName = null;
                        if (preg_match('/^INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches)) {
                            $tableName = $matches[1] ?? null;
                        }

                        if ($tableName) {
                            $safeName = '`' . str_replace('`', '``', $tableName) . '`';

                            if (!isset($truncatedTables[$tableName])) {
                                try {
                                    $pdo->exec('TRUNCATE TABLE ' . $safeName);
                                } catch (\Throwable $e) {
                                    // If table does not exist or cannot be truncated, skip truncation
                                }
                                $truncatedTables[$tableName] = true;
                            }
                        }

                        try {
                            $pdo->exec($statement);
                        } catch (\Throwable $e) {
                            // Log the error but continue with other inserts
                            Log::warning('Failed to restore INSERT for table ' . ($tableName ?? 'unknown') . ': ' . $e->getMessage());
                        }
                    }

                    $statement = '';
                }
            }

            // Re-enable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        } catch (\Throwable $exception) {
            throw new \RuntimeException('MySQL restore failed: ' . $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Restore a SQLite database using the sqlite3 CLI.
     */
    private function restoreSqliteDatabase(array $config, string $sourcePath): void
    {
        $dbPath = $config['database'];
        $command = sprintf('sqlite3 %s < %s', escapeshellarg($dbPath), escapeshellarg($sourcePath));
        $this->runProcess($command, 'SQLite restore failed');
    }

    /**
     * Execute a shell command and throw if it fails.
     */
    private function runProcess(string $command, string $errorMessage): void
    {
        $process = Process::fromShellCommandline($command, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            throw new \RuntimeException(sprintf('%s with exit code %s: %s', $errorMessage, $exitCode, $errorOutput));
        }
    }

    /**
     * Format bytes into a human readable string.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

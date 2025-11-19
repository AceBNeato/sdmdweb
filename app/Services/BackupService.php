<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * Get all backup files with metadata.
     */
    public function listBackups(): array
    {
        $directory = $this->ensureBackupDirectoryExists();
        $backups = [];

        foreach (scandir($directory) as $file) {
            if ($file === '.' || $file === '..' || substr($file, -4) !== '.sql') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($path),
                'created_at' => date('Y-m-d H:i:s', filemtime($path)),
                'size_human' => $this->formatBytes(filesize($path)),
            ];
        }

        usort($backups, static function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Create a new database backup and return the filename.
     *
     * @throws \Exception
     */
    public function createBackup(): string
    {
        $directory = $this->ensureBackupDirectoryExists();
        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $connectionName = config('database.default');
        $config = config('database.connections.' . $connectionName);

        if (!$config) {
            throw new \RuntimeException('Database configuration not found for connection: ' . $connectionName);
        }

        if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
            $this->createMySqlBackup($config, $path);
        } elseif ($config['driver'] === 'sqlite') {
            $this->createSqliteBackup($config, $path);
        } else {
            throw new \RuntimeException('Unsupported database driver: ' . $config['driver']);
        }

        Log::info('Database backup created', ['filename' => $filename]);

        return $filename;
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
        $path = $this->getBackupFilePath($filename);

        if (!file_exists($path)) {
            throw new \RuntimeException('Backup file not found: ' . $filename);
        }

        $this->runRestoreCommand($path);
    }

    /**
     * Delete a stored backup file.
     */
    public function deleteBackup(string $filename): void
    {
        $path = $this->getBackupFilePath($filename);

        if (!file_exists($path)) {
            throw new \RuntimeException('Backup file not found: ' . $filename);
        }

        if (!unlink($path)) {
            throw new \RuntimeException('Failed to delete backup file: ' . $filename);
        }
    }

    /**
     * Ensure the backup directory exists.
     */
    private function ensureBackupDirectoryExists(): string
    {
        $directory = storage_path('app/backups');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }

    /**
     * Get the absolute path for a stored backup file.
     */
    private function getBackupFilePath(string $filename): string
    {
        return $this->ensureBackupDirectoryExists() . DIRECTORY_SEPARATOR . basename($filename);
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

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $table = trim($table);

                if (empty($table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                    $sql .= "-- Skipping invalid table name: `$table`\n\n";
                    continue;
                }

                $sql .= "-- Table structure for `$table`\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";

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

    /**
     * Restore a MySQL/MariaDB database using the mysql CLI.
     */
    private function restoreMySqlDatabase(array $config, string $sourcePath): void
    {
        $binaryPath = $config['restore_binary_path'] ?? env('MYSQL_RESTORE_BINARY');

        if (!$binaryPath) {
            $defaultWindowsPath = 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe';
            $binaryPath = file_exists($defaultWindowsPath) ? $defaultWindowsPath : 'mysql';
        }

        $passwordParam = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';

        $command = sprintf(
            '"%s" --user=%s %s --host=%s --port=%s %s < %s',
            $binaryPath,
            escapeshellarg($config['username']),
            $passwordParam,
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['database']),
            escapeshellarg($sourcePath)
        );

        $this->runProcess($command, 'MySQL restore failed');
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

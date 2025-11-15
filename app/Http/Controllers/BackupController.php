<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    /**
     * Display the backup management page.
     */
    public function index()
    {
        // Only super-admin can access
        if (!auth()->user()->is_super_admin) {
            abort(403, 'Unauthorized');
        }

        // Get list of existing backups
        $backups = $this->getBackupFiles();

        return view('backup.index', compact('backups'));
    }

    /**
     * Create a new database backup.
     */
    public function backup(Request $request)
    {
        if (!auth()->user()->is_super_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
            $path = storage_path('app/backups/' . $filename);

            // Ensure directory exists
            $backupDir = dirname($path);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $connection = config('database.default');
            $config = config('database.connections.' . $connection);

            if ($config['driver'] === 'mysql' || $config['driver'] === 'mariadb') {
                // MySQL/MariaDB backup - use programmatic approach
                $this->createMySQLBackup($config, $path);
            } elseif ($config['driver'] === 'sqlite') {
                // SQLite backup
                $command = sprintf('sqlite3 "%s" .dump > "%s"', escapeshellarg($config['database']), escapeshellarg($path));

                $process = Process::fromShellCommandline($command);
                $process->setWorkingDirectory(base_path());
                $process->run();

                if (!$process->isSuccessful()) {
                    $errorOutput = $process->getErrorOutput();
                    $exitCode = $process->getExitCode();
                    throw new \Exception("SQLite backup failed with exit code {$exitCode}: " . $errorOutput);
                }
            } else {
                throw new \Exception('Unsupported database driver: ' . $config['driver']);
            }

            Log::info('Database backup created: ' . $filename);

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());

            // Clean up empty backup file if it was created
            if (isset($path) && file_exists($path) && filesize($path) === 0) {
                unlink($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file.
     */
    public function download($filename)
    {
        if (!auth()->user()->is_super_admin) {
            abort(403, 'Unauthorized');
        }

        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            abort(404, 'Backup file not found');
        }

        return response()->download($path);
    }

    /**
     * Restore database from backup file.
     */
    public function restore(Request $request)
    {
        if (!auth()->user()->is_super_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:sql|max:51200' // 50MB max
        ]);

        try {
            $file = $request->file('backup_file');
            $tempPath = $file->getRealPath();

            $connection = config('database.default');
            $config = config('database.connections.' . $connection);

            if ($config['driver'] === 'mysql' || $config['driver'] === 'mariadb') {
                // MySQL/MariaDB restore - use full path
                $mysqlPath = 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe';

                // Build command with proper password handling
                $passwordParam = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
                $command = sprintf(
                    '"%s" --user=%s %s --host=%s --port=%s %s < "%s"',
                    $mysqlPath,
                    escapeshellarg($config['username']),
                    $passwordParam,
                    escapeshellarg($config['host']),
                    escapeshellarg($config['port']),
                    escapeshellarg($config['database']),
                    escapeshellarg($tempPath)
                );
            } elseif ($config['driver'] === 'sqlite') {
                // SQLite restore - need to recreate database
                $dbPath = $config['database'];
                $command = sprintf('sqlite3 "%s" < "%s"', escapeshellarg($dbPath), escapeshellarg($tempPath));
            } else {
                throw new \Exception('Unsupported database driver: ' . $config['driver']);
            }

            // Execute the command
            $process = Process::fromShellCommandline($command);
            $process->setWorkingDirectory(base_path()); // Set working directory to project root
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                $exitCode = $process->getExitCode();
                throw new \Exception("Command failed with exit code {$exitCode}: " . $errorOutput);
            }

            Log::info('Database restored from backup');

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup file.
     */
    public function delete($filename)
    {
        if (!auth()->user()->is_super_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            return response()->json(['error' => 'Backup file not found'], 404);
        }

        if (unlink($path)) {
            return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);
        } else {
            return response()->json(['error' => 'Failed to delete backup'], 500);
        }
    }

    /**
     * Get list of backup files with metadata.
     */
    private function getBackupFiles()
    {
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            return [];
        }

        $files = scandir($backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !preg_match('/\.sql$/', $file)) {
                continue;
            }

            $path = $backupDir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($path),
                'created_at' => date('Y-m-d H:i:s', filemtime($path)),
                'size_human' => $this->formatBytes(filesize($path))
            ];
        }

        // Sort by creation date, newest first
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Create MySQL database backup programmatically.
     */
    private function createMySQLBackup($config, $path)
    {
        try {
            // Create PDO connection
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            $sql = "-- SDMD Database Backup\n";
            $sql .= "-- Generated on " . now() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                try {
                    // Validate and sanitize table name (prevent SQL injection)
                    $table = trim($table);
                    if (empty($table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                        $sql .= "-- Skipping invalid table name: `$table`\n\n";
                        continue;
                    }

                    // Get table structure
                    $sql .= "-- Table structure for `$table`\n";
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";

                    $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                    // Get the CREATE TABLE statement (column name might vary)
                    $createSql = array_values($createTable)[1]; // Second column contains the CREATE statement
                    $sql .= $createSql . ";\n\n";

                    // Get table data
                    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $sql .= "-- Data for `$table`\n";
                        $sql .= "INSERT INTO `$table` (";

                        // Get column names
                        $columns = array_keys($rows[0]);
                        $sql .= "`" . implode("`, `", $columns) . "`";

                        $sql .= ") VALUES\n";

                        $values = [];
                        foreach ($rows as $row) {
                            $rowValues = [];
                            foreach ($row as $value) {
                                if ($value === null) {
                                    $rowValues[] = "NULL";
                                } else {
                                    $rowValues[] = $pdo->quote($value);
                                }
                            }
                            $values[] = "(" . implode(", ", $rowValues) . ")";
                        }

                        $sql .= implode(",\n", $values) . ";\n\n";
                    } else {
                        $sql .= "\n";
                    }
                } catch (\Exception $e) {
                    $sql .= "-- Error processing table `$table`: " . $e->getMessage() . "\n\n";
                    Log::warning("Failed to backup table `$table`: " . $e->getMessage());
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            // Write to file
            file_put_contents($path, $sql);

        } catch (\Exception $e) {
            throw new \Exception("MySQL backup failed: " . $e->getMessage());
        }
    }
}

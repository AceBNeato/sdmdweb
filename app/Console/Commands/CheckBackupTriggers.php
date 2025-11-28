<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class CheckBackupTriggers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:check-triggers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for backup triggers and execute backup if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check for backup triggers in the last hour
        $triggers = DB::table('activities')
            ->where('type', 'backup_trigger')   
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->get();

        if ($triggers->isEmpty()) {
            $this->info('No backup triggers found.');
            return 0;
        }

        $this->info("Found {$triggers->count()} backup trigger(s). Executing backup...");

        try {
            // Run the backup command
            $exitCode = Artisan::call('backup:database');

            if ($exitCode === 0) {
                $this->info('Backup completed successfully.');
                
                // Mark the triggers as processed
                foreach ($triggers as $trigger) {
                    DB::table('activities')
                        ->where('id', $trigger->id)
                        ->update([
                            'type' => 'backup_processed',
                            'description' => $trigger->description . ' - PROCESSED'
                        ]);
                }
            } else {
                $this->error('Backup failed.');
            }

            return $exitCode;

        } catch (\Exception $e) {
            $this->error('Error processing backup triggers: ' . $e->getMessage());
            return 1;
        }
    }
}

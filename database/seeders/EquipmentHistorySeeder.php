<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EquipmentHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first equipment and user to associate with the history
        $equipment = Equipment::first();
        $user = User::first();

        if (!$equipment || !$user) {
            $this->command->warn('No equipment or user found. Please run EquipmentSeeder and UserSeeder first.');
            return;
        }

        $actions = [
            'Performed routine maintenance',
            'Fixed hardware issue',
            'Updated software',
            'Replaced faulty component',
            'Cleaned and inspected',
            'Calibrated equipment',
            'Performed diagnostics',
            'Installed updates',
            'Replaced battery',
            'Performed system reset'
        ];

        $remarks = [
            'All systems functioning normally',
            'Issue resolved successfully',
            'Preventive maintenance completed',
            'Replaced with new part',
            'Cleaned internal components',
            'Calibration within specifications',
            'No issues found',
            'Updated to latest version',
            'Battery replaced as part of maintenance',
            'System reset to factory settings'
        ];

        $startDate = Carbon::now()->subMonths(6);
        $history = [];

        for ($i = 0; $i < 40; $i++) {
            $action = $actions[array_rand($actions)];
            $remark = $remarks[array_rand($remarks)];
            $date = $startDate->copy()->addDays(rand(0, 180))->addHours(rand(0, 23))->addMinutes(rand(0, 59));
            
            $history[] = [
                'equipment_id' => $equipment->id,
                'date' => $date,
                'jo_number' => 'JO-' . strtoupper(Str::random(6)),
                'action_taken' => $action,
                'remarks' => $remark,
                'responsible_person' => $user->name,
                'user_id' => $user->id,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        // Sort by date to ensure chronological order
        usort($history, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        foreach ($history as $record) {
            EquipmentHistory::create($record);
        }

        $this->command->info('Successfully added 40 equipment history records.');
    }
}

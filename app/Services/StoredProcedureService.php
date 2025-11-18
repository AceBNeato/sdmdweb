<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoredProcedureService
{
    /**
     * Assign or unassign equipment to/from a user
     *
     * @param int $equipmentId
     * @param int|null $userId
     * @param int $assignedById
     * @param string $action ('assign' or 'unassign')
     * @return bool
     */
    public function assignEquipment(int $equipmentId, ?int $userId, int $assignedById, string $action = 'assign'): bool
    {
        try {
            DB::statement('CALL assign_equipment(?, ?, ?, ?)', [
                $equipmentId,
                $userId,
                $assignedById,
                $action
            ]);

            Log::info("Equipment {$action} operation completed", [
                'equipment_id' => $equipmentId,
                'user_id' => $userId,
                'assigned_by' => $assignedById
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to {$action} equipment", [
                'equipment_id' => $equipmentId,
                'user_id' => $userId,
                'assigned_by' => $assignedById,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Create equipment history with status update
     *
     * @param array $historyData
     * @return bool
     */
    public function createEquipmentHistory(array $historyData): bool
    {
        try {
            DB::statement('CALL create_equipment_history(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $historyData['equipment_id'],
                $historyData['user_id'],
                $historyData['date'],
                $historyData['jo_number'],
                $historyData['action_taken'],
                $historyData['remarks'] ?? null,
                $historyData['responsible_person'],
                $historyData['equipment_status'],
                $historyData['assigned_by_id']
            ]);

            Log::info("Equipment history created", [
                'equipment_id' => $historyData['equipment_id'],
                'jo_number' => $historyData['jo_number'],
                'status' => $historyData['equipment_status']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create equipment history", [
                'equipment_id' => $historyData['equipment_id'],
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Create user with role assignments
     *
     * @param array $userData
     * @return int|null User ID on success, null on failure
     */
    public function createUserWithRoles(array $userData): ?int
    {
        try {
            $result = DB::select('CALL create_user_with_roles(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $userData['first_name'],
                $userData['last_name'],
                $userData['email'],
                $userData['password'],
                $userData['phone'] ?? null,
                $userData['position'],
                $userData['office_id'],
                $userData['campus_id'],
                json_encode($userData['role_ids']),
                $userData['created_by_id']
            ]);

            $userId = $result[0]->user_id ?? null;

            if ($userId) {
                Log::info("User created with roles", [
                    'user_id' => $userId,
                    'email' => $userData['email'],
                    'roles_count' => count($userData['role_ids'])
                ]);
            }

            return $userId;
        } catch (\Exception $e) {
            Log::error("Failed to create user with roles", [
                'email' => $userData['email'],
                'error' => $e->getMessage()
            ]);

            if ($this->isMissingCreateUserProcedure($e)) {
                Log::warning('Stored procedure create_user_with_roles missing. Falling back to Eloquent user creation.');
                return $this->fallbackCreateUserWithRoles($userData);
            }

            return null;
        }
    }

    /**
     * Detect if exception was caused by missing stored procedure.
     */
    protected function isMissingCreateUserProcedure(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, 'create_user_with_roles')
            && Str::contains($message, 'does not exist');
    }

    /**
     * Fallback user creation when stored procedure is unavailable.
     */
    protected function fallbackCreateUserWithRoles(array $userData): ?int
    {
        try {
            return DB::transaction(function () use ($userData) {
                $user = User::create([
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'phone' => $userData['phone'] ?? null,
                    'position' => $userData['position'],
                    'office_id' => $userData['office_id'],
                    'campus_id' => $userData['campus_id'],
                ]);

                if (!empty($userData['role_ids'])) {
                    $roleIds = array_map(static fn ($roleId) => (int) $roleId, $userData['role_ids']);
                    $user->roles()->sync($roleIds);
                }

                try {
                    DB::table('activities')->insert([
                        'user_id' => $userData['created_by_id'],
                        'action' => 'user.create',
                        'description' => sprintf(
                            'Created user: %s %s (%s)',
                            $userData['first_name'],
                            $userData['last_name'],
                            $userData['email']
                        ),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $activityException) {
                    Log::warning('Failed to log user creation activity in fallback path', [
                        'email' => $userData['email'],
                        'error' => $activityException->getMessage(),
                    ]);
                }

                Log::info('User created using fallback path', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return $user->id;
            });
        } catch (\Throwable $fallbackException) {
            Log::error('Fallback user creation failed', [
                'email' => $userData['email'],
                'error' => $fallbackException->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate unique JO number for given date
     *
     * @param string $date
     * @return string|null
     */
    public function generateJONumber(string $date): ?string
    {
        try {
            $result = DB::select('CALL generate_jo_number(?, @jo_number)', [$date]);
            $joNumber = DB::select('SELECT @jo_number as jo_number')[0]->jo_number ?? null;

            Log::info("JO number generated", [
                'date' => $date,
                'jo_number' => $joNumber
            ]);

            return $joNumber;
        } catch (\Exception $e) {
            Log::error("Failed to generate JO number", [
                'date' => $date,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Bulk update equipment status
     *
     * @param array $equipmentIds
     * @param string $newStatus
     * @param int $updatedById
     * @param string|null $reason
     * @return int Number of affected rows
     */
    public function bulkUpdateEquipmentStatus(array $equipmentIds, string $newStatus, int $updatedById, ?string $reason = null): int
    {
        try {
            $result = DB::select('CALL bulk_update_equipment_status(?, ?, ?, ?)', [
                json_encode($equipmentIds),
                $newStatus,
                $updatedById,
                $reason
            ]);

            $affectedRows = $result[0]->affected_rows ?? 0;

            Log::info("Bulk equipment status update completed", [
                'equipment_count' => count($equipmentIds),
                'new_status' => $newStatus,
                'affected_rows' => $affectedRows,
                'updated_by' => $updatedById
            ]);

            return $affectedRows;
        } catch (\Exception $e) {
            Log::error("Failed to bulk update equipment status", [
                'equipment_count' => count($equipmentIds),
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Validate JO number uniqueness
     *
     * @param string $joNumber
     * @return bool
     */
    public function isJOUnique(string $joNumber): bool
    {
        return !DB::table('equipment_history')->where('jo_number', $joNumber)->exists();
    }

    /**
     * Check if date can be backdated beyond latest repair
     *
     * @param int $equipmentId
     * @param string $date
     * @return array
     */
    public function canBackdateRepair(int $equipmentId, string $date): array
    {
        $latestRepair = DB::table('equipment_history')
            ->where('equipment_id', $equipmentId)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestRepair) {
            return ['can_backdate' => true];
        }

        $selectedDateTime = new \DateTime($date);
        $latestDateTime = new \DateTime($latestRepair->date);

        return [
            'can_backdate' => $selectedDateTime >= $latestDateTime,
            'latest_date' => $latestDateTime->format('Y-m-d\TH:i'),
            'message' => $selectedDateTime < $latestDateTime ?
                'Cannot backdate beyond the latest repair record.' : null
        ];
    }
}

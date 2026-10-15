<?php

namespace App\Services;

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The admin's rules for managing users (plan §5.7).
 */
class UserService
{
    /** Receives StaffService, used to release a staff member's open work when they stop being staff. */
    public function __construct(private StaffService $staff) {}

    /**
     * Admin creates a user with any role; expertise is kept only for maintenance staff.
     *
     * @param  array<string, mixed>  $data  validated by StoreUserRequest
     */
    public function create(array $data): User
    {
        $data['expertise'] = $this->expertiseFor(Role::from($data['role']), $data['expertise'] ?? null);

        return User::create($data);
    }

    /**
     * Edits a user. An admin can't deactivate themselves or change their own role.
     * Deactivating logs the user out at once. Staff who stop being active staff
     * (deactivated or no longer maintenance_staff) have their open work released.
     *
     * @param  array<string, mixed>  $data  validated by UpdateUserRequest (partial)
     */
    public function update(User $user, User $admin, array $data): User
    {
        $isSelf = $user->is($admin);
        $deactivating = $user->is_active && array_key_exists('is_active', $data) && ! $data['is_active'];

        if ($isSelf && $deactivating) {
            $this->fail('is_active', 'You cannot deactivate your own account.');
        }
        if ($isSelf && isset($data['role']) && $data['role'] !== $user->role->value) {
            $this->fail('role', 'You cannot change your own role.');
        }

        $role = isset($data['role']) ? Role::from($data['role']) : $user->role;
        $expertise = array_key_exists('expertise', $data) ? $data['expertise'] : $user->expertise;
        $data['expertise'] = $this->expertiseFor($role, $expertise);

        if (empty($data['password'])) {
            unset($data['password']); // left blank: keep the current one
        }

        return DB::transaction(function () use ($user, $data, $role, $deactivating) {
            if ($user->role === Role::MaintenanceStaff && ($role !== Role::MaintenanceStaff || $deactivating)) {
                $this->staff->releaseOpenRequests($user);
            }

            $user->update($data);

            if ($deactivating) {
                $this->endSessions($user);
            }

            return $user;
        });
    }

    /**
     * Deletes a user. Not yourself, and not anyone with reservation history (their payments must stay).
     */
    public function delete(User $user, User $admin): void
    {
        if ($user->is($admin)) {
            $this->fail('user', 'You cannot delete your own account.');
        }
        if ($user->reservations()->exists()) {
            $this->fail('user', "{$user->name} has reservation history and can't be deleted. Deactivate the account instead.");
        }

        DB::transaction(function () use ($user) {
            if ($user->role === Role::MaintenanceStaff) {
                $this->staff->releaseOpenRequests($user); // before the delete, so nothing stays "in progress" with no staff
            }

            $this->endSessions($user);
            $user->delete();
        });
    }

    /**
     * Expertise belongs to maintenance staff only: required for them, cleared for everyone else.
     */
    private function expertiseFor(Role $role, MaintenanceCategory|string|null $expertise): MaintenanceCategory|string|null
    {
        if ($role !== Role::MaintenanceStaff) {
            return null;
        }
        if ($expertise === null) {
            $this->fail('expertise', 'Choose an expertise for maintenance staff.');
        }

        return $expertise;
    }

    /**
     * Deleting the user's rows in the sessions table logs them out on their next request.
     */
    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * Stops with a 422 that shows under the form field.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

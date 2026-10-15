<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin dashboard numbers: users by role and by active status.
 */
class DashboardController extends Controller
{
    /** GET /api/admin/dashboard: counts of all users, active vs inactive, and per role. */
    public function __invoke(): JsonResource
    {
        $byRole = [];
        foreach (Role::cases() as $role) {
            $byRole[$role->value] = User::where('role', $role)->count();
        }

        $active = User::where('is_active', true)->count();

        return new JsonResource([
            'total' => array_sum($byRole),
            'active' => $active,
            'inactive' => array_sum($byRole) - $active,
            'by_role' => $byRole,
        ]);
    }
}

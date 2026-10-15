<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenants with their houses.
 */
class TenantController extends Controller
{
    /** GET /api/manager/tenants: tenants by name with the houses they own, optionally filtered by ?search=. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = User::tenants()
            ->with('houses.houseType')
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get();

        return TenantResource::collection($tenants);
    }
}

<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Enums\HouseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ManagerHouseResource;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only list of units (R7): a unit's status only changes through reservations.
 */
class HouseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $houses = House::with(['houseType', 'owner'])
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($query) => $query->where('block', 'like', "%{$search}%")
                    ->orWhere('lot', 'like', "%{$search}%")
                    ->orWhereHas('houseType', fn ($type) => $type->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('owner', fn ($owner) => $owner->where('name', 'like', "%{$search}%"))
            ))
            ->when(HouseStatus::tryFrom((string) $request->query('status')), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('house_type'), fn ($query, $slug) => $query->whereHas('houseType', fn ($type) => $type->where('slug', $slug)))
            ->orderBy('block')
            ->orderBy('lot')
            ->paginate(15);

        return ManagerHouseResource::collection($houses);
    }
}

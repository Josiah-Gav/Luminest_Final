<?php

namespace App\Http\Controllers\Api;

use App\Enums\HouseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\HouseTypeResource;
use App\Models\HouseType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * M2 House Catalog (public).
 */
class HouseTypeController extends Controller
{
    /** GET /api/house-types: the four models, cheapest first, each with its count of still-available units. */
    public function index(): AnonymousResourceCollection
    {
        $types = HouseType::withCount(['houses as available_units_count' => fn ($query) => $query->available()])
            ->orderBy('price')
            ->get();

        return HouseTypeResource::collection($types);
    }

    /** GET /api/house-types/{slug}: one model with its available units (block/lot) for the reserve form. */
    public function show(HouseType $houseType): HouseTypeResource
    {
        // Only the units someone can still reserve, for the unit dropdown.
        $houseType->load(['houses' => fn ($query) => $query
            ->where('status', HouseStatus::Available)
            ->orderBy('block')
            ->orderBy('lot')]);

        return new HouseTypeResource($houseType);
    }
}

<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The houses the tenant owns, for the "which house?" dropdown of a new request.
 */
class HouseController extends Controller
{
    /** GET /api/tenant/houses: only the houses this tenant owns. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return HouseResource::collection($request->user()->houses()->with('houseType')->get());
    }
}

<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Active maintenance staff for the assign dropdown. Phase 5 adds search and counts.
 */
class StaffController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return StaffResource::collection(User::staff()->where('is_active', true)->orderBy('name')->get());
    }
}

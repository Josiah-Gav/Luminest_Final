<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * M6 User Administration. The route group is role:admin; the rules are in UserService.
 */
class UserController extends Controller
{
    /** Receives UserService, which holds the rules for creating, editing and deleting users. */
    public function __construct(private UserService $users) {}

    /** GET /api/admin/users: users by name, 15 per page, filtered by ?search= (name, email, phone) and ?role=. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
            ))
            ->when(Role::tryFrom((string) $request->query('role')), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15);

        return UserResource::collection($users);
    }

    /** POST /api/admin/users: creates a user with any role (rules in UserService::create). */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /** GET /api/admin/users/{id}: one user's details. */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /** PUT/PATCH /api/admin/users/{id}: edits, activates or deactivates a user (rules in UserService::update). */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->users->update($user, $request->user(), $request->validated()));
    }

    /** DELETE /api/admin/users/{id}: deletes a user unless it is the admin themselves or has reservations (UserService::delete). */
    public function destroy(Request $request, User $user): Response
    {
        $this->users->delete($user, $request->user());

        return response()->noContent();
    }
}

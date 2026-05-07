<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        return response()->json([
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'permissions' => Permission::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query()->with('roles:id,name')->latest();

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($userQuery) use ($term) {
                $userQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'sanctum']);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions:id,name'),
        ], 201);
    }

    public function updateRolePermissions(Request $request, int $id)
    {
        $role = Role::findById($id, 'sanctum');
        $data = $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($data['permissions']);

        return response()->json([
            'message' => 'Role permissions updated',
            'role' => $role->fresh()->load('permissions:id,name'),
        ]);
    }

    public function updateUserRoles(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'roles' => 'present|array',
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'sanctum')],
        ]);

        $user->syncRoles($data['roles']);

        return response()->json([
            'message' => 'User roles updated',
            'user' => $user->fresh()->load('roles:id,name'),
        ]);
    }
}

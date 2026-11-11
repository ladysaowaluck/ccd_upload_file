<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminUser::with(['roles', 'office', 'department', 'division']);

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', $search)
                    ->orWhere('name', 'LIKE', $search)
                    ->orWhere('email', 'LIKE', $search);
            });
        }

        if ($request->filled('role')) {
            $roleSlug = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleSlug) {
                $q->where('slug', $roleSlug);
            });
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $roles = AdminRole::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function show(int|string $id): View
    {
        $user = AdminUser::with(['roles.permissions', 'office', 'department', 'division'])->findOrFail($id);
        $allRoles = AdminRole::orderBy('name')->get();

        return view('admin.users.show', compact('user', 'allRoles'));
    }

    /**
     * Update roles assigned to a user.
     */
    public function updateRoles(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:authdb.ccd_admin_roles,id',
        ]);

        $user = AdminUser::findOrFail($id);
        $user->roles()->sync($request->input('role_ids', []));

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', "Roles updated successfully for user '{$user->username}'.");
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(): Response
    {
        $users = User::with('roles')->get();

        return Inertia::render('admins/index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admins/create', [
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admins.index');
    }

    public function edit(int $id): Response
    {
        $user = User::with('roles')->findOrFail($id);

        return Inertia::render('admins/edit', [
            'admin' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admins.index');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ($request->user()->id == $id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Kendi hesabınızı silemezsiniz.',
            ]);

            return redirect()->route('admins.index');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admins.index');
    }
}

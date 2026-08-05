<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();

        return Inertia::render('admins/index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        return Inertia::render('admins/create', [
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
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

    public function edit($id)
    {
        $user = User::with('roles')->find($id);

        return Inertia::render('admins/edit', [
            'admin' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
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

    public function destroy(Request $request, $id)
    {
        if ($request->user()->id == $id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Kendi hesabınızı silemezsiniz.',
            ]);

            return redirect()->route('admins.index');
        }

        $user = User::find($id);
        $user->delete();

        return redirect()->route('admins.index');
    }
}

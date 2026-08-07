<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('depo görevlisi cannot access the sistem yöneticileri page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('admins.index'));

    $response->assertForbidden();
});

test('süper admin cannot delete their own account', function () {
    $admin = actingAsSuperAdmin();

    $response = $this->delete(route('admins.destroy', $admin->id));

    $response->assertRedirect(route('admins.index'));
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('süper admin can delete another admin account', function () {
    actingAsSuperAdmin();

    $otherAdmin = User::factory()->create();

    $response = $this->delete(route('admins.destroy', $otherAdmin->id));

    $response->assertRedirect(route('admins.index'));
    $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
});

test('süper admin can create a new admin with a role', function () {
    actingAsSuperAdmin();
    Role::findOrCreate('Depo Görevlisi');

    $response = $this->post(route('admins.store'), [
        'name' => 'Yeni Personel',
        'email' => 'yeni@example.com',
        'password' => 'password123',
        'role' => 'Depo Görevlisi',
    ]);

    $response->assertRedirect(route('admins.index'));
    $this->assertDatabaseHas('users', ['email' => 'yeni@example.com']);

    $newUser = User::where('email', 'yeni@example.com')->first();
    expect($newUser->hasRole('Depo Görevlisi'))->toBeTrue();
});

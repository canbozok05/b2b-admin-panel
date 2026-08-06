<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Süper Admin']);
        Role::create(['name' => 'Depo Görevlisi']);

        $testUser = User::where('email', 'test@example.com')->first();
        $testUser->assignRole('Süper Admin');

        $depoUser = User::firstOrCreate(
            ['email' => 'depo@example.com'],
            ['name' => 'Depo Görevlisi', 'password' => Hash::make('password')]
        );
        $depoUser->assignRole('Depo Görevlisi');
    }
}

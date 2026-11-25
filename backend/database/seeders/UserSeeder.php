<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin LinguaPro',
            'email' => 'admin@linguapro.mx',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Prof. Laura R.',
            'email' => 'laura@linguapro.mx',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        User::create([
            'name' => 'Evelyn Guzmán',
            'email' => 'evelyn@linguapro.mx',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        User::create([
            'name' => 'Ana Gómez',
            'email' => 'ana@linguapro.mx',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        User::create([
            'name' => 'Carlos Díaz',
            'email' => 'carlos@linguapro.mx',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);
    }
}

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
            'name'       => 'Admin',
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin@academy.ro',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
        ]);

        User::create([
            'name'       => 'Events Manager',
            'first_name' => 'Events',
            'last_name'  => 'Manager',
            'email'      => 'manager@academy.ro',
            'password'   => Hash::make('password'),
            'role'       => 'events_manager',
        ]);

        User::create([
            'name'               => 'Dr. Theodor Cebotaru',
            'first_name'         => 'Theodor',
            'last_name'          => 'Cebotaru',
            'email'              => 'theodor.cebotaru@academy.ro',
            'password'           => Hash::make('password'),
            'role'               => 'doctor',
            'specialty'          => 'chirurgie',
            'professional_grade' => 'medic-primar',
        ]);

        User::create([
            'name'       => 'Participant Test',
            'first_name' => 'Test',
            'last_name'  => 'Participant',
            'email'      => 'participant@academy.ro',
            'password'   => Hash::make('password'),
            'role'       => 'participant',
        ]);
    }
}

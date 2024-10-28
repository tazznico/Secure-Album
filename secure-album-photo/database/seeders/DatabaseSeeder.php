<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créez 3 utilisateurs par défaut
        User::create([
            'name' => 'tazznico',
            'email' => 'tazznico@hotmail.com',
            'password' => Hash::make('tazznico'),
        ]);

        User::create([
            'name' => 'nicotazz',
            'email' => 'nicotazz@hotmail.com',
            'password' => Hash::make('nicotazz'),
        ]);

        User::create([
            'name' => 'michelle',
            'email' => 'michelle@hotmail.com',
            'password' => Hash::make('michelle'),
        ]);
    }
}

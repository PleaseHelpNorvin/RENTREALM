<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'John Doe', 
            'email' => 'landlord@example.com',
            'password' => Hash::make('password'), // You can use any password you want
            'role' => User::LANDLORD,
        ]);
    }
}

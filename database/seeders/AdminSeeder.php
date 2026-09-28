<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'quincyjane.oliver@nmsc.edu.ph'],
            [
                'user_type'      => 'Admin',
                'name'           => 'Quincy Jane Oliver',
                'password'       => 'admin123',
                'contact_number' => null,
            ]
        );
    }
}

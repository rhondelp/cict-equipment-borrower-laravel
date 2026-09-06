<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Idempotent: uses updateOrCreate keyed on email (users) and
     * equipment_name (equipment), so running `php artisan db:seed`
     * repeatedly will not create duplicates and will refresh the
     * password / status fields on existing rows.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedInstructors();
        $this->seedStudents();
        $this->seedEquipment();
    }

    private function seedAdmin(): void
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

    private function seedInstructors(): void
    {
        $instructors = [
            ['name' => 'Maria Santos',   'email' => 'maria.santos@nmsc.edu.ph',   'contact_number' => '09171234567'],
            ['name' => 'Jose Reyes',     'email' => 'jose.reyes@nmsc.edu.ph',     'contact_number' => '09181234567'],
            ['name' => 'Ana Cruz',       'email' => 'ana.cruz@nmsc.edu.ph',       'contact_number' => '09191234567'],
        ];

        foreach ($instructors as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'user_type'      => 'Instructor',
                    'name'           => $row['name'],
                    'password'       => 'password',
                    'contact_number' => $row['contact_number'],
                ]
            );
        }
    }

    private function seedStudents(): void
    {
        $students = [
            ['name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@student.nmsc.edu.ph', 'contact_number' => '09201111111'],
            ['name' => 'Maria Garcia',   'email' => 'maria.garcia@student.nmsc.edu.ph', 'contact_number' => '09202222222'],
            ['name' => 'Pedro Mendoza',  'email' => 'pedro.mendoza@student.nmsc.edu.ph','contact_number' => '09203333333'],
            ['name' => 'Liza Bautista',  'email' => 'liza.bautista@student.nmsc.edu.ph','contact_number' => '09204444444'],
            ['name' => 'Carlo Ramos',    'email' => 'carlo.ramos@student.nmsc.edu.ph',  'contact_number' => '09205555555'],
        ];

        foreach ($students as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'user_type'      => 'Student',
                    'name'           => $row['name'],
                    'password'       => 'password',
                    'contact_number' => $row['contact_number'],
                ]
            );
        }
    }

    private function seedEquipment(): void
    {
        $items = [
            ['equipment_name' => 'Laptop (Dell Latitude)',  'description' => '14-inch laptop for classroom use',                 'quantity' => 10, 'available_quantity' => 10, 'status' => 'Available'],
            ['equipment_name' => 'Projector (Epson)',      'description' => 'Portable projector for presentations',              'quantity' => 4,  'available_quantity' => 4,  'status' => 'Available'],
            ['equipment_name' => 'HDMI Cable',             'description' => '3m HDMI cable for projector / monitor hookups',     'quantity' => 20, 'available_quantity' => 20, 'status' => 'Available'],
            ['equipment_name' => 'Wireless Presenter',     'description' => 'Slide advancer with USB receiver',                 'quantity' => 8,  'available_quantity' => 8,  'status' => 'Available'],
            ['equipment_name' => 'Extension Cord',         'description' => '5m heavy-duty extension cord',                     'quantity' => 12, 'available_quantity' => 12, 'status' => 'Available'],
            ['equipment_name' => 'Document Camera',        'description' => 'Visualizer for projecting documents',              'quantity' => 2,  'available_quantity' => 2,  'status' => 'Available'],
            ['equipment_name' => 'Microphone Set',         'description' => 'Wireless lapel + handheld microphone pair',        'quantity' => 3,  'available_quantity' => 3,  'status' => 'Available'],
            ['equipment_name' => 'Whiteboard Markers',     'description' => 'Pack of 4 assorted colors',                        'quantity' => 30, 'available_quantity' => 30, 'status' => 'Available'],
        ];

        foreach ($items as $row) {
            Equipment::updateOrCreate(
                ['equipment_name' => $row['equipment_name']],
                [
                    'description'        => $row['description'],
                    'quantity'           => $row['quantity'],
                    'available_quantity' => $row['available_quantity'],
                    'status'             => $row['status'],
                ]
            );
        }
    }
}

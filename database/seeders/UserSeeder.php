<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Clinician',
                'email' => 'clinician@carelink.mail',
                'role' => 'CLINICIAN',
                'facility_id' => 'FAC-0101',
            ],
            [
                'name' => 'District Health Officer',
                'email' => 'district@carelink.mail',
                'role' => 'DISTRICT_OFFICER',
                'facility_id' => null,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('DemoPass123!'),
                    'role' => $user['role'],
                    'facility_id' => $user['facility_id'],
                ]
            );
        }

        $this->command->info('CareLink demonstration accounts created.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_created(): void
    {
        $this->seed(UserSeeder::class);

        $clinician = User::where(
            'email', 'clinician@carelink.mail'
        )->firstOrFail();

        $district = User::where(
            'email', 'district@carelink.mail'
        )->firstOrFail();

        $this->assertSame('CLINICIAN', $clinician->role);
        $this->assertSame('FAC-0101', $clinician->facility_id);

        $this->assertSame('DISTRICT_OFFICER', $district->role);
        $this->assertNull($district->facility_id);

        $this->assertTrue(
            Hash::check('DemoPass123!', $clinician->password)
        );
    }

    // public function test_running_seeder_twice_does_not_duplicate_users(): void
    // {
    //     $this->seed(UserSeeder::class);
    //     $this->seed(UserSeeder::class);

    //     $this->assertSame(
    //         2,
    //         User::whereIn('email', [
    //             'clinician@carelink.test',
    //             'district@carelink.test',
    //         ])->count()
    //     );
    // }
}

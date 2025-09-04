<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Profile;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'ADMIN',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $admin->id,
        ]);

        $userOne = User::create([
            'full_name' => 'User one',
            'email' => 'user.one@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'USER',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $userOne->id,
        ]);

        $userTwo = User::create([
            'full_name' => 'User two',
            'email' => 'user.two@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'USER',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $userTwo->id,
        ]);

        $userThree = User::create([
            'full_name' => 'User three',
            'email' => 'user.three@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'USER',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $userThree->id,
        ]);

        $partnerOne = User::create([
            'full_name' => 'Partner one',
            'email' => 'partner.one@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'PARTNER',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $partnerOne->id,
        ]);

        $partnerTwo = User::create([
            'full_name' => 'Partner two',
            'email' => 'partner.two@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'PARTNER',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $partnerTwo->id,
        ]);

        $challenges = ['Health', 'Fitness', 'Productivity', 'Learning'];
        foreach ($challenges as $challenge) {
            Challenge::create([
                'challenge_type' => $challenge,
                'note' => 'This is ' . $challenge . ' type.'
            ]);
        }
    }
}

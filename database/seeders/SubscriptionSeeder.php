<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscription::create([
            'plan_name' => 'Free',
            'duration' => 'Ongoing',
            'price' => 0.00,
            'features' => [
                'Join challenge group & activity',
                'Only 5 habits added',
                'Only 5 Say No added',
                'Earn point 1 per work done',
            ],
            'active_subscribers' => 3,
        ]);

        Subscription::create([
            'plan_name' => 'Premium',
            'duration' => 'Monthly',
            'price' => 5.99,
            'features' => [
                'Creating a challenge group',
                'Unlimited habits added',
                'Unlimited Say No added',
                'Advanced graphical analytics',
                'Earn point 2x per work done',
                'Reward redemption by point'
            ],
            'active_subscribers' => 0,
        ]);

        Subscription::create([
            'plan_name' => 'Premium',
            'duration' => 'Yearly',
            'price' => 29.99,
            'features' => [
                'Creating a challenge group',
                'Unlimited habits added',
                'Unlimited Say No added',
                'Advanced graphical analytics',
                'Earn point 2x per work done',
                'Reward redemption by point'
            ],
            'active_subscribers' => 0,
        ]);
    }
}

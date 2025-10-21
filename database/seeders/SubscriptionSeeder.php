<?php

namespace Database\Seeders;

use App\Models\Subscription;
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
                'Basic challenges',
                'Unlimited habits tracking',
                'Unlimited Say No'
            ],
            'active_subscribers' => 0,
        ]);

        Subscription::create([
            'plan_name' => 'Premium',
            'duration' => 'Monthly',
            'price' => 5.99,
            'features' => [
                'Basic challenges',
                'Unlimited habits tracking',
                'Unlimited Say No',
                'Advanced analytics',
                'Premium rewards (earn point 2x)'
            ],
            'active_subscribers' => 0,
        ]);

        Subscription::create([
            'plan_name' => 'Premium',
            'duration' => 'Yearly',
            'price' => 29.99,
            'features' => [
                'Basic challenges',
                'Unlimited habits tracking',
                'Unlimited Say No',
                'Advanced analytics',
                'Premium rewards (earn point 2x)'
            ],
            'active_subscribers' => 0,
        ]);
    }
}

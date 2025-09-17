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
            'duration' => '-',
            'price' => 0.00,
            'features' => '2 features available',
            'active_subscribers' => 0,
        ]);

        Subscription::create([
            'plan_name' => 'Premium',
            'duration' => '1 Month',
            'price' => 29.99,
            'features' => 'All features available',
            'active_subscribers' => 0,
        ]);
    }
}

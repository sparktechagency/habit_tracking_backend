<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use Ramsey\Uuid\Type\Decimal;

class SubscriptionService
{
    public function getSubscriptions()
    {
        return Subscription::latest('id')->get();
    }

    public function editPremiumPrice(?int $id, ?float $price)
    {
        $subscription = Subscription::where('id', $id)->first();
        $subscription->price = $price ?? $subscription->price;
        $subscription->save();
        return $subscription;
    }
}
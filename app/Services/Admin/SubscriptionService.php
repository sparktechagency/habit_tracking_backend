<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use Ramsey\Uuid\Type\Decimal;

class SubscriptionService
{
    public function getSubscriptions(?string $search)
    {
        $query = Subscription::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest()->get();

    }
    public function editPremiumPrice(?int $id, ?float $price)
    {
        $subscription = Subscription::where('id', $id)->first();
        $subscription->price = $price ?? $subscription->price;
        $subscription->save();
        return $subscription;
    }
}
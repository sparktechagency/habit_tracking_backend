<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Type\Decimal;

class SubscriptionService
{
    public function addSubscription(array $data): Subscription
    {
        return Subscription::create($data);
    }
    public function getSubscriptions(?string $search)
    {
        $query = Subscription::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();

    }
    public function editSubscription(?int $id, array $data)
    {
        $subscription = Subscription::where('id', $id)->first();

        if ($subscription->plan_name == 'Free') {
            $subscription->features = $data['features'];
            $subscription->save();
        } else {
            $subscription->plan_name = $data['plan_name'];
            $subscription->duration = $data['duration'];
            $subscription->price = $data['price'];
            $subscription->features = $data['features'];
            $subscription->save();
        }

        return $subscription;
    }

    public function deleteSubscription(int $id): bool
    {
        $challenge = Subscription::find($id);

        if ($challenge && $challenge->plan_name != 'Free') {
            return $challenge->delete();
        }
        return false;
    }

}
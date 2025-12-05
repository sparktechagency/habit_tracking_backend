<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
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

        if (!$subscription) {
            throw ValidationException::withMessages([
                'message' => 'Subscription id not found.',
            ]);
        }

        if ($subscription->plan_name == 'Free') {
            $subscription->features = $data['features'] ?? $subscription->features;
            $subscription->save();
        } else {
            $subscription->plan_name = $data['plan_name'] ?? $subscription->plan_name;
            $subscription->duration = $data['duration'] ?? $subscription->duration;
            $subscription->price = $data['price'] ?? $subscription->price;
            $subscription->discount = $data['discount'] ?? $subscription->discount;
            $subscription->features = $data['features'] ?? $subscription->features;
            $subscription->save();
        }

        return $subscription;
    }
    public function deleteSubscription(int $id): bool
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            throw ValidationException::withMessages([
                'message' => 'Subscription id not found.',
            ]);
        }

        if ($subscription && $subscription->plan_name != 'Free') {
            return $subscription->delete();
        }
        return false;
    }

}
<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

    // =======================free subscription buying==============================
    public function addFreeSubscription(array $data)
    {
        $subscription = Subscription::find($data['subscription_id']);

        $isPlan = Plan::where('user_id', $data['user_id'])->first();

        if ($isPlan->status == 'Paid' && $isPlan->renewal > Carbon::now()) {
            throw ValidationException::withMessages([
                'message' => 'Plan already running.',
            ]);
        }

        if ($isPlan->status == 'Gift') {
            throw ValidationException::withMessages([
                'message' => 'Free plan already exists. You can renew the plan if you want',
            ]);
        }

        $plan = Plan::create([
            'user_id' => $data['user_id'],
            'plan_name' => $subscription->plan_name,
            'duration' => $subscription->duration,
            'price' => 0,
            'features' => json_encode($subscription->features),
            'renewal' => $subscription->duration == 'Monthly' ? Carbon::now()->addMonth() : Carbon::now()->addYear(),
            'status' => 'Gift',
            'store' => null,
            'storeTransactionId' => null,
        ]);

        return $plan;
    }

    public function getFreeSubscriptions()
    {
        $giftPlans = Plan::where('status', 'Gift')->get();

        foreach ($giftPlans as $giftPlan) {
            if ($giftPlan->renewal < Carbon::now()) {
                $giftPlan->isRenew = true;
            } else {
                $giftPlan->isRenew = false;
            }

            $giftPlan->features = json_decode($giftPlan->features);
        }

        return $giftPlans;
    }

    public function viewFreeSubscription($id)
    {
        $giftPlan = Plan::find($id);

        if (!$giftPlan) {
            throw ValidationException::withMessages([
                'message' => 'Plan id not found!',
            ]);
        }

        $giftPlan->features = json_decode($giftPlan->features);

        return $giftPlan;
    }

    public function removeFreeSubscription($id)
    {
        $giftPlan = Plan::find($id);

        if (!$giftPlan) {
            throw ValidationException::withMessages([
                'message' => 'Plan id not found!',
            ]);
        }

        return $giftPlan->delete();
    }

    public function renewFreeSubscription($id, $duration)
    {
        $giftPlan = Plan::find($id);

        if (!$giftPlan) {
            throw ValidationException::withMessages([
                'message' => 'Plan id not found!',
            ]);
        }

        if ($giftPlan->renewal < Carbon::now()) {
            $giftPlan->renewal = $duration == 'Monthly' ? Carbon::now()->addMonth() : Carbon::now()->addYear();
            $giftPlan->save();
        } else {
            throw ValidationException::withMessages([
                'message' => 'The plan is already active. You can renew the plan after this period ends.',
            ]);
        }

        return $giftPlan;
    }


    // =======================refund==============================
    public function getPlans()
    {
        $plans = Plan::whereNotIn('status', ['Gift', 'Refund'])->get();

        foreach ($plans as $plan) {
            $plan->features = json_decode($plan->features);
            if ($plan->renewal < Carbon::now()) {
                $plan->isRefund = false;
            } else {
                $plan->isRefund = true;
            }
        }

        return $plans;
    }

    public function viewPlan($id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            throw ValidationException::withMessages([
                'message' => 'Plan id not found!',
            ]);
        }

        $plan->features = json_decode($plan->features);

        return $plan;
    }

    public function refund($storeTransactionId)
    {
        $plan = Plan::where('storeTransactionId', $storeTransactionId)->first();

        if (!$plan) {
            throw ValidationException::withMessages([
                'message' => 'storeTransactionId id not found!',
            ]);
        }

        if ($plan->renewal < Carbon::now()) {
            throw ValidationException::withMessages([
                'message' => 'The plan is already expired.',
            ]);
        } else {
            if ($plan->status == 'Paid') {
                $plan->renewal = Carbon::now()->subDay();
                $plan->status = 'Refund';
                $plan->save();
            }
        }

        return $plan;
    }
}

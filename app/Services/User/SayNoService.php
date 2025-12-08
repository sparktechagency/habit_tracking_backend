<?php

namespace App\Services\User;

use App\Models\Entry;
use App\Models\Habit;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SayNoService
{
    public function addEntry(array $data): Entry
    {
        $max = 5;

        $plan = Plan::where('user_id', Auth::id())->latest()->first();
        if ($plan) {
            if ($plan->renewal >= Carbon::now()) {
                $plan->features = json_decode($plan->features);
                $is_premium_user = true;
            } else {
                $is_premium_user = false;
            }
        } else {
            $is_premium_user = false;
        }

        // if ($is_premium_user == false) {
        //     if (Entry::where('user_id', Auth::id())->count() == $max) {
        //         throw ValidationException::withMessages([
        //             // 'message' => 'You already have ' . $max . ' entries created. You cannot create more than ' . $max . ' entries.',
        //             'message' => 'Free users can add up to '.$max.' say no only. Upgrade to premium to add unlimited say no.',
        //         ]);
        //     }
        // }

        if ($is_premium_user == false) {

            $free = Subscription::where('plan_name', 'Free')->first();

            if (!in_array("Unlimited Say No added", $free->features)) {
                if (Entry::where('user_id', Auth::id())->count() == $max) {
                    throw ValidationException::withMessages([
                        // 'message' => 'You already have ' . $max . ' habits created. You cannot create more than ' . $max . ' habits.',
                        'message' => 'Free users can add up to ' . $max . ' say no only. Upgrade to premium to add unlimited say no.',
                    ]);
                }
            }

        } else {
            if (!in_array("Unlimited Say No added", $plan->features)) {
                if (Entry::where('user_id', Auth::id())->count() == $max) {
                    throw ValidationException::withMessages([
                        // 'message' => 'You already have ' . $max . ' habits created. You cannot create more than ' . $max . ' habits.',
                        'message' => 'You can add up to ' . $max . ' say no only. "Unlimited Say No added" feature not available for you.',
                    ]);
                }
            }
        }

        return Entry::create([
            'user_id' => Auth::id(),
            'date' => Carbon::now(),
            'say_no' => $data['say_no'],
        ]);
    }
    public function getEntries(?string $filter, ?int $per_page)
    {
        $query = Entry::where('user_id', Auth::id());
        $now = Carbon::now();

        if ($filter === 'day') {
            $query->whereDate('date', $now->toDateString());
        } elseif ($filter === 'week') {
            $query->whereBetween('date', [$now->subDays(6), Carbon::now()]);
        } elseif ($filter === 'month') {
            $query->whereYear('date', $now->year)
                ->whereMonth('date', $now->month);
        } elseif ($filter === 'year') {
            $query->whereYear('date', $now->year);
        }
        return $query->latest()->paginate($per_page ?? 10);
    }
    public function viewEntry(int $id): ?Entry
    {
        return Entry::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }
    public function deleteEntry(int $id): bool
    {
        $entry = Entry::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        if ($entry) {
            $entry->delete();
            return true;
        }
        return false;
    }
}
<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Reward;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Type\Decimal;

class PartnerBusinessService
{
    public function getPartners(?string $search, ?int $per_page)
    {
        $query = User::query();

        $query->with('profile')->where('role', 'PARTNER');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%");
            });
        }
        $partners = $query->paginate($per_page ?? 10);

        foreach ($partners as $partner) {

            $partner->rewards_offered = Reward::where('partner_id', $partner->id)->count();
        }

        return $partners;

    }
}
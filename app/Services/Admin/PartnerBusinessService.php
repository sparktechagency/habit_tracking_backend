<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\User;
use Ramsey\Uuid\Type\Decimal;

class PartnerBusinessService
{
   public function getPartners(?string $search){
        $query = User::query();

        $query->with('profile')->where('role','PARTNER');

         if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->get(); 
    }
}
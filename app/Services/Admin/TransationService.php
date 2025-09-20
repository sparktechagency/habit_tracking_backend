<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Ramsey\Uuid\Type\Decimal;

use function PHPUnit\Framework\isEmpty;

class TransationService
{
    public function getTransations(?int $userId, ?int $per_page)
    {
        $query = Transaction::query();

        if ($userId) {
            $query->where('user_id', $userId);
            return $query->latest()->first();
        }

        return $query->latest()->paginate($per_page ?? 10);
    }
}
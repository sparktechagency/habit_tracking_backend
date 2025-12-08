<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Type\Decimal;

use function PHPUnit\Framework\isEmpty;

class TransationService
{
    public function getTransations(?int $userId, ?int $per_page)
    {
        $query = Transaction::query();

        if ($userId) {

            throw ValidationException::withMessages([
                'message' => 'No transaction here.',
            ]);

            // if (!Transaction::where('user_id', $userId)->latest()->first()) {
            //     throw ValidationException::withMessages([
            //         'message' => 'No transaction here.',
            //     ]);
            // }

            // $query->where('user_id', $userId);
            // return $query->latest()->first();
        }

        return $query->latest()->paginate($per_page ?? 10);
    }
}
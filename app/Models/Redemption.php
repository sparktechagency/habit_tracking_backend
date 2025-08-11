<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redemption extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function reward()
    {
        return $this->belongsTo(Reward::class, 'reward_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupHabit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function challenge_group()
    {
        return $this->belongsTo(ChallengeGroup::class);
    }
}

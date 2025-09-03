<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeGroup extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function group_habits()
    {
        return $this->hasMany(GroupHabit::class);
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }
}

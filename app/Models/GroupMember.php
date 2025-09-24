<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public function challenge_group()
    {
        return $this->belongsTo(ChallengeGroup::class);
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function logs()
{
    return $this->hasMany(ChallengeLog::class, 'user_id', 'user_id')
        ->whereColumn('challenge_group_id', 'challenge_group_id');
}

}

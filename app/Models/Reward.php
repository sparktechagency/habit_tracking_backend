<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

     protected $appends = ['image_url'];

    // expiration_date কে Carbon instance বানাতে
    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset($this->image)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($this->title);
    }


}

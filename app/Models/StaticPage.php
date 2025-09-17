<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    protected $table = 'static_pages';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_active',
    ];

    protected $hidden = [
        'is_active',
    ];

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}

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

    // slug দিয়ে খোঁজার জন্য convenience scope
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}

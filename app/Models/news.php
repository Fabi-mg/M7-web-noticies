<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class news extends Model
{
    protected $fillable = [
        'title',
        'newsUrl',
        'imageUrl',
        'themes_id',
    ];

    public function themes()
    {
        return $this->belongsTo(themes::class);
    }
}

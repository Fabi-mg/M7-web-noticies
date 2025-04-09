<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class themes extends Model
{
    protected $fillable = [
        'title',
        'traffic',
        'imageUrl',
        'publicationDate',
    ];

    protected function casts()
    {
        return [
            'publicationDate' => 'datetime',
        ];
    }
}

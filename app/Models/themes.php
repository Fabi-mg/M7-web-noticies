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
        'published',
    ];

    protected function casts()
    {
        return [
            'publicationDate' => 'datetime',
        ];
    }

    public function news()
    {
        return $this->hasMany(news::class);
    }
}

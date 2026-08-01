<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'region', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    //
    protected $fillable = [
        'line_1', 'line_2', 'province', 'country', 'postal_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}

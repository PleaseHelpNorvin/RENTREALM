<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class RentalAgreement extends Model
{
    use HasFactory;
    protected $fillable = [
        'inquiry_id',
        'agreement_code',
        'rent_start_date',
        'rent_end_date',

        'rent_price',
        'deposit',
        'description',
        'signature_svg_string',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }
}



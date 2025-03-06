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

        // 'rent_price',
        'person_count',
        'total_monthly_due',

        'description',
        'signature_png_string',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rent_start_date'=> 'date',
        'end_date_date' => 'date',
        'signature_png_string' => 'array',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }
}



<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    //
    protected $fillable = [
        'line_1',
        'line_2', 
        'province', 
        'country', 
        'postal_code', 
        'latitude', 
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float', 
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }


    public static function getCoordinates($address)
    {
        $apiKey = config('services.gomaps.key');
       
        $url = "https://maps.gomaps.pro/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;

        \Log::info('GoMaps Request Url:', ['url' => $url]);

        $response = Http::withoutVerifying()->get($url);
        $data = $response->json();

        // \Log::info('GoMaps API Response:', $data);

        if (!empty($data['results'][0]['geometry']['location'])) {
            return [
                'latitude' => $data['results'][0]['geometry']['location']['lat'],
                'longitude' => $data['results'][0]['geometry']['location']['lng'],
            ];
        }

        return null;
    }

}

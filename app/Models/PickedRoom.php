<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickedRoom extends Model
{
    use HasFactory;
    
    protected $table = 'picked_room';

    protected $fillable = ['user_id', 'room_id'];


    protected $cast = [
        'create_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

}

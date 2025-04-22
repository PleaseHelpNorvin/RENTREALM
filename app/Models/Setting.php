<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

//   protected $table = 'settings';

  protected $fillable = [
      'user_id', 
      'default_min_lease', 
      'default_reservation_fee'
  ];

  public function user()
  {
      return $this->belongsTo(User::class);
  }
  
}

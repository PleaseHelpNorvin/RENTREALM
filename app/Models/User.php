<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{  
    const LANDLORD = 'landlord';
    const HANDYMAN = 'handyman';
    const TENANT = 'tenant';
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'steps'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    //relationships
    // public function userProfile()
    // {
    //     return $this->hasOne(UserProfile::class);
    // }

    // Role checkers
    public function isLandlord(): bool
    {
        return $this->role === self::LANDLORD;
    }

    public function isHandyman(): bool
    {
        return $this->role === self::HANDYMAN;
    }

    public function isTenant(): bool
    {
        return $this->role === self::TENANT;
    }
    

    // General role checker (optional)
    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function handyman()
    {
        return $this->hasOne(Handyman::class);
    }

    public function pickedRooms()
    {
        return $this->hasMany(PickedRoom::class);
    }

    public function reservations()
    {
        return $this->hasManyThrough(Reservation::class, PickedRoom::class);
    }

    public function approvedBy()
    {
        return $this->hasMany(Reservation::class, 'approved_by');
    }

    public function assignedRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_by');
    }
    public function ApproveRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'approved_by');
    }
}

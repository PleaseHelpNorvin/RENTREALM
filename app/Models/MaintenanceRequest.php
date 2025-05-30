<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'tenant_id',
        'room_id',
        'handyman_id',
        'assigned_by', //done relationship hasmany
        'title',
        'description',
        'images',
        'status',
        'requested_at',
        'assisted_at',
        'approved_at',
        'completed_at',
        'approved_by',
    ];

    protected $casts = [
        'images' => 'array',
        'requested_at' => 'datetime',
        'assisted_at' => 'datetime',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // protected $hidden = [
    //     'tenant_id',
    //     'room_id',
    //     'handyman_id',
    // ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function handyman()
    {
        return $this->belongsTo(Handyman::class, 'handyman_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}

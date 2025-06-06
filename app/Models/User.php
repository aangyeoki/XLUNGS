<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function xrayDiagnoses()
    {
        return $this->hasMany(XRayDiagnosis::class);
    }

    public function latestXrayDiagnoses($limit = 5)
    {
        return $this->xrayDiagnoses()->orderBy('created_at', 'desc')->limit($limit);
    }

    public function getTotalDiagnosesAttribute()
    {
        return $this->xrayDiagnoses()->count();
    }
}

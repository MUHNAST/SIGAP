<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginDevice extends Model
{
    protected $fillable = [
        'id_user',
        'device_fingerprint',
        'nama_perangkat',
        'browser',
        'platform',
        'ip_address',
        'session_id',
        'aktivitas_terakhir',
    ];

    protected $casts = [
        'aktivitas_terakhir' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}

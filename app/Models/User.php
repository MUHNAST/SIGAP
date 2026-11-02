<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\RolePermission;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $table = 'users';

    public function department()
    {
        return $this->belongsTo(Department::class, 'kode_unit_kerja', 'kode');
    }
    public function activityLogs() {
        return $this->hasMany(ActivityLog::class, 'id_user', 'id');
    }
    /**
     * Histori KGB yang dibuat user.
     */
    public function kgbHistories()
    {
        return $this->hasMany(KgbHistory::class, 'id_user', 'id');
    }
    public function loginDevices()
    {
        return $this->hasMany(LoginDevice::class);
    }
    public function document()
    {
        return $this->hasMany( EmployeeDocument::class, 'id_user', 'id' );
    }
    public function employee(){
        return $this->belongsTo(Employee::class, 'nip', 'nip');
    }
    /**
     * Memeriksa apakah pengguna memiliki permission tertentu.
     */
    public function hasPermission(string $kode): bool
    {
        // Super Admin memiliki seluruh hak akses.
        if ($this->role === 'super_admin') {
            return true;
        }

        return RolePermission::query()
            ->where('role', $this->role)
            ->whereHas('permission', function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->exists();
    }
}

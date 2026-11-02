<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];
    protected $table = 'notifications';
    protected $casts = [
        'tanggal_notifikasi' => 'date',
        'tanggal_referensi' => 'date',
        'sudah_dibaca' => 'boolean',
        'email_terkirim' => 'boolean',
        'whatsapp_terkirim' => 'boolean',
        'email_super_admin_terkirim' => 'boolean',
        'whatsapp_super_admin_terkirim' => 'boolean',
    ];
    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'id_user');
    // }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'nip', 'nip');
    }
    public function getIconAttribute(): array
    {
        $title = strtolower($this->judul);
        if (str_contains($title, 'pangkat')) {
            return [
                'icon' => 'fa-chart-line',
                'bg' => 'bg-orange-100',
                'text' => 'text-orange-600',
            ];
        }
        if (str_contains($title, 'kgb')) {
            return [
                'icon' => 'fa-wallet',
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-600',
            ];
        }
        if (str_contains($title, 'pensiun')) {
            return [
                'icon' => 'fa-user-clock',
                'bg' => 'bg-green-100',
                'text' => 'text-green-600',
            ];
        }
        return [
            'icon' => 'fa-bell',
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-600',
        ];
    }
}

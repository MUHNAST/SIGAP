<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class KgbHistory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
     protected function casts(): array
    {
        return [
            'tanggal_sk_lama' => 'date',
            'tanggal_sk_baru' => 'date',
            'tmt_kgb_lama' => 'date',
            'tmt_kgb_baru' => 'date',
            'tahun_masa_kerja_lama' => 'integer',
            'bulan_masa_kerja_lama' => 'integer',
            'tahun_masa_kerja_baru' => 'integer',
            'bulan_masa_kerja_baru' => 'integer',
            'gaji_pokok_lama' => 'integer',
            'gaji_pokok_baru' => 'integer',
        ];
    }
    /**
     * Pegawai pemilik histori KGB.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'nip');
    }
    /**
     * Pangkat/Golongan pada saat SK KGB diterbitkan.
     */
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'id_pangkat', 'id');
    }
    /**
     * User yang membuat histori.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;
    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'employees';
    protected $fillable = [
        'nip',
        'nik',
        'email',
        'no_hp',
        'kode_unit_kerja',
        'id_pangkat',
        'kode_pppk',
        'kode_jabatan',
        'nama',
        'tingkat_pendidikan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'alamat',
        'id_provinsi',
        'id_kota',
        'id_kecamatan',
        'id_kelurahan',
        'status_pegawai',
        'status_nikah',
        'tanggal_masuk',
        'tmt_pangkat_lama',
        'tmt_pangkat_baru',
        'tmt_kgb_lama',
        'tmt_kgb_baru',
        'tanggal_pensiun',
        'tmt_pensiun',
        'foto',
        'tahun_masa_kerja',
        'bulan_masa_kerja',
        'status',
    ];
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tmt_pangkat_lama' => 'date',
            'tmt_pangkat_baru' => 'date',
            'tmt_kgb_lama' => 'date',
            'tmt_kgb_baru' => 'date',
            'tanggal_pensiun' => 'date',
            'tmt_pensiun' => 'date',
            'tahun_masa_kerja' => 'integer',
            'bulan_masa_kerja' => 'integer',
        ];
    }
    public function user()
    {
        return $this->hasOne(User::class, 'nip', 'nip');
    }
    public function getRouteKeyName(): string
    {
        return 'nip';
    }
    public function position()
    {
        return $this->belongsTo(Position::class, 'kode_jabatan', 'kode');
    }
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'id_pangkat', 'id');
    }
     public function pppk()
    {
        return $this->belongsTo(Pppk::class, 'kode_pppk', 'kode');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class,'tingkat_pendidikan','kode');
    }
    public function department()
    {
        return $this->belongsTo(Department::class,'kode_unit_kerja','kode');
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'nip', 'nip');
    }
    // public function activityLogs()
    // {
    //     return $this->hasMany(ActivityLog::class, 'nip', 'nip');
    // }
    public function province()
    {
        return $this->belongsTo(Province::class, 'id_provinsi', 'id');
    }
    public function city()
    {
        return $this->belongsTo(City::class, 'id_kota', 'id');
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'id_kecamatan', 'id');
    }
    public function urbanVillage()
    {
        return $this->belongsTo(UrbanVillage::class,'id_kelurahan','id');
    }
    public function birthplace()
    {
        return $this->belongsTo(City::class, 'tempat_lahir', 'id');
    }
    /**
     * Riwayat KGB pegawai.
     */
    public function kgbHistories()
    {
        return $this->hasMany(KgbHistory::class, 'nip', 'nip');
    }
    /**
     * Riwayat KGB terakhir.
     */
    public function latestKgbHistory()
    {
        return $this->hasOne(KgbHistory::class, 'nip', 'nip')->latestOfMany('tanggal_sk_baru');
    }
    public function promotionHistories()
    {
        return $this->hasMany(PromotionHistory::class,'nip','nip');
    }
    public function dokumen(): HasMany
    {
        return $this->hasMany( EmployeeDocument::class, 'nip', 'nip' );
    }
}

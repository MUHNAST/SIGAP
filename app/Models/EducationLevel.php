<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EducationLevel extends Model
{

    protected $primaryKey = 'kode';
    protected $table = 'education_levels';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'kode',
        'nama',
        'pangkat_pns_minimum',
        'pangkat_pns_maksimum',
        'golongan_pppk_minimum',
        'golongan_pppk_maksimum',
        'deskripsi',
        'status',
    ];
    protected $casts = [
        'pangkat_pns_minimum' => 'integer',
        'pangkat_pns_maksimum' => 'integer',
        'golongan_pppk_minimum' => 'integer',
        'golongan_pppk_maksimum' => 'integer',
        'status' => 'boolean',
    ];
    public function employees()
    {
        return $this->hasMany(Employee::class, 'tingkat_pendidikan', 'kode');
    }
}

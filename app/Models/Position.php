<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Position extends Model
{
    /** @use HasFactory<\Database\Factories\PositionFactory> */
    use HasFactory;
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'positions';
    protected $fillable = [
        'kode',
        'nama',
        // 'position_type',
        // 'position_level',
        // 'retirement_rule_id',
        'deskripsi',
        'usia_pensiun',
    ];
    public function getRouteKeyName(): string
    {
        return 'kode';
    }
    public function employees()
    {
        return $this->hasMany( Employee::class, 'kode_jabatan', 'kode' );
    }
}

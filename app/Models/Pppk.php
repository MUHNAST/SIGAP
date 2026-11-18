<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Pppk extends Model
{
    use HasFactory;
    protected $table = 'pppk';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'kode',
        'nama',
        'urutan_golongan',
        'status'
    ];
    protected function casts(): array
    {
        return [
            'urutan_golongan' => 'integer',
        ];
    }

    public function employees()
    {
        return $this->hasMany(Employee::class,'kode_pppk','kode');
    }
    public function getRouteKeyName(): string
    {
        return 'kode';
    }
}

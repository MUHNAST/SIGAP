<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'departments';
    // protected $guarded = ['kode'];
     protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'kode';
    }
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'kode_unit_kerja', 'kode');
    }
    /**
     * Relasi ke user admin.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kode_unit_kerja', 'kode');
    }
}

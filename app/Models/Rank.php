<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Rank extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'ranks';
    protected $guarded = ['id'];
    public function employees()
    {
        return $this->hasMany(Employee::class,'id_pangkat', 'id');
    }
    public function salaries()
    {
        return $this->hasMany(Salary::class,'id_pangkat', 'id');
    }
    public function kgbHistories()
    {
        return $this->hasMany(KgbHistory::class, 'id_pangkat', 'id');
    }
    public function oldPromotionHistories()
    {
        return $this->hasMany(PromotionHistory::class, 'id_pangkat_lama', 'id');
    }
    public function newPromotionHistories()
    {
        return $this->hasMany(PromotionHistory::class, 'id_pangkat_baru', 'id');
    }
}

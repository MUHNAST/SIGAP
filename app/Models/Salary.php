<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{
    /** @use HasFactory<\Database\Factories\SalaryFactory> */
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'salaries';
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'id_pangkat', 'id');
    }

}

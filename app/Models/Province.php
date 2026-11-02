<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
     protected $table = 'provinces';

    protected $fillable = [
        'id',
        'nama',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'id_provinsi', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'id',
        'id_provinsi',
        'nama',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'id_provinsi', 'id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'id_kota', 'id');
    }
}

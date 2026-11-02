<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'districts';

    protected $fillable = [
        'id',
        'id_kota',
        'nama',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'id_kota', 'id');
    }

    public function urbanVillages(): HasMany
    {
        return $this->hasMany(UrbanVillage::class, 'id_kecamatan', 'id');
    }
}

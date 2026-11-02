<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrbanVillage extends Model
{
    protected $table = 'urban_villages';

    protected $fillable = [
        'id',
        'id_kecamatan',
        'nama',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'id_kecamatan', 'id');
    }
}

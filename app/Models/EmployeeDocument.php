<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';

    protected $fillable = [
        'nip',
        'id_user',
        'jenis_dokumen',
        'nama_file',
        'nama_file_asli',
        'keterangan',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo( Employee::class, 'nip', 'nip');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class, 'id_user', 'id');
    }

}

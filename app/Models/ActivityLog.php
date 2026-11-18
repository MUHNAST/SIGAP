<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityLogFactory> */
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'activity_logs';

    public function user() {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

}

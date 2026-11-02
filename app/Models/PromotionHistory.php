<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PromotionHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PromotionHistoryFactory> */
    use HasFactory;
    protected $table = 'promotion_histories';
    protected $fillable = [
        'nip',
        'id_pangkat_lama',
        'id_pangkat_baru',
        'tmt_pangkat',
        'keterangan',
    ];
    protected $casts = [
        'tmt_pangkat' => 'date',
    ];
    /**
     * Pegawai yang memiliki riwayat kenaikan pangkat.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'nip', 'nip');
    }
    /**
     * Pangkat sebelum kenaikan.
     */
    public function oldRank()
    {
        return $this->belongsTo(Rank::class, 'id_pangkat_lama', 'id');
    }
    /**
     * Pangkat setelah kenaikan.
     */
    public function newRank()
    {
        return $this->belongsTo(Rank::class, 'id_pangkat_baru', 'id');
    }
}

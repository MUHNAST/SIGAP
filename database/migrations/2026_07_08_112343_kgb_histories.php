<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('kgb_histories', function (Blueprint $table) {
            $table->id();
            // Pegawai
            $table->string('nip', 18);
            $table->foreign('nip')->references('nip')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            // Surat Keputusan KGB
            $table->string('nomor_sk_lama')->nullable();
            $table->string('nomor_sk_baru')->nullable();
            $table->date('tanggal_sk_lama')->nullable();
            $table->date('tanggal_sk_baru')->nullable();
            // Pejabat yang menetapkan SK
            $table->string('pejabat')->nullable();
            // TMT KGB
            $table->date('tmt_kgb_lama')->nullable();
            $table->date('tmt_kgb_baru')->nullable();
            // Masa kerja
            $table->unsignedTinyInteger('tahun_masa_kerja_lama')->default(0);
            $table->unsignedTinyInteger('bulan_masa_kerja_lama')->default(0);
            $table->unsignedTinyInteger('tahun_masa_kerja_baru')->default(0);
            $table->unsignedTinyInteger('bulan_masa_kerja_baru')->default(0);
            // Pangkat
            $table->foreignId('id_pangkat')->nullable()->constrained('ranks')->cascadeOnDelete();
            // Gaji pokok
            $table->unsignedInteger('gaji_pokok_lama')->default(0);
            $table->unsignedInteger('gaji_pokok_baru')->default(0);
            // Dasar hukum KGB
            $table->text('dasar_hukum_kgb')->nullable();
            // Pengguna yang membuat riwayat
            $table->foreignId('id_user')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('kgb_histories');
    }
};

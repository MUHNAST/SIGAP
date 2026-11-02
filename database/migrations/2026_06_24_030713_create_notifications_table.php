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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18);
            $table->foreign('nip')->references('nip')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('jenis', ['Pangkat','KGB','Pensiun','KGB Diproses']);
            $table->string('judul');
            $table->text('pesan');
            // Tanggal notifikasi dibuat/dikirim
            $table->date('tanggal_notifikasi');
            // Tanggal yang menjadi dasar penjadwalan notifikasi
            $table->date('tanggal_referensi');
            $table->boolean('sudah_dibaca')->default(false);
            $table->boolean('email_terkirim')->default(false);
            $table->boolean('whatsapp_terkirim')->default(false);
            $table->boolean('email_super_admin_terkirim')->default(false);
            $table->boolean('whatsapp_super_admin_terkirim')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['jenis','tanggal_referensi','nip','sudah_dibaca']);
            $table->unique(['nip','jenis','tanggal_referensi',],'notifications_nip_jenis_tanggal_referensi_unique');
        });
    }
    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

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
        Schema::create('promotion_histories', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18);
            $table->foreign('nip')->references('nip')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            // Pangkat sebelumnya
            $table->foreignId('id_pangkat_baru')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_pangkat_lama')->constrained('users')->cascadeOnDelete();
            // Pangkat setelah kenaikan
            $table->date('tmt_pangkat');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_histories');
    }
};

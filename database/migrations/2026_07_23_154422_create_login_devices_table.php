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
        Schema::create('login_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->string('device_fingerprint');
            $table->string('nama_perangkat')->nullable();
            $table->text('browser')->nullable();
            $table->string('platform')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('aktivitas_terakhir')->nullable();
            $table->timestamps();
            $table->index('id_user');
            $table->index('session_id');
            $table->index('aktivitas_terakhir');
            $table->unique(
                ['id_user', 'device_fingerprint'],
                'login_devices_user_fingerprint_unique'
            );
        });
    }
    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_devices');
    }
};

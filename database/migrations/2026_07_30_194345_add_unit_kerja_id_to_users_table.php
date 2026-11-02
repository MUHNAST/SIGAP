<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kode_unit_kerja')->nullable();
            $table->foreign('kode_unit_kerja')->references('kode')->on('departments')->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('email_2fa_enabled')->default(false)->after('email');
            $table->string('nip', 18)->nullable()->unique();
            $table->foreign('nip')->references('nip')->on('employees')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};

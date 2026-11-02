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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pangkat')->constrained('ranks')->cascadeOnDelete();
            $table->unsignedTinyInteger('tahun_masa_kerja');
            $table->unsignedInteger('gaji_pokok');
            $table->timestamps();
            // $table->softDeletes();
            $table->unique(['id_pangkat','tahun_masa_kerja']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};

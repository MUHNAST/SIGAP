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
        Schema::create('urban_villages', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('id_kecamatan', 7);
            $table->string('nama');
            $table->timestamps();
            $table->foreign('id_kecamatan')->references('id')->on('districts')->cascadeOnDelete();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urban_villages');
    }
};

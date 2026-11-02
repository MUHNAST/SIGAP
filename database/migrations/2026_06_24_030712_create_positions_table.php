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
        Schema::create('positions', function (Blueprint $table) {
            $table->string('kode', 20)->primary();
            $table->string('nama');
            // $table->enum('position_type', ['Manajerial', 'Nonmanajerial']);
            // $table->string('position_level');
            // $table->foreignId('retirement_rule_id')->constrained('retirement_rules')->cascadeOnDelete();
            $table->text('deskripsi')->nullable();
            $table->tinyInteger('usia_pensiun');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};

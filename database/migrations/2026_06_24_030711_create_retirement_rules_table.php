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
        // Schema::create('retirement_rules', function (Blueprint $table) {
        //     $table->id();
        //     $table->tinyInteger('retirement_age');
        //     $table->string('legal_basis');
        //     $table->string('article');
        //     $table->string('deskripsi');
        //     $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_rules');
    }
};

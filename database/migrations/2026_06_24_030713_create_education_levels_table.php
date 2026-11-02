<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_levels', function (Blueprint $table) {
            $table->string('kode', 14)->primary();
            $table->string('nama');
            // $table->unsignedTinyInteger('pangkat_minimum');
            // $table->unsignedTinyInteger('pangkat_maksimum');
            $table->unsignedTinyInteger('pangkat_pns_minimum');
            $table->unsignedTinyInteger('pangkat_pns_maksimum');
             $table->unsignedTinyInteger('golongan_pppk_minimum')->nullable();
            $table->unsignedTinyInteger('golongan_pppk_maksimum')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_levels');
    }
};

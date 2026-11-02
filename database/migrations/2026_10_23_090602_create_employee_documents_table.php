<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18);
            $table->foreign('nip')->references('nip')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis_dokumen', 50);
            $table->string('nama_file');
            $table->string('nama_file_asli');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index('jenis_dokumen');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

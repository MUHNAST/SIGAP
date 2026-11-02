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
        Schema::create('employees', function (Blueprint $table) {
            // $table->id();
            $table->string('nip', 18)->primary();
            $table->string('nik')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('no_hp')->unique()->nullable();
            // Pangkat saat ini
            $table->foreignId('id_pangkat')->nullable()->constrained('ranks')->nullOnDelete();
            // $table->foreign('id_pangkat')->references('kode')->on('ranks')->nullOnDelete();
            // $table->string('id_pangkat')->nullable();
            $table->string('kode_pppk')->nullable();
            $table->foreign('kode_pppk')->references('kode')->on('Pppk')->nullOnDelete()->cascadeOnUpdate();;
            $table->string('kode_unit_kerja')->nullable();
            $table->foreign('kode_unit_kerja')->references('kode')->on('departments')->nullOnDelete()->cascadeOnUpdate();;
            $table->string('kode_jabatan')->nullable();
            $table->foreign('kode_jabatan')->references('kode')->on('positions')->nullOnDelete()->cascadeOnUpdate();;
            $table->string('nama');
            // $table->enum('education_level', ['SD/Sederajat','SMP/Sederajat','SMA/SMK/MA/Sederajat','D1','D2','D3','D4','S1','S2','S3'])->nullable();
            $table->string('tingkat_pendidikan')->nullable();
            $table->foreign('tingkat_pendidikan')->references('kode')->on('education_levels')->nullOnDelete();
            $table->string('tempat_lahir', 4)->nullable();
            $table->foreign('tempat_lahir')->references('id')->on('cities')->nullOnDelete();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki','Perempuan'])->nullable();
            $table->enum('agama', ['Islam','Kristen Protestan','Kristen Katolik','Budha','Hindu','Konghucu'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('id_provinsi', 2)->nullable();
            $table->string('id_kota', 4)->nullable();
            $table->string('id_kecamatan', 7)->nullable();
            $table->string('id_kelurahan', 10)->nullable();
            $table->foreign('id_provinsi')->nullable()->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('id_kota')->nullable()->references('id')->on('cities')->nullOnDelete();
            $table->foreign('id_kecamatan')->nullable()->references('id')->on('districts')->nullOnDelete();
            $table->foreign('id_kelurahan')->nullable()->references('id')->on('urban_villages')->nullOnDelete();
            $table->enum('status_pegawai', ['PNS','PPPK'])->nullable();
            $table->enum('status_nikah', ['Belum Menikah','Menikah','Cerai Hidup','Cerai Mati'])->nullable();
            // Tanggal masuk kerja
            $table->date('tanggal_masuk')->nullable();
            // ==========================
            // DATA KEPEGAWAIAN
            // ==========================
            // TMT pangkat saat ini
            $table->date('tmt_pangkat_lama')->nullable();
            $table->date('tmt_pangkat_baru')->nullable();
            // TMT Kenaikan Gaji Berkala (KGB)
            $table->date('tmt_kgb_lama')->nullable();
            $table->date('tmt_kgb_baru')->nullable();
            // TMT pensiun
            $table->date('tanggal_pensiun')->nullable();
            // Tanggal pensiun efektif
            $table->date('tmt_pensiun')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedTinyInteger('tahun_masa_kerja')->default(0)->nullable();
            $table->unsignedTinyInteger('bulan_masa_kerja')->default(0)->nullable();
            $table->enum('status', ['Aktif', 'Nonaktif', 'Pensiun'])->nullable();
            $table->timestamps();
            // $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

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
            Schema::create('departments', function (Blueprint $table) {
                $table->string('kode')->primary();
                $table->string('nama')->unique();
                $table->text('deskripsi')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('departments');
        }
    };

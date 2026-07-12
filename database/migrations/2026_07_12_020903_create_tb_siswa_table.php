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
        Schema::create('tb_siswa', function (Blueprint $table) {
            $table->id('id_siswa');
            $table->string('nis', 16);
            $table->string('nama_siswa', 255);
            $table->foreignId('id_kelas')->constrained('tb_kelas', 'id_kelas')->restrictOnUpdate()->cascadeOnDelete();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('no_hp', 32);
            $table->integer('poin_pelanggaran')->default(0);
            $table->string('unique_code', 64)->unique();
            $table->string('rfid_code', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_siswa');
    }
};

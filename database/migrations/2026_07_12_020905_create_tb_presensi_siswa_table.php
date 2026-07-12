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
        Schema::create('tb_presensi_siswa', function (Blueprint $table) {
            $table->id('id_presensi');
            $table->foreignId('id_siswa')->constrained('tb_siswa', 'id_siswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('id_kelas')->nullable()->constrained('tb_kelas', 'id_kelas')->onUpdate('set null')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->foreignId('id_kehadiran')->constrained('tb_kehadiran', 'id_kehadiran')->restrictOnUpdate()->cascadeOnDelete();
            $table->integer('menit_keterlambatan')->default(0);
            $table->string('keterangan', 255);
            $table->unique(['id_siswa', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_presensi_siswa');
    }
};

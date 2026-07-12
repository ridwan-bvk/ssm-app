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
        Schema::create('tb_presensi_guru', function (Blueprint $table) {
            $table->id('id_presensi');
            $table->foreignId('id_guru')->nullable()->constrained('tb_guru', 'id_guru')->onUpdate('set null')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->foreignId('id_kehadiran')->constrained('tb_kehadiran', 'id_kehadiran')->restrictOnUpdate()->cascadeOnDelete();
            $table->string('keterangan', 255);
            $table->unique(['id_guru', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_presensi_guru');
    }
};

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
        Schema::create('tb_perizinan', function (Blueprint $table) {
            $table->id('id_perizinan');
            $table->foreignId('id_siswa')->nullable()->constrained('tb_siswa', 'id_siswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('id_guru')->nullable()->constrained('tb_guru', 'id_guru')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('tipe_izin', ['Sakit', 'Izin'])->default('Sakit');
            $table->text('alasan')->nullable();
            $table->string('bukti', 255)->nullable();
            $table->enum('status', ['Pending', 'Disetujui', 'Ditolak'])->default('Pending');
            $table->foreignId('id_petugas')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_perizinan');
    }
};

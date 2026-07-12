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
        Schema::create('tb_kehadiran', function (Blueprint $table) {
            $table->id('id_kehadiran');
            $table->enum('kehadiran', ['Hadir', 'Sakit', 'Izin', 'Tanpa keterangan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_kehadiran');
    }
};

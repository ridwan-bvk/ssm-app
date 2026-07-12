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
        Schema::create('tb_kelas', function (Blueprint $table) {
            $table->id('id_kelas');
            $table->string('tingkat', 10);
            $table->foreignId('id_jurusan')->constrained('tb_jurusan', 'id')->cascadeOnUpdate();
            $table->string('index_kelas', 5);
            $table->foreignId('id_wali_kelas')->nullable()->constrained('tb_guru', 'id_guru')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_kelas');
    }
};

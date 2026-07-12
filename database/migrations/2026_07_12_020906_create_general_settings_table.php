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
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo', 225)->nullable();
            $table->string('school_name', 225)->nullable()->default('SMK 1 Indonesia');
            $table->string('school_year', 225)->nullable()->default('2024/2025');
            $table->time('jam_masuk_limit')->nullable()->default('07:00:00');
            $table->time('jam_pulang_standard')->nullable()->default('14:00:00');
            $table->string('hari_kerja', 30)->nullable()->default('1,2,3,4,5');
            $table->string('copyright', 225)->nullable()->default('© 2025 All rights reserved.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};

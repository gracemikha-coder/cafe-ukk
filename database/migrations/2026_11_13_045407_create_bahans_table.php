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
        Schema::create('bahans', function (Blueprint $table){
            $table->id();
            $table->string('nama');
            $table->string('satuan_dasar');
            $table->decimal('stok', 12, 2)->default(0);
            $table->decimal('batas_minimum', 12, 2)->default(0);
            $table->decimal('harga_per_satuan', 12, 2)->default(0);
            $table->date('harga_diperbarui_pada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legislatures', function (Blueprint $table) {
            $table->id();
            $table->string('sigla')->unique();
            $table->unsignedSmallInteger('number')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('initiatives_url')->nullable();
            $table->text('base_info_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legislatures');
    }
};

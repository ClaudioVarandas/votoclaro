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
        Schema::create('votes', function (Blueprint $table) {
            $table->string('id')->primary(); // vote id from JSON
            $table->string('initiative_id');
            $table->date('date')->nullable();
            $table->string('result')->nullable();
            $table->boolean('unanimous')->default(false);
            $table->timestamps();

            $table->foreign('initiative_id')
                ->references('id')
                ->on('initiatives')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};

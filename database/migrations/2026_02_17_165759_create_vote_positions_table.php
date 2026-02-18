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
        Schema::create('vote_positions', function (Blueprint $table) {
            $table->id();
            $table->string('vote_id');
            $table->string('party');
            $table->string('position'); // favor, contra, abstencao
            $table->timestamps();

            $table->foreign('vote_id')
                ->references('id')
                ->on('votes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_positions');
    }
};

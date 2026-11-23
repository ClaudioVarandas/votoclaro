<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_vote_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_vote_id')->constrained()->cascadeOnDelete();
            $table->string('party')->nullable();
            $table->string('deputy')->nullable();
            $table->string('position');
            $table->timestamps();

            $table->index(['party', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_vote_positions');
    }
};

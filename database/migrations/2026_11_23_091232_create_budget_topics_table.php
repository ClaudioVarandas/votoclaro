<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('label', 120);
            $table->unsignedInteger('proposals_count');
            $table->unsignedInteger('parties_count');
            $table->unsignedInteger('voted_count');
            $table->decimal('division', 5, 4)->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_topics');
    }
};

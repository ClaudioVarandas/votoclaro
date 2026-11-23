<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_articles', function (Blueprint $table) {
            // The Parliament's own id of the article, which its amendments point to.
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('number', 40);
            $table->string('number_slug', 40);
            $table->string('slug', 100);
            $table->string('title', 500)->nullable();
            $table->mediumText('text')->nullable();
            $table->string('status');
            $table->string('status_label');
            $table->string('law_title', 500)->nullable();
            $table->string('law_subtitle', 500)->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'slug']);
            $table->index(['budget_id', 'number_slug']);
            $table->index(['budget_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_articles');
    }
};

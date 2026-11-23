<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_article_budget_proposal', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_article_id');
            $table->unsignedBigInteger('budget_proposal_id');

            $table->primary(['budget_article_id', 'budget_proposal_id']);
            $table->index('budget_proposal_id');
            $table->foreign('budget_article_id')->references('id')->on('budget_articles')->cascadeOnDelete();
            $table->foreign('budget_proposal_id')->references('id')->on('budget_proposals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_article_budget_proposal');
    }
};

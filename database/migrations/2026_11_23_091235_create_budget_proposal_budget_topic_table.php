<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_proposal_budget_topic', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_proposal_id');
            $table->foreignId('budget_topic_id')->constrained()->cascadeOnDelete();

            $table->primary(['budget_topic_id', 'budget_proposal_id']);
            $table->foreign('budget_proposal_id')->references('id')->on('budget_proposals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_proposal_budget_topic');
    }
};

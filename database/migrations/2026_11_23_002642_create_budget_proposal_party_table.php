<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_proposal_party', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_proposal_id');
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['budget_proposal_id', 'party_id']);
            $table->foreign('budget_proposal_id')->references('id')->on('budget_proposals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_proposal_party');
    }
};

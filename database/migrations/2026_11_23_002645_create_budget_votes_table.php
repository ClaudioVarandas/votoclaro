<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_proposal_id');
            $table->unsignedSmallInteger('sequence');
            $table->string('stage');
            $table->date('date')->nullable();
            $table->string('result')->nullable();
            $table->string('result_label');
            $table->text('subject')->nullable();
            $table->timestamps();

            $table->unique(['budget_proposal_id', 'sequence']);
            $table->foreign('budget_proposal_id')->references('id')->on('budget_proposals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_votes');
    }
};

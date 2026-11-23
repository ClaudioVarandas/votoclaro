<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A vote is on an amendment or on an article of the Government's bill, never both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_proposal_id')->nullable()->change();
            $table->unsignedBigInteger('budget_article_id')->nullable()->after('budget_proposal_id');

            $table->unique(['budget_article_id', 'sequence']);
            $table->index(['budget_article_id', 'is_final']);
            $table->foreign('budget_article_id')->references('id')->on('budget_articles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_votes', function (Blueprint $table) {
            $table->dropForeign(['budget_article_id']);
            $table->dropUnique(['budget_article_id', 'sequence']);
            $table->dropIndex(['budget_article_id', 'is_final']);
            $table->dropColumn('budget_article_id');
        });

        Schema::table('budget_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_proposal_id')->nullable(false)->change();
        });
    }
};

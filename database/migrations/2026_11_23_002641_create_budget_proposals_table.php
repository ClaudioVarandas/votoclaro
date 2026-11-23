<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_proposals', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20);
            $table->string('slug');
            $table->text('title');
            $table->string('type')->nullable();
            $table->string('scope')->nullable();
            $table->string('status');
            $table->string('status_label');
            $table->string('proposer_label')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->text('pdf_url')->nullable();
            $table->longText('text')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'number']);
            $table->unique(['budget_id', 'slug']);
            $table->index(['budget_id', 'status']);

            // SQLite, which runs most of the tests, has no full-text index.
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'text']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_proposals');
    }
};

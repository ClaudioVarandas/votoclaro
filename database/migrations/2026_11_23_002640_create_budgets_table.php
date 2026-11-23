<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislature_id')->constrained();
            $table->unsignedSmallInteger('year');
            $table->string('kind', 2)->default('Or');
            $table->string('initiative_id')->nullable();
            $table->text('proposals_url')->nullable();
            $table->text('budget_url')->nullable();
            $table->json('party_order')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'kind']);
            $table->foreign('initiative_id')->references('id')->on('initiatives')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

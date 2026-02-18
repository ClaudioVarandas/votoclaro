<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('initiatives', function (Blueprint $table) {
            $table->string('id')->primary(); // IniId
            $table->text('title');
            $table->string('status'); // approved, rejected, in_progress
            $table->date('entry_date')->nullable();
            $table->date('final_vote_date')->nullable();
            $table->integer('days_to_approval')->nullable();
            $table->string('author_category'); // government | parliamentary_group | deputies | mixed | other
            $table->string('author_party')->nullable(); // e.g. PSD, L, PS
            $table->string('author_label')->nullable(); // "Governo", "Grupo Parlamentar L", etc.
            $table->string('initiative_type'); // R, P, etc.
            $table->string('initiative_type_label'); // Projeto de Resolução
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives');
    }
};

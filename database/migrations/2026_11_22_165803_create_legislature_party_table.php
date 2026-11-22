<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The parties imported so far all came from the XVII legislature's base information.
     */
    public function up(): void
    {
        Schema::create('legislature_party', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['legislature_id', 'party_id']);
        });

        $legislatureId = DB::table('legislatures')->where('sigla', 'XVII')->value('id');

        if ($legislatureId) {
            DB::table('legislature_party')->insert(
                DB::table('parties')->pluck('id')->map(fn (int $partyId): array => [
                    'legislature_id' => $legislatureId,
                    'party_id' => $partyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legislature_party');
    }
};

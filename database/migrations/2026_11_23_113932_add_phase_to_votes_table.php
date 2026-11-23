<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The stage of the legislative process a vote belongs to, as the Parliament names it ("Votação na
 * generalidade", "Votação final global"…). The next initiatives import fills it in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->string('phase')->nullable()->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropColumn('phase');
        });
    }
};

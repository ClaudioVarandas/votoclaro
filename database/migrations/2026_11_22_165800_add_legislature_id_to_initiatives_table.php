<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Everything imported before legislatures existed belongs to the XVII legislature, whose
     * source URLs were then the only ones, in config/services.php.
     */
    public function up(): void
    {
        Schema::table('initiatives', function (Blueprint $table) {
            $table->foreignId('legislature_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (DB::table('initiatives')->exists()) {
            $legislatureId = DB::table('legislatures')->insertGetId([
                'sigla' => 'XVII',
                'number' => 17,
                'start_date' => '2025-06-03',
                'initiatives_url' => config('services.parliament.json_url'),
                'base_info_url' => config('services.parliament.base_info_url'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('initiatives')->update(['legislature_id' => $legislatureId]);
        }

        Schema::table('initiatives', function (Blueprint $table) {
            $table->foreignId('legislature_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('initiatives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legislature_id');
        });
    }
};

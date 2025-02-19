<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->date('completed_at')->nullable()->after('priority');
        });

        DB::table('todos')
            ->where('is_completed', true)
            ->update([
                'completed_at' => DB::raw('DATE(created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};

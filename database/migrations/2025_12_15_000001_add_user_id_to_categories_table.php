<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->dropUnique('categories_name_unique');
            $table->unique(['user_id', 'name']);
        });

        // Backfill user_id using the earliest transaction owner for each category.
        DB::table('categories')
            ->whereNull('user_id')
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $ownerId = DB::table('transactions')
                        ->where('category_id', $row->id)
                        ->orderBy('id')
                        ->value('user_id');

                    if ($ownerId) {
                        DB::table('categories')
                            ->where('id', $row->id)
                            ->update(['user_id' => $ownerId]);
                    }
                }
            });

        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');
        if ($fallbackUserId) {
            DB::table('categories')
                ->whereNull('user_id')
                ->where('id', '!=', 'uncategorized')
                ->update(['user_id' => $fallbackUserId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
            $table->unique('name');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

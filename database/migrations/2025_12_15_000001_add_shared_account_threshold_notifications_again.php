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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'notify_shared_near')) {
                $table->boolean('notify_shared_near')->default(false);
            }
            if (!Schema::hasColumn('users', 'notify_shared_over')) {
                $table->boolean('notify_shared_over')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'notify_shared_near')) {
                $table->dropColumn('notify_shared_near');
            }
            if (Schema::hasColumn('users', 'notify_shared_over')) {
                $table->dropColumn('notify_shared_over');
            }
        });
    }
};

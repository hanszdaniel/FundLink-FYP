<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_accounts', function (Blueprint $table) {
            $table->boolean('is_achieved')->default(false)->after('current_total');
            $table->timestamp('achieved_at')->nullable()->after('is_achieved');
        });
    }

    public function down(): void
    {
        Schema::table('shared_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_achieved', 'achieved_at']);
        });
    }
};

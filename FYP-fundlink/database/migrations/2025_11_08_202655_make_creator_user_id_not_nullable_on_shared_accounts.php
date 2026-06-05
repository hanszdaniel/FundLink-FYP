<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, update any existing NULL values to a valid user ID (your test ID)
        \DB::table('shared_accounts')->whereNull('creator_user_id')->update(['creator_user_id' => 1]); // Assuming 1 is your test ID

        // Then, change the column structure
        Schema::table('shared_accounts', function (Blueprint $table) {
            // Change the column to be NOT NULL
            $table->foreignId('creator_user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Revert the column back to nullable if necessary
        Schema::table('shared_accounts', function (Blueprint $table) {
            $table->foreignId('creator_user_id')->nullable()->change();
        });
    }
};
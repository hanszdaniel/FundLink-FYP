<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_accounts', function (Blueprint $table) {
            $table->id(); // Primary key (UNSIGNED BIGINT)

            // Essential Fields
            $table->string('name')->unique(); // E.g., "Monthly Rent", "Holiday Fund"
            
            // Optional but recommended: Tracks the user who created/owns the account
            $table->foreignId('creator_user_id')->constrained('users'); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_accounts');
    }
};

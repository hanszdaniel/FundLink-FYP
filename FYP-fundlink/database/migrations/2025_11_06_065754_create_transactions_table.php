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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // 1. Core Transaction Data
            $table->string('description');
            $table->decimal('amount', 10, 2); // Stores the monetary value
            $table->date('date');

            // 2. Foreign Keys (Relationships)
            
            /// The user who created this transaction (Standard foreign key)
$table->foreignId('user_id')->constrained(); 

// 🎯 FIX: Manually define category_id as a string (VARCHAR) to match categories.id
$table->string('category_id', 50); 
$table->foreign('category_id')->references('id')->on('categories'); 

// The shared account constraint
$table->foreignId('shared_account_id')->nullable()->constrained('shared_accounts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

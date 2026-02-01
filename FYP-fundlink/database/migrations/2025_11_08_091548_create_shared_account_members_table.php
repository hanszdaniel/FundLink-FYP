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
        Schema::create('shared_account_members', function (Blueprint $table) {
        // Remove the default $table->id() if it was there, as pivot tables don't need it.

        $table->foreignId('shared_account_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        // Define the composite primary key for unique membership
        $table->primary(['shared_account_id', 'user_id']); 
        
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_account_members');
    }
};

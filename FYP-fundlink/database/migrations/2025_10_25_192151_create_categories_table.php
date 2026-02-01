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
        // We use Schema::dropIfExists first to ensure a clean run in case of rollback issues
        Schema::dropIfExists('categories'); 
        
        Schema::create('categories', function (Blueprint $table) {
            // Note: Since the controller creates a unique slug-based ID, 
            // we will use string(50) for 'id' and make it the primary key.
            // If the controller was using auto-increment IDs, we would use $table->id();
            $table->string('id', 50)->primary(); 

            // Fields required by the CategoryController's store method
            $table->string('name', 255)->unique();
            $table->string('icon', 50);
            $table->decimal('budget', 10, 2); // Budget value
            $table->text('description')->nullable(); // Optional description
            $table->decimal('spent', 10, 2)->default(0.00); // Spent initialized to 0.00
            $table->integer('percent')->default(0); // Percentage initialized to 0
            $table->string('color', 7); // To store the hex color code (#RRGGBB)
            
            // Standard timestamps (created_at and updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
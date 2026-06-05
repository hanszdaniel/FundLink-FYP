<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_accounts', function (Blueprint $table) {
            // Add the columns that the current controller relies on
            $table->text('description')->nullable()->after('name'); 
            $table->enum('type', ['Expense', 'Savings'])->default('Expense')->after('description');
            $table->decimal('target_amount', 10, 2)->default(0)->after('type'); 
            $table->decimal('current_total', 10, 2)->default(0)->after('target_amount');
            
            // If the 'creator_user_id' was also missing, add it here:
            // $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('shared_accounts', function (Blueprint $table) {
            // Remove the columns if the migration is rolled back
            $table->dropColumn(['description', 'type', 'target_amount', 'current_total']);
        });
    }
};
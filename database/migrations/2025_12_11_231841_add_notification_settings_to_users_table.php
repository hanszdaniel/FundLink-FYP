<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('notify_budget_near')->default(true);
        $table->boolean('notify_budget_over')->default(true);
        $table->boolean('notify_category_near')->default(false);
        $table->boolean('notify_category_over')->default(false);
        $table->boolean('notify_shared_join')->default(true);
        $table->string('summary_frequency')->default('monthly');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};

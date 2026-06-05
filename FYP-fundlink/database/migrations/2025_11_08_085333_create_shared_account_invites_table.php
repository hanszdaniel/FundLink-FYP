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
        Schema::create('shared_account_invites', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shared_account_id')->constrained()->onDelete('cascade');
        $table->string('recipient_contact'); // Phone number, Telegram ID, or Email
        $table->enum('contact_type', ['whatsapp', 'telegram', 'email']);
        $table->string('auth_code', 6); // The 6-digit code for verification
        $table->string('token', 60)->unique()->nullable(); // Unique link token (optional, but good practice)
        $table->timestamp('expires_at');
        $table->enum('status', ['pending', 'verified', 'expired'])->default('pending');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_account_invites');
    }
};

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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('admin_name');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('target_user_name')->nullable();
            $table->enum('action', ['assigned', 'removed', 'access_denied', 'failed_login', 'locked_out', '2fa_required', 'password_reset', 'session_expired']);
            $table->string('role');
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            // Performance indexes
            $table->index(['admin_id', 'timestamp']);
            $table->index(['target_user_id', 'timestamp']);
            $table->index('action');
            $table->index('role');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

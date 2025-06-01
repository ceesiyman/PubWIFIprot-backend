<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('device_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('wifi_network_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('wifi_changes_count')->default(0);
            $table->integer('dns_requests_count')->default(0);
            $table->integer('blocked_domains_count')->default(0);
            $table->json('session_summary')->nullable(); // Store additional session metrics
            $table->timestamps();

            // Index for faster lookups and analytics
            $table->index(['user_id', 'started_at']);
            $table->index(['device_id', 'started_at']);
            $table->index(['wifi_network_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
}; 
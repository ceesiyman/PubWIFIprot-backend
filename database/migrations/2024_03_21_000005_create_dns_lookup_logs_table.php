<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_lookup_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('device_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('wifi_network_id')->nullable()->constrained()->onDelete('set null');
            $table->string('domain');
            $table->string('ip_address')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->json('response_data')->nullable(); // Store additional DNS response data
            $table->timestamps();

            // Index for faster lookups and analytics
            $table->index(['user_id', 'created_at']);
            $table->index(['domain', 'created_at']);
            $table->index(['wifi_network_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_lookup_logs');
    }
}; 
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('malicious_domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain')->unique();
            $table->enum('threat_type', ['malware', 'phishing', 'scam', 'suspicious', 'other']);
            $table->text('description')->nullable();
            $table->string('source')->nullable(); // Where this domain was reported/imported from
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Index for faster lookups
            $table->index(['domain', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('malicious_domains');
    }
}; 
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_networks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ssid');
            $table->string('bssid')->unique(); // MAC address of the access point
            $table->string('encryption_type')->nullable(); // WPA2, WPA3, etc.
            $table->string('channel')->nullable();
            $table->integer('signal_strength')->nullable(); // in dBm
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->integer('report_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index for faster lookups
            $table->index(['ssid', 'bssid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_networks');
    }
}; 
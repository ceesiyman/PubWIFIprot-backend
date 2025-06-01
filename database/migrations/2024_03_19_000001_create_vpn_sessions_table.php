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
        Schema::create('vpn_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('status')->default('active');
            $table->string('client_ip');
            $table->string('server_address');
            $table->integer('server_port');
            $table->string('client_public_key');
            $table->text('client_private_key');
            $table->bigInteger('bytes_sent')->default(0);
            $table->bigInteger('bytes_received')->default(0);
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vpn_sessions');
    }
}; 
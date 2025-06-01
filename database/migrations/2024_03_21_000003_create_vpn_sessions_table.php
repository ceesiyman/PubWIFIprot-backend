<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vpn_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('encryption_key');
            $table->string('server_address');
            $table->integer('server_port');
            $table->string('client_ip')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->bigInteger('bytes_sent')->default(0);
            $table->bigInteger('bytes_received')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vpn_sessions');
    }
}; 
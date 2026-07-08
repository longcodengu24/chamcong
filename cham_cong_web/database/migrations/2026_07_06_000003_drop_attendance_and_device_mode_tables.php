<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('device_mode');
    }

    public function down(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_uid');
            $table->string('name')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->string('date')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('device_mode', function (Blueprint $table) {
            $table->id();
            $table->boolean('register_mode')->default(false);
            $table->string('pending_uid')->nullable();
            $table->timestamp('pending_at')->nullable();
        });
    }
};

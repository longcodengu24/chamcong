<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_mode', function (Blueprint $table) {
            $table->id();
            $table->boolean('register_mode')->default(false);
            $table->string('pending_uid')->nullable();
            $table->timestamp('pending_at')->nullable();
        });

        // Hàng duy nhất mà ESP32 và web cùng đọc/ghi để phối hợp chế độ đăng ký thẻ
        DB::table('device_mode')->insert([
            'id'             => 1,
            'register_mode'  => false,
            'pending_uid'    => null,
            'pending_at'     => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('device_mode');
    }
};

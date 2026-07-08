<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employees');
    }

    public function down(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('rfid_uid')->primary();
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
};

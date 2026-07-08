<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at', 'department']);
            $table->unsignedInteger('price')->default(25000)->after('rfid_uid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->string('email')->nullable()->unique()->after('username');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('department')->nullable()->after('rfid_uid');
        });
    }
};

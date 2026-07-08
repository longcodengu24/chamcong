<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('department')->nullable()->after('rfid_uid');
            $table->string('phone')->nullable()->after('department');
        });

        // Email không còn bắt buộc vì đăng nhập chuyển sang dùng username
        DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'department', 'phone']);
        });
    }
};

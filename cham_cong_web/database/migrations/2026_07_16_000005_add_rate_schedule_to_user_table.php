<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            // Mảng JSON các khung giờ tùy chỉnh: [{"from":"22:00","to":"06:00","rate":30000}, ...]
            // Giờ nào không nằm trong khung nào thì dùng hourly_rate làm giá mặc định.
            $table->jsonb('rate_schedule')->nullable()->after('hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('rate_schedule');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cho phép history lưu luôn cả các lượt quẹt thẻ CHƯA gán cho nhân viên nào
     * (user_id = null, rfid_uid = UID vừa quét). Trang "Thêm nhân viên" dùng chính
     * các dòng này để lấy UID khi bấm "Quét thẻ", không cần thêm bảng riêng.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE history ALTER COLUMN user_id DROP NOT NULL');

        Schema::table('history', function (Blueprint $table) {
            $table->string('rfid_uid')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('history', function (Blueprint $table) {
            $table->dropColumn('rfid_uid');
        });

        DB::statement('ALTER TABLE history ALTER COLUMN user_id SET NOT NULL');
    }
};

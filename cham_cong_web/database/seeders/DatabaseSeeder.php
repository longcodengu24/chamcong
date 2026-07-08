<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tài khoản Admin mặc định
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Quản trị viên',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        // Tài khoản User mẫu
        User::firstOrCreate(
            ['username' => 'nhanvien'],
            [
                'name'     => 'Nguyễn Văn A',
                'password' => Hash::make('user123'),
                'role'     => 'user',
                'rfid_uid' => null,
            ]
        );
    }
}

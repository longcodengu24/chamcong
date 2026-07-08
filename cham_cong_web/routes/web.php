<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use Illuminate\Support\Facades\Route;

// Trang chủ → redirect về login
Route::get('/', fn() => redirect()->route('login'));

// ---- Auth ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---- Admin ----
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Nhân viên (tài khoản đăng nhập + thẻ RFID)
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::patch('/employees/{employee}/price', [EmployeeController::class, 'updatePrice'])->name('employees.price');

    // Quẹt thẻ RFID trực tiếp khi thêm nhân viên
    Route::get('/employees/scan/start', [EmployeeController::class, 'startCardScan'])->name('employees.scan.start');
    Route::get('/employees/scan/poll', [EmployeeController::class, 'pollCardScan'])->name('employees.scan.poll');

    // Chấm công
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::delete('/attendance', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::get('/attendance/export', [AttendanceController::class, 'exportCsv'])->name('attendance.export');

    // Bảng lương
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/{employee}', [PayrollController::class, 'show'])->name('payroll.show');
});

// ---- User ----
Route::prefix('user')->name('user.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
});

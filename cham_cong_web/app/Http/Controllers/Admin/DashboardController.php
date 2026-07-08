<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $todayStart = now()->setTimezone('Asia/Ho_Chi_Minh')->startOfDay();
        $todayEnd   = now()->setTimezone('Asia/Ho_Chi_Minh')->endOfDay();

        $stats = [
            'today_count'     => History::whereNotNull('user_id')->whereBetween('check_in', [$todayStart, $todayEnd])->count(),
            'total_employees' => User::whereNotNull('rfid_uid')->count(),
            'today_date'      => $todayStart->format('Y-m-d'),
        ];

        $recentHistory = History::with('user')
            ->whereNotNull('user_id')
            ->whereBetween('check_in', [$todayStart, $todayEnd])
            ->orderByDesc('check_in')
            ->get()
            ->map(fn (History $row) => [
                'name'      => $row->user->name ?? 'Đã xoá',
                'check_in'  => Carbon::parse($row->check_in)->format('H:i:s'),
                'check_out' => $row->check_out ? Carbon::parse($row->check_out)->format('H:i:s') : null,
            ])
            ->all();

        return view('admin.dashboard', compact('stats', 'recentHistory'));
    }
}

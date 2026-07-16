<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\User;
use Illuminate\Support\Facades\Date;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmployees = User::where('role', 'user')->count();
        $todayCheckIns = Price::whereDate('check_in', Date::today())->count();
        $openShifts = Price::whereNotNull('check_in')->whereNull('check_out')->count();

        return view('admin.dashboard', compact('totalEmployees', 'todayCheckIns', 'openShifts'));
    }
}

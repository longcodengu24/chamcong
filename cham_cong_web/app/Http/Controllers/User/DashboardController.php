<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $records = $user->prices()
            ->whereNotNull('check_in')
            ->orderByDesc('check_in')
            ->paginate(20);

        $totalThisMonth = $user->prices()
            ->whereYear('check_in', Date::now()->year)
            ->whereMonth('check_in', Date::now()->month)
            ->sum('amount');

        $totalAllTime = $user->prices()->sum('amount');

        return view('user.dashboard', compact('records', 'totalThisMonth', 'totalAllTime'));
    }
}

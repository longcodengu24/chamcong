<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employees = User::orderBy('name')->get();

        $records = Price::with('user')
            ->whereNotNull('user_id')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('user_id', $request->query('employee_id')))
            ->when($request->filled('month'), function ($q) use ($request) {
                [$year, $month] = explode('-', $request->query('month'));
                $q->whereYear('check_in', $year)->whereMonth('check_in', $month);
            })
            ->orderByDesc('check_in')
            ->paginate(30)
            ->withQueryString();

        return view('admin.attendance.index', compact('records', 'employees'));
    }
}

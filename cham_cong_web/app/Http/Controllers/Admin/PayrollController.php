<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\User;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m'));

        $employees = User::whereNotNull('rfid_uid')->orderBy('name')->get();

        $rows = $employees->map(function (User $employee) use ($month) {
            $records = $this->monthRecords($employee->id, $month);
            $summary = PayrollCalculator::summary($records, $employee->price, $employee->nightRate());

            return [
                'employee'    => $employee,
                'days'        => count($summary['sessions']),
                'total_hours' => $summary['total_hours'],
                'total_pay'   => $summary['total_pay'],
            ];
        });

        return view('admin.payroll.index', compact('rows', 'month'));
    }

    public function show(Request $request, User $employee)
    {
        $month = $request->get('month', now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m'));

        $records = $this->monthRecords($employee->id, $month);
        $summary = PayrollCalculator::summary($records, $employee->price, $employee->nightRate());

        return view('admin.payroll.show', compact('employee', 'month', 'summary'));
    }

    private function monthRecords(int $userId, string $month)
    {
        return History::where('user_id', $userId)
            ->whereBetween('check_in', [
                Carbon::createFromFormat('Y-m', $month)->startOfMonth(),
                Carbon::createFromFormat('Y-m', $month)->endOfMonth(),
            ])
            ->orderBy('check_in')
            ->get();
    }
}

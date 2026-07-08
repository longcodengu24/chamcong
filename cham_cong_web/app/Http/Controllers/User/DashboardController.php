<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $rfidUid = $user->rfid_uid;
        $month   = $request->get('month', now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m'));

        $sessions       = [];
        $payroll        = null;
        $isCheckedIn    = false;

        if ($rfidUid) {
            $isCheckedIn = History::where('user_id', $user->id)->whereNull('check_out')->exists();

            $records = History::where('user_id', $user->id)
                ->whereBetween('check_in', [
                    Carbon::createFromFormat('Y-m', $month)->startOfMonth(),
                    Carbon::createFromFormat('Y-m', $month)->endOfMonth(),
                ])
                ->orderByDesc('check_in')
                ->get();

            $payroll  = PayrollCalculator::summary($records, $user->price, $user->nightRate());
            $sessions = $payroll['sessions'];
        }

        return view('user.dashboard', compact('sessions', 'month', 'isCheckedIn', 'rfidUid', 'payroll'));
    }
}

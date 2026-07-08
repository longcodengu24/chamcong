<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date  = $request->get('date', now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d'));
        $month = $request->get('month');

        $records = $this->queryRecords($month ? null : $date, $month)->map(function (History $row) {
            $employee = $row->user;
            $calc = PayrollCalculator::sessionPay(
                Carbon::parse($row->check_in),
                $row->check_out ? Carbon::parse($row->check_out) : null,
                $employee?->price ?? 0,
                $employee?->nightRate() ?? 0
            );

            return [
                'id'        => $row->id,
                'name'      => $employee->name ?? 'Đã xoá',
                'check_in'  => Carbon::parse($row->check_in)->format('d/m/Y H:i:s'),
                'check_out' => $row->check_out ? Carbon::parse($row->check_out)->format('d/m/Y H:i:s') : null,
                'hours'     => $calc['hours'],
                'pay'       => $calc['pay'],
            ];
        })->all();

        return view('admin.attendance.index', compact('records', 'date', 'month'));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        History::where('id', $data['id'])->delete();

        return redirect()->back()->with('success', 'Đã xoá bản ghi chấm công.');
    }

    public function exportCsv(Request $request)
    {
        $date  = $request->get('date');
        $month = $request->get('month');

        if ($month) {
            $records  = $this->queryRecords(null, $month)->all();
            $filename = "cham_cong_{$month}.csv";
        } else {
            $d        = $date ?? now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
            $records  = $this->queryRecords($d, null)->all();
            $filename = "cham_cong_{$d}.csv";
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, ['Giờ vào', 'Giờ ra', 'Họ tên', 'Số giờ', 'Tiền']);
            foreach ($records as $row) {
                $employee = $row->user;
                $calc = PayrollCalculator::sessionPay(
                    Carbon::parse($row->check_in),
                    $row->check_out ? Carbon::parse($row->check_out) : null,
                    $employee?->price ?? 0,
                    $employee?->nightRate() ?? 0
                );

                fputcsv($out, [
                    Carbon::parse($row->check_in)->format('d/m/Y H:i:s'),
                    $row->check_out ? Carbon::parse($row->check_out)->format('d/m/Y H:i:s') : '',
                    $employee->name ?? 'Đã xoá',
                    $calc['hours'],
                    $calc['pay'],
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function queryRecords(?string $date, ?string $month)
    {
        return History::query()
            ->with('user')
            ->whereNotNull('user_id')
            ->when($date, fn ($q) => $q->whereBetween('check_in', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ]))
            ->when($month, fn ($q) => $q->whereBetween('check_in', [
                Carbon::createFromFormat('Y-m', $month)->startOfMonth(),
                Carbon::createFromFormat('Y-m', $month)->endOfMonth(),
            ]))
            ->orderByDesc('check_in')
            ->get();
    }
}

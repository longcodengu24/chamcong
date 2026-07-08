<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculator
{
    /**
     * Tính số giờ + tiền của 1 ca làm (check_in -> check_out), tự chia theo
     * giờ thường (04:00-22:00) và giờ đêm (22:00-04:00), kể cả khi ca kéo dài qua nhiều ngày.
     */
    public static function sessionPay(Carbon $checkIn, ?Carbon $checkOut, int $normalRate, int $nightRate): array
    {
        if (!$checkOut) {
            return ['hours' => 0.0, 'pay' => 0];
        }

        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        $nightMinutes = 0;

        $dayCursor = $checkIn->copy()->startOfDay();
        while ($dayCursor->lt($checkOut)) {
            $night1Start = $dayCursor->copy();
            $night1End   = $dayCursor->copy()->addHours(4);   // 00:00 - 04:00
            $night2Start = $dayCursor->copy()->addHours(22);  // 22:00 - 24:00
            $night2End   = $dayCursor->copy()->addDay();

            $nightMinutes += self::overlapMinutes($checkIn, $checkOut, $night1Start, $night1End);
            $nightMinutes += self::overlapMinutes($checkIn, $checkOut, $night2Start, $night2End);

            $dayCursor->addDay();
        }

        $normalMinutes = max(0, $totalMinutes - $nightMinutes);
        $pay = round(($normalMinutes / 60) * $normalRate + ($nightMinutes / 60) * $nightRate);

        return [
            'hours' => round($totalMinutes / 60, 2),
            'pay'   => (int) $pay,
        ];
    }

    /**
     * Gộp danh sách bản ghi `history` (mỗi bản ghi có check_in/check_out) thành
     * tổng giờ công + tổng lương, kèm chi tiết từng ca.
     */
    public static function summary(Collection $historyRows, int $normalRate, int $nightRate): array
    {
        $sessions = $historyRows->map(function ($row) use ($normalRate, $nightRate) {
            $checkIn  = Carbon::parse($row->check_in);
            $checkOut = $row->check_out ? Carbon::parse($row->check_out) : null;
            $calc     = self::sessionPay($checkIn, $checkOut, $normalRate, $nightRate);

            return [
                'check_in'  => $checkIn->format('d/m/Y H:i:s'),
                'check_out' => $checkOut?->format('d/m/Y H:i:s'),
                'hours'     => $calc['hours'],
                'pay'       => $calc['pay'],
            ];
        })->values()->all();

        return [
            'sessions'    => $sessions,
            'total_hours' => round(array_sum(array_column($sessions, 'hours')), 2),
            'total_pay'   => array_sum(array_column($sessions, 'pay')),
        ];
    }

    private static function overlapMinutes(Carbon $start, Carbon $end, Carbon $rangeStart, Carbon $rangeEnd): int
    {
        $from = $start->greaterThan($rangeStart) ? $start : $rangeStart;
        $to   = $end->lessThan($rangeEnd) ? $end : $rangeEnd;

        return $to->greaterThan($from) ? (int) $from->diffInMinutes($to) : 0;
    }
}

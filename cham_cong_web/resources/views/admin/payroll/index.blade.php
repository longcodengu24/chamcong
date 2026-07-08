@extends('layouts.app')

@section('title', 'Bảng lương')
@section('page-title', 'Bảng lương')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">💰 Bảng lương theo tháng</span>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="month" name="month" value="{{ $month }}" class="form-input" style="width:160px">
            <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
        </form>
    </div>
    <div style="font-size:12px;color:#94a3b8;margin-bottom:14px">
        Mỗi nhân viên có đơn giá giờ thường riêng (xem/sửa ở trang Nhân viên); giờ đêm (22:00–04:00) = đơn giá + 5.000đ.
        Giờ công mỗi ngày = lần quẹt thẻ cuối − lần quẹt thẻ đầu.
    </div>
    <div class="table-wrap">
        @if(count($rows) > 0)
        <table>
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Đơn giá (thường/đêm)</th>
                    <th>Số ngày công</th>
                    <th>Tổng giờ</th>
                    <th>Tổng lương</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td><strong>{{ $row['employee']->name }}</strong></td>
                    <td style="font-size:12px">{{ number_format($row['employee']->price) }}đ / {{ number_format($row['employee']->nightRate()) }}đ</td>
                    <td>{{ $row['days'] }}</td>
                    <td>{{ $row['total_hours'] }}</td>
                    <td><strong>{{ number_format($row['total_pay']) }}đ</strong></td>
                    <td>
                        <a href="{{ route('admin.payroll.show', ['employee' => $row['employee']->id, 'month' => $month]) }}"
                           class="btn btn-secondary btn-sm">Chi tiết →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div style="text-align:center;padding:30px;color:#94a3b8">Chưa có nhân viên nào gắn thẻ RFID</div>
        @endif
    </div>
</div>
@endsection

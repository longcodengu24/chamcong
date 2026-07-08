@extends('layouts.app')

@section('title', 'Dashboard cá nhân')
@section('page-title', 'Dashboard cá nhân')

@section('content')
{{-- Thông tin cá nhân --}}
<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

    <div>
        <div class="card" style="text-align:center;padding:28px 20px">
            <div style="font-size:64px;margin-bottom:12px">👤</div>
            <div style="font-size:18px;font-weight:700">{{ auth()->user()->name }}</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px">{{ auth()->user()->username }}</div>

            @if($rfidUid)
                <div style="margin-top:14px">
                    <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em">Thẻ RFID</div>
                    <code style="background:#f1f5f9;padding:4px 10px;border-radius:6px;font-size:13px;display:inline-block;margin-top:4px">
                        {{ $rfidUid }}
                    </code>
                </div>
            @endif
        </div>

        {{-- Trạng thái hôm nay --}}
        <div class="card" style="text-align:center;padding:20px">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:8px">
                Hôm nay — {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}
            </div>
            @if($rfidUid)
                @if($isCheckedIn)
                    <div style="font-size:36px">✅</div>
                    <div style="font-weight:700;color:#16a34a;margin-top:6px">Đang làm việc</div>
                @else
                    <div style="font-size:36px">⏳</div>
                    <div style="font-weight:700;color:#d97706;margin-top:6px">Chưa vào ca</div>
                @endif
            @else
                <div style="font-size:36px">❓</div>
                <div style="font-size:13px;color:#94a3b8;margin-top:6px">Chưa gắn thẻ RFID<br>Liên hệ admin để được cấp thẻ</div>
            @endif
        </div>

        {{-- Tháng này --}}
        <div class="card">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:12px">
                Tháng {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('m/Y') }}
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-size:32px;font-weight:700">{{ count($sessions) }}</div>
                    <div style="font-size:12px;color:#64748b">ca đã làm</div>
                </div>
                <div style="font-size:40px">📊</div>
            </div>
        </div>

        {{-- Lương tháng này --}}
        @if($payroll)
        <div class="card" style="text-align:center;padding:20px">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:8px">
                💰 Lương tháng này
            </div>
            <div style="font-size:28px;font-weight:700;color:#16a34a">{{ number_format($payroll['total_pay']) }}đ</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:4px">{{ $payroll['total_hours'] }} giờ công</div>
        </div>
        @endif
    </div>

    {{-- Lịch sử chấm công --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Lịch sử chấm công</span>
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="month" name="month" value="{{ $month }}" class="form-input" style="width:160px">
                <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
            </form>
        </div>

        @if(!$rfidUid)
            <div style="text-align:center;padding:50px;color:#94a3b8">
                <div style="font-size:40px;margin-bottom:8px">🔖</div>
                <div style="font-weight:600">Tài khoản chưa được gắn thẻ RFID</div>
                <div style="font-size:13px;margin-top:6px">Liên hệ admin để được gắn thẻ RFID vào tài khoản này</div>
            </div>
        @elseif(count($sessions) === 0)
            <div style="text-align:center;padding:50px;color:#94a3b8">
                <div style="font-size:40px;margin-bottom:8px">📭</div>
                Không có dữ liệu chấm công tháng {{ $month }}
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Số giờ</th>
                            <th>Lương ca</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $i => $session)
                        <tr>
                            <td style="color:#94a3b8">{{ $i + 1 }}</td>
                            <td><strong>{{ $session['check_in'] }}</strong></td>
                            <td>
                                @if($session['check_out'])
                                    {{ $session['check_out'] }}
                                @else
                                    <span class="badge badge-warning">Đang làm việc</span>
                                @endif
                            </td>
                            <td>{{ $session['hours'] }}</td>
                            <td>{{ number_format($session['pay']) }}đ</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

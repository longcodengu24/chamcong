@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📅</div>
        <div>
            <div class="stat-value">{{ $stats['today_count'] }}</div>
            <div class="stat-label">Chấm công hôm nay</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">👥</div>
        <div>
            <div class="stat-value">{{ $stats['total_employees'] }}</div>
            <div class="stat-label">Tổng nhân viên RFID</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📅</div>
        <div>
            <div class="stat-value">{{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m') }}</div>
            <div class="stat-label">Hôm nay</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">🕐</div>
        <div>
            <div class="stat-value">{{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') }}</div>
            <div class="stat-label">Thời gian hiện tại</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Chấm công hôm nay — {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}</span>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary btn-sm">Xem tất cả →</a>
    </div>
    <div class="table-wrap">
        @if(count($recentHistory) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Họ tên</th>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentHistory as $i => $record)
                <tr>
                    <td style="color:#94a3b8">{{ $i + 1 }}</td>
                    <td><strong>{{ $record['name'] }}</strong></td>
                    <td>{{ $record['check_in'] }}</td>
                    <td>{{ $record['check_out'] ?? '-' }}</td>
                    <td>
                        @if($record['check_out'])
                            <span class="badge badge-success">✓ Đã ra ca</span>
                        @else
                            <span class="badge badge-warning">Đang làm việc</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div style="text-align:center;padding:40px;color:#94a3b8">
                <div style="font-size:40px;margin-bottom:8px">📭</div>
                Chưa có dữ liệu chấm công hôm nay
            </div>
        @endif
    </div>
</div>

<div class="card" style="background: linear-gradient(135deg, #1e3a5f, #1e293b); color: #e2e8f0;">
    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div style="font-size:32px">📡</div>
        <div>
            <div style="font-weight:600;font-size:15px;margin-bottom:6px">Trạng thái thiết bị ESP32</div>
            <div style="font-size:13px;color:#94a3b8">
                ESP32 kết nối trực tiếp Supabase (Postgres). Dữ liệu chấm công được cập nhật khi nhân viên quẹt thẻ RFID.
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                <span class="badge badge-success">● Supabase Connected</span>
                <span class="badge badge-blue">RFID RC522</span>
                <span class="badge badge-gray">ESP32 Dev</span>
            </div>
        </div>
    </div>
</div>
@endsection

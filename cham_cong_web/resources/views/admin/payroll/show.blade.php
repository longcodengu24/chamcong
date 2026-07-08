@extends('layouts.app')

@section('title', 'Chi tiết lương')
@section('page-title', 'Chi tiết lương — ' . $employee->name)

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">💰 {{ $employee->name }} — Tháng {{ $month }}</span>
        <div style="display:flex;gap:8px;align-items:center">
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="month" name="month" value="{{ $month }}" class="form-input" style="width:160px">
                <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
            </form>
            <a href="{{ route('admin.payroll.index', ['month' => $month]) }}" class="btn btn-secondary btn-sm">← Quay lại</a>
        </div>
    </div>

    <div class="stat-grid" style="margin-bottom:0">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div>
                <div class="stat-value">{{ count($summary['sessions']) }}</div>
                <div class="stat-label">Số ca làm</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">⏱️</div>
            <div>
                <div class="stat-value">{{ $summary['total_hours'] }}</div>
                <div class="stat-label">Tổng giờ</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💵</div>
            <div>
                <div class="stat-value">{{ number_format($summary['total_pay']) }}đ</div>
                <div class="stat-label">Tổng lương</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Chi tiết từng ca</span>
    </div>
    <div class="table-wrap">
        @if(count($summary['sessions']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Số giờ</th>
                    <th>Lương ca</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['sessions'] as $session)
                <tr>
                    <td>{{ $session['check_in'] }}</td>
                    <td>
                        @if($session['check_out'])
                            {{ $session['check_out'] }}
                        @else
                            <span class="badge badge-warning">Đang làm việc</span>
                        @endif
                    </td>
                    <td>{{ $session['hours'] }}</td>
                    <td><strong>{{ number_format($session['pay']) }}đ</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div style="text-align:center;padding:30px;color:#94a3b8">Không có dữ liệu chấm công tháng này</div>
        @endif
    </div>
</div>
@endsection

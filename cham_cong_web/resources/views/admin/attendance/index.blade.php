@extends('layouts.app')

@section('title', 'Lịch sử chấm công')
@section('page-title', 'Lịch sử chấm công')

@section('content')
{{-- Bộ lọc --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">🔍 Bộ lọc</span>
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.attendance.export', request()->only('date','month')) }}" class="btn btn-success btn-sm">
                ⬇ Xuất CSV
            </a>
        </div>
    </div>
    <form method="GET" action="{{ route('admin.attendance.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0">
            <label class="form-label">Lọc theo ngày</label>
            <input type="date" name="date" class="form-input" value="{{ $date }}" style="width:180px">
        </div>
        <div style="color:#94a3b8;align-self:center;padding-bottom:0">hoặc</div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Lọc theo tháng</label>
            <input type="month" name="month" class="form-input" value="{{ $month }}" style="width:160px"
                   oninput="this.form.date.value=''">
        </div>
        <button type="submit" class="btn btn-primary" style="height:38px">Lọc</button>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary" style="height:38px">Đặt lại</a>
    </form>
</div>

{{-- Bảng dữ liệu --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">
            📋 Kết quả
            @if($month)
                — Tháng {{ $month }}
            @else
                — Ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            @endif
            <span style="font-size:13px;color:#64748b;font-weight:400">({{ count($records) }} bản ghi)</span>
        </span>
    </div>
    <div class="table-wrap">
        @if(count($records) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Họ tên</th>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Số giờ</th>
                    <th>Tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $i => $record)
                <tr>
                    <td style="color:#94a3b8">{{ $i + 1 }}</td>
                    <td><strong>{{ $record['name'] }}</strong></td>
                    <td>{{ $record['check_in'] }}</td>
                    <td>
                        @if($record['check_out'])
                            {{ $record['check_out'] }}
                        @else
                            <span class="badge badge-warning">Đang làm việc</span>
                        @endif
                    </td>
                    <td>{{ $record['hours'] }}</td>
                    <td><strong>{{ number_format($record['pay']) }}đ</strong></td>
                    <td>
                        <form method="POST" action="{{ route('admin.attendance.destroy') }}"
                              onsubmit="return confirm('Xoá bản ghi này?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="id" value="{{ $record['id'] }}">
                            <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div style="text-align:center;padding:50px;color:#94a3b8">
                <div style="font-size:40px;margin-bottom:8px">📭</div>
                Không có dữ liệu chấm công cho khoảng thời gian này
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Tổng quan')

@section('content')
<h1 class="text-xl font-semibold mb-6">Tổng quan</h1>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white border rounded-lg p-5">
        <div class="text-sm text-gray-500">Nhân viên</div>
        <div class="text-2xl font-semibold mt-1">{{ $totalEmployees }}</div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <div class="text-sm text-gray-500">Lượt chấm công hôm nay</div>
        <div class="text-2xl font-semibold mt-1">{{ $todayCheckIns }}</div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <div class="text-sm text-gray-500">Đang trong ca</div>
        <div class="text-2xl font-semibold mt-1">{{ $openShifts }}</div>
    </div>
</div>
@endsection

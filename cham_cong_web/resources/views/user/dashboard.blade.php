@extends('layouts.app')

@section('title', 'Chấm công của tôi')

@section('content')
<h1 class="text-xl font-semibold mb-6">Chấm công của tôi</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border rounded-lg p-5">
        <div class="text-sm text-gray-500">Tiền công tháng này</div>
        <div class="text-2xl font-semibold mt-1">{{ number_format($totalThisMonth) }} đ</div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <div class="text-sm text-gray-500">Tổng tiền công đã nhận</div>
        <div class="text-2xl font-semibold mt-1">{{ number_format($totalAllTime) }} đ</div>
    </div>
</div>

<div class="bg-white border rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500">
            <tr>
                <th class="px-4 py-2">Vào ca</th>
                <th class="px-4 py-2">Ra ca</th>
                <th class="px-4 py-2">Tiền công</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($records as $record)
                <tr>
                    <td class="px-4 py-2">{{ $record->check_in?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $record->check_out?->format('d/m/Y H:i') ?: 'Đang trong ca' }}</td>
                    <td class="px-4 py-2">{{ $record->amount !== null ? number_format($record->amount).' đ' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-gray-400">Chưa có lịch sử chấm công.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $records->links() }}
</div>
@endsection

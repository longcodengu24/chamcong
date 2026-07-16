@extends('layouts.app')

@section('title', 'Chấm công')

@section('content')
<h1 class="text-xl font-semibold mb-6">Lịch sử chấm công</h1>

<form method="GET" class="flex gap-3 mb-4">
    <select name="employee_id" class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
        <option value="">Tất cả nhân viên</option>
        @foreach ($employees as $employee)
            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>
        @endforeach
    </select>
    <input type="month" name="month" value="{{ request('month') }}"
           class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
    <button class="bg-gray-900 text-white text-sm rounded px-4 hover:bg-gray-800">Lọc</button>
</form>

<div class="bg-white border rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500">
            <tr>
                <th class="px-4 py-2">Nhân viên</th>
                <th class="px-4 py-2">Vào ca</th>
                <th class="px-4 py-2">Ra ca</th>
                <th class="px-4 py-2">Tiền công</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($records as $record)
                <tr>
                    <td class="px-4 py-2">{{ $record->user->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $record->check_in?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $record->check_out?->format('d/m/Y H:i') ?: 'Đang trong ca' }}</td>
                    <td class="px-4 py-2">{{ $record->amount !== null ? number_format($record->amount).' đ' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">Chưa có dữ liệu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $records->links() }}
</div>
@endsection

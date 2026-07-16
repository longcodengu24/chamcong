@extends('layouts.app')

@section('title', 'Nhân viên')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">Nhân viên</h1>
    <a href="{{ route('admin.employees.create') }}" class="bg-gray-900 text-white text-sm rounded px-4 py-2 hover:bg-gray-800">
        + Thêm nhân viên
    </a>
</div>

<div class="bg-white border rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500">
            <tr>
                <th class="px-4 py-2">Tên</th>
                <th class="px-4 py-2">Tài khoản</th>
                <th class="px-4 py-2">Vai trò</th>
                <th class="px-4 py-2">Thẻ RFID</th>
                <th class="px-4 py-2">Khung giờ / đơn giá</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($employees as $employee)
                <tr>
                    <td class="px-4 py-2">{{ $employee->name }}</td>
                    <td class="px-4 py-2">{{ $employee->username }}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $employee->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $employee->role }}
                        </span>
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $employee->rfid_uid ?: '—' }}</td>
                    <td class="px-4 py-2">
                        @if (empty($employee->rate_schedule))
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Chưa cấu hình</span>
                        @else
                            <span class="text-xs text-gray-600">
                                {{ count($employee->rate_schedule) }} khung
                                ({{ collect($employee->rate_schedule)->pluck('from')->implode(', ') }})
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('admin.employees.edit', $employee) }}" class="text-blue-600 hover:underline">Sửa</a>
                        <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}" class="inline"
                              onsubmit="return confirm('Xóa nhân viên này?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">Chưa có nhân viên nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

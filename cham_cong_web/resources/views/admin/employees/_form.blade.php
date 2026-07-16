@csrf
@if (isset($employee))
    @method('PUT')
@endif

@if ($errors->any())
    <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-2 text-sm">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Họ tên</label>
        <input type="text" name="name" value="{{ old('name', $employee->name ?? '') }}" required
               class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Tài khoản đăng nhập</label>
        <input type="text" name="username" value="{{ old('username', $employee->username ?? '') }}" required
               class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">
            Mật khẩu
            @if (isset($employee))
                <span class="text-gray-400 font-normal">(để trống nếu không đổi)</span>
            @endif
        </label>
        <input type="password" name="password"
               class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Vai trò</label>
        <select name="role" required class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
            @php $role = old('role', $employee->role ?? 'user'); @endphp
            <option value="user" @selected($role === 'user')>Nhân viên</option>
            <option value="admin" @selected($role === 'admin')>Admin</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Mã thẻ RFID</label>
        <div class="flex gap-2">
            <input type="text" id="rfid_uid" name="rfid_uid" value="{{ old('rfid_uid', $employee->rfid_uid ?? '') }}"
                   class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0 font-mono">
            <button type="button" id="scan-btn"
                    class="whitespace-nowrap bg-gray-100 border rounded px-3 text-sm hover:bg-gray-200">
                Quét thẻ
            </button>
        </div>
        <p id="scan-status" class="text-xs text-gray-400 mt-1"></p>
    </div>
</div>

@php
    $scheduleRows = old('rate_from')
        ? collect(old('rate_from'))->map(fn ($from, $i) => [
            'from' => $from,
            'to' => old('rate_to')[$i] ?? '',
            'rate' => old('rate_price')[$i] ?? '',
        ])->values()->all()
        : collect($employee->rate_schedule ?? [])->values()->all();
@endphp

<div class="mt-6">
    <label class="block text-sm font-medium mb-1">Đơn giá theo khung giờ</label>
    <p class="text-xs text-gray-400 mb-2">Khai báo ít nhất 1 khung giờ + đơn giá (vd 22:00 → 06:00 = 30.000đ). Khung qua nửa đêm (giờ bắt đầu lớn hơn giờ kết thúc) tự động được hiểu là kéo dài sang ngày hôm sau. Giờ nào không nằm trong khung nào sẽ không được tính tiền.</p>

    <div id="rate-rows" class="space-y-2">
        @foreach ($scheduleRows as $row)
            <div class="rate-row flex items-center gap-2">
                <input type="time" name="rate_from[]" value="{{ $row['from'] }}"
                       class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
                <span class="text-gray-400 text-sm">đến</span>
                <input type="time" name="rate_to[]" value="{{ $row['to'] }}"
                       class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
                <input type="number" name="rate_price[]" min="0" value="{{ $row['rate'] }}" placeholder="Đơn giá (đ)"
                       class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0 flex-1">
                <button type="button" class="remove-row text-red-600 text-sm hover:underline">Xóa</button>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-rate-row" class="mt-2 text-sm text-blue-600 hover:underline">
        + Thêm khung giờ
    </button>
</div>

<template id="rate-row-template">
    <div class="rate-row flex items-center gap-2">
        <input type="time" name="rate_from[]" class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
        <span class="text-gray-400 text-sm">đến</span>
        <input type="time" name="rate_to[]" class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0">
        <input type="number" name="rate_price[]" min="0" placeholder="Đơn giá (đ)"
               class="rounded border-gray-300 text-sm focus:border-gray-500 focus:ring-0 flex-1">
        <button type="button" class="remove-row text-red-600 text-sm hover:underline">Xóa</button>
    </div>
</template>

<div class="mt-6">
    <button type="submit" class="bg-gray-900 text-white text-sm rounded px-4 py-2 hover:bg-gray-800">
        Lưu
    </button>
    <a href="{{ route('admin.employees.index') }}" class="text-sm text-gray-500 ml-3">Hủy</a>
</div>

<script>
(function () {
    const rowsContainer = document.getElementById('rate-rows');
    const template = document.getElementById('rate-row-template');

    document.getElementById('add-rate-row').addEventListener('click', function () {
        rowsContainer.appendChild(template.content.cloneNode(true));
    });

    rowsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.rate-row').remove();
        }
    });
})();

(function () {
    const btn = document.getElementById('scan-btn');
    const input = document.getElementById('rfid_uid');
    const status = document.getElementById('scan-status');
    let polling = null;

    btn.addEventListener('click', function () {
        if (polling) {
            clearInterval(polling);
            polling = null;
            btn.textContent = 'Quét thẻ';
            status.textContent = '';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Đang chuẩn bị...';
        status.textContent = '';

        // Lấy id của lượt quét chưa gán gần nhất hiện tại làm mốc, để không
        // nhận nhầm một thẻ lạ đã quét từ trước đó.
        fetch(`{{ route('admin.rfid.latest-scan') }}?after_id=0`)
            .then((r) => r.json())
            .then((baseline) => startPolling(baseline.id || 0));
    });

    function startPolling(afterId) {
        btn.disabled = false;
        btn.textContent = 'Đang chờ quẹt thẻ...';
        status.textContent = 'Hãy quẹt thẻ trên đầu đọc RFID trong 30 giây...';

        let elapsed = 0;
        polling = setInterval(function () {
            elapsed += 2;
            fetch(`{{ route('admin.rfid.latest-scan') }}?after_id=${afterId}`)
                .then((r) => r.json())
                .then((data) => {
                    if (data.found) {
                        input.value = data.rfid_uid;
                        status.textContent = 'Đã nhận thẻ: ' + data.rfid_uid;
                        clearInterval(polling);
                        polling = null;
                        btn.textContent = 'Quét thẻ';
                    } else if (elapsed >= 30) {
                        status.textContent = 'Không nhận được thẻ nào, thử lại.';
                        clearInterval(polling);
                        polling = null;
                        btn.textContent = 'Quét thẻ';
                    }
                });
        }, 2000);
    }
})();
</script>

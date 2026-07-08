@extends('layouts.app')

@section('title', 'Quản lý Nhân viên')
@section('page-title', 'Nhân viên')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

    {{-- Danh sách nhân viên --}}
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">👥 Danh sách nhân viên ({{ count($employees) }})</span>
            </div>
            <div class="table-wrap">
                @if(count($employees) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Tên đăng nhập</th>
                            <th>Vai trò</th>
                            <th>Thẻ RFID</th>
                            <th>Đơn giá/giờ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        <tr>
                            <td><strong>{{ $emp->name }}</strong></td>
                            <td style="font-size:13px">{{ $emp->username }}</td>
                            <td>
                                <span class="badge {{ $emp->role === 'admin' ? 'badge-blue' : 'badge-gray' }}">
                                    {{ $emp->role === 'admin' ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td>
                                @if($emp->rfid_uid)
                                    <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px">{{ $emp->rfid_uid }}</code>
                                @else
                                    <span class="badge badge-gray">Chưa gắn</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.employees.price', $emp->id) }}" style="display:flex;gap:4px;align-items:center">
                                    @csrf @method('PATCH')
                                    <input type="number" name="price" value="{{ $emp->price }}" min="0" step="1000"
                                           class="form-input" style="width:90px;padding:5px 8px;font-size:12px">
                                    <button type="submit" class="btn btn-secondary btn-sm">Lưu</button>
                                </form>
                                <div style="font-size:10px;color:#94a3b8;margin-top:2px">đêm: {{ number_format($emp->nightRate()) }}đ</div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.employees.destroy', $emp->id) }}"
                                      onsubmit="return confirm('Xoá nhân viên {{ addslashes($emp->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            {{ $emp->id === auth()->id() ? 'disabled' : '' }}>Xoá</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div style="text-align:center;padding:30px;color:#94a3b8">Chưa có nhân viên nào</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Form thêm nhân viên --}}
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">➕ Thêm nhân viên</span>
            </div>
            <form method="POST" action="{{ route('admin.employees.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Họ tên <span style="color:red">*</span></label>
                        <input type="text" name="name" class="form-input"
                               placeholder="Nguyễn Văn A" value="{{ old('name') }}">
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập <span style="color:red">*</span></label>
                        <input type="text" name="username" class="form-input"
                               placeholder="nguyenvana" value="{{ old('username') }}">
                        @error('username')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mật khẩu <span style="color:red">*</span></label>
                        <input type="password" name="password" class="form-input" placeholder="Tối thiểu 6 ký tự">
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Nhập lại mật khẩu">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Vai trò <span style="color:red">*</span></label>
                        <select name="role" class="form-input">
                            <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User (nhân viên)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (quản trị)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Đơn giá giờ thường <span style="color:red">*</span></label>
                        <input type="number" name="price" class="form-input" min="0" step="1000"
                               value="{{ old('price', 25000) }}">
                        @error('price')<div class="form-error">{{ $message }}</div>@enderror
                        <div style="font-size:11px;color:#94a3b8;margin-top:3px">Giờ đêm (22h-4h) tự động = đơn giá + 5.000đ</div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-input"
                           placeholder="0901234567" value="{{ old('phone') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Thẻ RFID (tuỳ chọn)</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="rfid_uid" id="rfid_uid" class="form-input"
                               placeholder="A1B2C3D4" value="{{ old('rfid_uid') }}"
                               style="font-family:monospace;text-transform:uppercase;flex:1">
                        <button type="button" id="scan-btn" class="btn btn-secondary" style="white-space:nowrap">📡 Quét thẻ</button>
                    </div>
                    @error('rfid_uid')<div class="form-error">{{ $message }}</div>@enderror
                    <div id="scan-status" style="font-size:12px;margin-top:5px;min-height:16px"></div>
                </div>

                <button type="submit" class="btn btn-primary">💾 Lưu nhân viên</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const scanBtn   = document.getElementById('scan-btn');
    const rfidInput = document.getElementById('rfid_uid');
    const statusEl  = document.getElementById('scan-status');
    let pollTimer    = null;
    let timeoutTimer = null;

    function stopPolling() {
        clearInterval(pollTimer);
        clearTimeout(timeoutTimer);
        pollTimer = null;
        scanBtn.disabled = false;
        scanBtn.textContent = '📡 Quét thẻ';
    }

    scanBtn.addEventListener('click', function () {
        if (pollTimer) { // đang quét dở, bấm lại để huỷ
            stopPolling();
            statusEl.textContent = '';
            return;
        }

        statusEl.textContent = 'Đang chờ quẹt thẻ vào đầu đọc ESP32...';
        statusEl.style.color = '#d97706';
        scanBtn.disabled = true;
        scanBtn.textContent = '⏳ Đang chờ...';

        fetch('{{ route("admin.employees.scan.start") }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const since = data.since;

                pollTimer = setInterval(function () {
                    fetch('{{ route("admin.employees.scan.poll") }}?since=' + encodeURIComponent(since), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(poll => {
                            if (poll.uid) {
                                rfidInput.value = poll.uid;
                                statusEl.textContent = '✅ Đã quét được thẻ: ' + poll.uid;
                                statusEl.style.color = '#16a34a';
                                stopPolling();
                            }
                        });
                }, 1500);

                timeoutTimer = setTimeout(function () {
                    if (pollTimer) {
                        stopPolling();
                        statusEl.textContent = 'Hết thời gian chờ, vui lòng thử lại.';
                        statusEl.style.color = '#dc2626';
                    }
                }, 30000);
            });
    });
})();
</script>
@endsection

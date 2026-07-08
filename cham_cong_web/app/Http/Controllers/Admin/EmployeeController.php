<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::orderBy('name')->get();
        return view('admin.employees.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:admin,user',
            'rfid_uid' => 'nullable|string|max:20|unique:users,rfid_uid',
            'phone'    => 'nullable|string|max:20',
            'price'    => 'required|integer|min:0',
        ]);

        $rfidUid = $data['rfid_uid'] ? strtoupper(trim($data['rfid_uid'])) : null;

        User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'rfid_uid' => $rfidUid,
            'phone'    => $data['phone'] ?? null,
            'price'    => $data['price'],
        ]);

        // Dọn các bản ghi "quẹt thẻ chờ gán" (user_id null) trùng UID này, vì UID đã được gán rồi
        if ($rfidUid) {
            History::whereNull('user_id')->where('rfid_uid', $rfidUid)->delete();
        }

        return redirect()->route('admin.employees.index')
            ->with('success', "Đã thêm nhân viên {$data['name']}");
    }

    // ---- Quẹt thẻ trực tiếp: dựa vào các dòng history có user_id = null (ESP32 ghi khi gặp thẻ lạ) ----

    public function startCardScan()
    {
        return response()->json(['since' => now()->toIso8601String()]);
    }

    public function pollCardScan(Request $request)
    {
        $data = $request->validate(['since' => 'required|date']);

        $row = History::whereNull('user_id')
            ->where('check_in', '>', Carbon::parse($data['since']))
            ->orderByDesc('check_in')
            ->first();

        return response()->json(['uid' => $row?->rfid_uid]);
    }

    public function updatePrice(Request $request, User $employee)
    {
        $data = $request->validate([
            'price' => 'required|integer|min:0',
        ]);

        $employee->update(['price' => $data['price']]);

        return redirect()->route('admin.employees.index')
            ->with('success', "Đã cập nhật đơn giá của {$employee->name}");
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Không thể xoá tài khoản đang đăng nhập.');
        }
        $user->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Đã xoá nhân viên ' . $user->name);
    }
}

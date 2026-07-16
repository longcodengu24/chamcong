<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::orderBy('name')->get();

        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('admin.employees.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateEmployee($request);
        $data['password'] = Hash::make($data['password']);
        $data['rate_schedule'] = $this->extractRateSchedule($request);

        User::create($data);

        return redirect()->route('admin.employees.index')->with('status', 'Đã thêm nhân viên.');
    }

    public function edit(User $employee)
    {
        return view('admin.employees.edit', ['employee' => $employee]);
    }

    public function update(Request $request, User $employee)
    {
        $data = $this->validateEmployee($request, $employee);
        $data['rate_schedule'] = $this->extractRateSchedule($request);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $employee->update($data);

        return redirect()->route('admin.employees.index')->with('status', 'Đã cập nhật nhân viên.');
    }

    public function destroy(User $employee)
    {
        $employee->delete();

        return redirect()->route('admin.employees.index')->with('status', 'Đã xóa nhân viên.');
    }

    /**
     * Poll for the latest unassigned RFID card scan (used by the "quét thẻ" button).
     *
     * Cursor is the row id, not created_at/check_in: the ESP32 firmware writes
     * straight to Supabase via REST and never sets created_at, so it's always
     * null there and unusable for filtering.
     */
    public function latestScan(Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);

        $scan = Price::whereNull('user_id')
            ->whereNotNull('rfid_uid')
            ->where('id', '>', $afterId)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'found' => (bool) $scan,
            'rfid_uid' => $scan?->rfid_uid,
            'id' => $scan?->id,
        ]);
    }

    private function validateEmployee(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('user', 'username')->ignore($employee?->id)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'rfid_uid' => ['nullable', 'string', 'max:255', Rule::unique('user', 'rfid_uid')->ignore($employee?->id)],
        ]);
    }

    /**
     * Parse the repeatable "khung giờ" rows (rate_from[]/rate_to[]/rate_price[])
     * submitted by the employee form into the JSON schedule stored on `user.rate_schedule`.
     * This is now the only way pay is calculated — hours outside every declared
     * range are simply unpaid (see the price trigger), so at least one row is required.
     */
    private function extractRateSchedule(Request $request): array
    {
        $froms = $request->input('rate_from', []);
        $tos = $request->input('rate_to', []);
        $rates = $request->input('rate_price', []);

        $rules = [];
        foreach ($froms as $i => $from) {
            if (($from ?? '') === '' && ($tos[$i] ?? '') === '' && ($rates[$i] ?? '') === '') {
                continue;
            }

            $rules["rate_from.$i"] = ['required', 'date_format:H:i'];
            $rules["rate_to.$i"] = ['required', 'date_format:H:i'];
            $rules["rate_price.$i"] = ['required', 'integer', 'min:0'];
        }

        $request->validate($rules);

        $schedule = [];
        foreach ($froms as $i => $from) {
            if (($from ?? '') === '' && ($tos[$i] ?? '') === '' && ($rates[$i] ?? '') === '') {
                continue;
            }

            $schedule[] = [
                'from' => $from,
                'to' => $tos[$i],
                'rate' => (int) $rates[$i],
            ];
        }

        if (empty($schedule)) {
            throw ValidationException::withMessages([
                'rate_from' => 'Cần khai báo ít nhất 1 khung giờ và đơn giá.',
            ]);
        }

        return $schedule;
    }
}

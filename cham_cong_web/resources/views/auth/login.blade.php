<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Hệ thống Chấm Công</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1e3a5f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .login-header { text-align: center; margin-bottom: 32px; }
        .login-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
        }
        .login-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .login-header p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: border-color .15s;
            background: #f8fafc;
        }
        .form-input:focus { outline: none; border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .form-error { color: #dc2626; font-size: 12px; margin-top: 4px; }
        .remember-row {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px;
            font-size: 13px; color: #64748b;
        }
        .btn-login {
            width: 100%;
            padding: 11px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-login:hover { background: #1d4ed8; }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .login-footer { text-align: center; margin-top: 24px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-header">
        <span class="login-icon">📋</span>
        <h1>Hệ thống Chấm Công</h1>
        <p>ESP32 + RFID + Supabase</p>
    </div>

    @if($errors->any())
        <div class="alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-input" value="{{ old('username') }}"
                   placeholder="admin" required autofocus>
            @error('username')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-input"
                   placeholder="••••••••" required>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="remember-row">
            <input type="checkbox" name="remember" id="remember" value="1">
            <label for="remember">Ghi nhớ đăng nhập</label>
        </div>
        <button type="submit" class="btn-login">Đăng nhập</button>
    </form>

    <div class="login-footer">
        Hệ thống chấm công tự động qua thẻ RFID
    </div>
</div>
</body>
</html>

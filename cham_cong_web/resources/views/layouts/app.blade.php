<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hệ thống Chấm Công') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
            --bg: #f1f5f9;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #2563eb;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ---- Layout ---- */
        .layout { display: flex; min-height: 100vh; }

        /* ---- Sidebar ---- */
        .sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform .25s;
        }
        .sidebar-brand {
            padding: 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h1 {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.3;
        }
        .sidebar-brand p { color: #94a3b8; font-size: 11px; margin-top: 2px; }
        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .nav-label {
            padding: 8px 18px 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #475569;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 14px;
            border-radius: 0;
            transition: background .15s, color .15s;
        }
        .nav-link:hover { background: rgba(255,255,255,.06); color: #fff; }
        .nav-link.active { background: var(--sidebar-active); color: #fff; }
        .nav-icon { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer {
            padding: 14px 18px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-user { color: #94a3b8; font-size: 12px; margin-bottom: 8px; }
        .sidebar-user strong { display: block; color: #e2e8f0; font-size: 13px; }
        .btn-logout {
            display: flex; align-items: center; gap: 8px;
            width: 100%;
            padding: 8px 12px;
            background: rgba(220,38,38,.15);
            color: #fca5a5;
            border: 1px solid rgba(220,38,38,.3);
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-logout:hover { background: rgba(220,38,38,.25); }

        /* ---- Main content ---- */
        .main-content {
            margin-left: 240px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-title { font-size: 18px; font-weight: 600; }
        .topbar-meta { font-size: 13px; color: var(--text-muted); }
        .page-content { padding: 24px; flex: 1; }

        /* ---- Cards ---- */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 15px; font-weight: 600; }

        /* ---- Stat cards ---- */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            font-size: 28px;
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-icon.blue  { background: #dbeafe; }
        .stat-icon.green { background: #dcfce7; }
        .stat-icon.amber { background: #fef3c7; }
        .stat-icon.purple{ background: #f3e8ff; }
        .stat-value { font-size: 26px; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

        /* ---- Table ---- */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
        }
        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        /* ---- Badges ---- */
        .badge {
            display: inline-flex; align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger  { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-blue    { background: #dbeafe; color: #1d4ed8; }
        .badge-gray    { background: #f1f5f9; color: #475569; }

        /* ---- Buttons ---- */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity .15s, background .15s;
        }
        .btn:hover { opacity: .88; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger  { background: var(--danger);  color: #fff; }
        .btn-secondary { background: #f1f5f9; color: var(--text); border: 1px solid var(--border); }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        /* ---- Form ---- */
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 14px;
            transition: border-color .15s;
            background: #fff;
        }
        .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-error { color: var(--danger); font-size: 12px; margin-top: 4px; }

        /* ---- Alerts ---- */
        .alert {
            padding: 10px 14px;
            border-radius: 7px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger  { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* ---- Responsive ---- */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-240px); }
            .main-content { margin-left: 0; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1>📋 Chấm Công IoT</h1>
            <p>ESP32 + RFID + Supabase</p>
        </div>
        <nav class="sidebar-nav">
            @if(auth()->user()->isAdmin())
                <div class="nav-label">Quản trị</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="{{ route('admin.employees.index') }}" class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span> Nhân viên & Thẻ
                </a>
                <a href="{{ route('admin.attendance.index') }}" class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span> Lịch sử chấm công
                </a>
                <a href="{{ route('admin.payroll.index') }}" class="nav-link {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Bảng lương
                </a>
            @else
                <div class="nav-label">Cá nhân</div>
                <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Dashboard
                </a>
            @endif
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <strong>{{ auth()->user()->name }}</strong>
                {{ auth()->user()->username }}
                <span class="badge {{ auth()->user()->isAdmin() ? 'badge-blue' : 'badge-success' }}" style="margin-top:4px">
                    {{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">
                    🚪 Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
            <span class="topbar-meta">
                {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
            </span>
        </div>
        <div class="page-content">
            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">❌ {{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Chấm công')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    @auth
    <nav class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="font-semibold">Chấm công</span>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Tổng quan</a>
                    <a href="{{ route('admin.employees.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Nhân viên</a>
                    <a href="{{ route('admin.attendance.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Chấm công</a>
                @else
                    <a href="{{ route('user.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Của tôi</a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-red-600 hover:text-red-800">Đăng xuất</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-5xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-50 border border-green-200 text-green-800 px-4 py-2 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>

@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
<div class="max-w-sm mx-auto mt-16">
    <h1 class="text-xl font-semibold mb-6 text-center">Đăng nhập</h1>

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-2 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" class="bg-white border rounded-lg p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tài khoản</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus
                   class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Mật khẩu</label>
            <input type="password" name="password" required
                   class="w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
        </div>
        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800">
            Đăng nhập
        </button>
    </form>
</div>
@endsection

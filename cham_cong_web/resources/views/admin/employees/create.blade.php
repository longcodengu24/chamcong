@extends('layouts.app')

@section('title', 'Thêm nhân viên')

@section('content')
<h1 class="text-xl font-semibold mb-6">Thêm nhân viên</h1>

<form method="POST" action="{{ route('admin.employees.store') }}" class="bg-white border rounded-lg p-6">
    @include('admin.employees._form')
</form>
@endsection

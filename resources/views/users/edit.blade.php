@extends('layouts.app')
@section('title','تعديل مستخدم')
@section('page-title','تعديل المستخدم')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">✏️ تعديل: {{ $user->name }}</div>
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label-dark">الاسم *</label><input type="text" name="name" class="form-control form-control-dark" value="{{ $user->name }}" required></div>
            <div class="mb-3"><label class="form-label-dark">البريد *</label><input type="email" name="email" class="form-control form-control-dark" value="{{ $user->email }}" required></div>
            <div class="mb-3"><label class="form-label-dark">كلمة مرور جديدة (اتركها فارغة للإبقاء)</label><input type="password" name="password" class="form-control form-control-dark"></div>
            <div class="mb-4"><label class="form-label-dark">الدور *</label>
                <select name="role" class="form-select form-select-dark" required>
                    @foreach($roles as $role)<option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name }}</option>@endforeach
                </select></div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('users.index') }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-check-lg"></i> حفظ</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

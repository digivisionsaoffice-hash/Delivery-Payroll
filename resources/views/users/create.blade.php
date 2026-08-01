@extends('layouts.app')
@section('title','مستخدم جديد')
@section('page-title','إضافة مستخدم')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">👤 مستخدم جديد</div>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label-dark">الاسم *</label><input type="text" name="name" class="form-control form-control-dark" required></div>
            <div class="mb-3"><label class="form-label-dark">البريد الإلكتروني *</label><input type="email" name="email" class="form-control form-control-dark" required></div>
            <div class="mb-3"><label class="form-label-dark">كلمة المرور *</label><input type="password" name="password" class="form-control form-control-dark" required></div>
            <div class="mb-3"><label class="form-label-dark">تأكيد كلمة المرور *</label><input type="password" name="password_confirmation" class="form-control form-control-dark" required></div>
            <div class="mb-4"><label class="form-label-dark">الدور *</label>
                <select name="role" class="form-select form-select-dark" required>
                    @foreach($roles as $role)<option value="{{ $role->name }}">{{ $role->name }}</option>@endforeach
                </select></div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('users.index') }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-plus-lg"></i> إضافة</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

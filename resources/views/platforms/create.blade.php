@extends('layouts.app')
@section('title', 'إضافة منصة')
@section('page-title', 'إضافة منصة جديدة')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">🚀 منصة جديدة</div>
        <form method="POST" action="{{ route('platforms.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label-dark">الاسم بالعربي *</label>
                <input type="text" name="name" class="form-control form-control-dark" placeholder="نينجا" required></div>
            <div class="mb-3"><label class="form-label-dark">الاسم بالإنجليزي *</label>
                <input type="text" name="name_en" class="form-control form-control-dark" placeholder="Ninja" required></div>
            <div class="mb-4"><label class="form-label-dark">نوع الفوترة *</label>
                <select name="billing_type" class="form-select form-select-dark" required>
                    <option value="per_order">بالطلبية</option>
                    <option value="tiered">شرائح</option>
                    <option value="fixed">ثابت</option>
                </select></div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('platforms.index') }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-plus-lg"></i> إضافة</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

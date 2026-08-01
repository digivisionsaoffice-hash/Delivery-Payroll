@extends('layouts.app')
@section('title','فرع جديد')
@section('page-title','إضافة فرع')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">🏢 فرع جديد</div>
        <form method="POST" action="{{ route('branches.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label-dark">اسم الفرع *</label>
                <input type="text" name="name" class="form-control form-control-dark" required></div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('branches.index') }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-plus-lg"></i> إضافة</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

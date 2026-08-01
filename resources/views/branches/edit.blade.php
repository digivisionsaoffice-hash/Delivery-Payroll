@extends('layouts.app')
@section('title','تعديل فرع')
@section('page-title','تعديل الفرع')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">✏️ تعديل: {{ $branch->name }}</div>
        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label-dark">اسم الفرع *</label>
                <input type="text" name="name" class="form-control form-control-dark" value="{{ $branch->name }}" required>
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked($branch->is_active)>
                <label class="form-check-label text-light" for="is_active">الفرع نشط</label>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('branches.index') }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-check-lg"></i> حفظ</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

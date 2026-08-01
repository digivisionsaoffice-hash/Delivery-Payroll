@extends('layouts.app')
@section('title', 'إنشاء دورة رواتب')
@section('page-title', 'إنشاء دورة رواتب جديدة')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-6">
        <div class="chart-card fade-in">
            <div class="chart-title mb-4">📅 دورة رواتب جديدة</div>
            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label-dark">المنصة *</label>
                    <select name="platform_id" class="form-select form-select-dark" required>
                        <option value="">اختر المنصة</option>
                        @foreach($platforms as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">الشهر *</label>
                    <input type="month" name="month" class="form-control form-control-dark" value="{{ date('Y-m') }}" required>
                </div>
                <div class="mb-4">
                    <label class="form-label-dark">ملاحظات</label>
                    <textarea name="notes" class="form-control form-control-dark" rows="3"></textarea>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('payroll.index') }}" class="btn-ghost">إلغاء</a>
                    <button type="submit" class="btn-walim"><i class="bi bi-plus-lg"></i> إنشاء</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

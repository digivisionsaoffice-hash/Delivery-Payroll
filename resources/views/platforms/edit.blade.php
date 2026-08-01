@extends('layouts.app')
@section('title', 'تعديل منصة')
@section('page-title', 'تعديل المنصة')
@section('content')
<div class="row justify-content-center"><div class="col-xl-5">
    <div class="chart-card fade-in">
        <div class="chart-title mb-4">✏️ تعديل: {{ $platform->name }}</div>
        <form method="POST" action="{{ route('platforms.update', $platform) }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label-dark">الاسم بالعربي *</label>
                <input type="text" name="name" class="form-control form-control-dark" value="{{ $platform->name }}" required></div>
            <div class="mb-3"><label class="form-label-dark">الاسم بالإنجليزي *</label>
                <input type="text" name="name_en" class="form-control form-control-dark" value="{{ $platform->name_en }}" required></div>
            <div class="mb-3"><label class="form-label-dark">نوع الفوترة *</label>
                <select name="billing_type" class="form-select form-select-dark" required>
                    <option value="per_order" @selected($platform->billing_type=='per_order')>بالطلبية</option>
                    <option value="tiered" @selected($platform->billing_type=='tiered')>شرائح</option>
                    <option value="fixed" @selected($platform->billing_type=='fixed')>ثابت</option>
                </select></div>
            <div class="mb-3">
                <label class="form-label-dark">شكل تقرير التطبيق النهائي *</label>
                <small style="display:block;color:var(--text-muted);font-size:0.78rem;margin-bottom:0.4rem">يحدد هذا الإعداد شكل القالب وطريقة قراءة الأعمدة عند الاستيراد</small>
                <select name="report_format" class="form-select form-select-dark" required>
                    @foreach(\App\Support\PlatformColumnMap::reportFormats() as $fmtKey => $fmtName)
                    <option value="{{ $fmtKey }}" @selected($platform->report_format === $fmtKey)>{{ $fmtName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4 d-flex align-items-center gap-2">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked($platform->is_active)>
                <label class="form-label-dark mb-0">المنصة نشطة</label>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('platforms.show', $platform) }}" class="btn-ghost">إلغاء</a>
                <button type="submit" class="btn-walim"><i class="bi bi-check-lg"></i> حفظ</button>
            </div>
        </form>
    </div>
</div></div>
@endsection

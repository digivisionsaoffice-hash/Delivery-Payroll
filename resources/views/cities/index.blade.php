@extends('layouts.app')
@section('title', 'المدن والمناطق')
@section('page-title', 'المدن والمناطق')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div class="chart-title mb-0">قائمة المدن والمناطق</div>
    <div class="d-flex gap-2">
        <form action="{{ route('cities.destroy_all') }}" method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف جميع المدن؟');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" style="border-radius: 8px; padding: 0.5rem 1rem;">
                <i class="bi bi-trash"></i> حذف الجميع
            </button>
        </form>
        <button class="btn-walim" data-bs-toggle="modal" data-bs-target="#createCityModal">
            <i class="bi bi-plus-lg"></i> إضافة مدينة
        </button>
    </div>
</div>

<div class="chart-card fade-in">
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>المدينة</th>
                    <th>المنطقة</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cities as $c)
                <tr>
                    <td class="fw-bold">{{ $c->name }}</td>
                    <td>{{ $c->region ?? '—' }}</td>
                    <td>
                        <span class="status-badge {{ $c->is_active ? 'badge-active' : 'badge-loss' }}">
                            {{ $c->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="icon-btn" style="width:28px;height:28px" data-bs-toggle="modal" data-bs-target="#editCityModal{{ $c->id }}">
                                <i class="bi bi-pencil" style="font-size:0.8rem"></i>
                            </button>
                            <form action="{{ route('cities.destroy', $c) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المدينة؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn text-danger" style="width:28px;height:28px;background:rgba(239,68,68,0.1)">
                                    <i class="bi bi-trash" style="font-size:0.8rem"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- Edit Modal --}}
                <div class="modal fade" id="editCityModal{{ $c->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('cities.update', $c) }}" method="POST" class="modal-content" style="background:var(--bg-dark);border:1px solid var(--border-color)">
                            @csrf @method('PUT')
                            <div class="modal-header border-bottom-0">
                                <h5 class="modal-title">تعديل المدينة</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label-dark">اسم المدينة *</label>
                                    <input type="text" name="name" class="form-control form-control-dark" value="{{ $c->name }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-dark">المنطقة</label>
                                    <input type="text" name="region" class="form-control form-control-dark" value="{{ $c->region }}">
                                </div>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="is_active_{{ $c->id }}" name="is_active" value="1" @checked($c->is_active)>
                                    <label class="form-check-label text-light" for="is_active_{{ $c->id }}">نشط</label>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn-ghost" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn-walim">حفظ التغييرات</button>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">لا توجد مدن مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createCityModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('cities.store') }}" method="POST" class="modal-content" style="background:var(--bg-dark);border:1px solid var(--border-color)">
            @csrf
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title">إضافة مدينة جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label-dark">اسم المدينة *</label>
                    <input type="text" name="name" class="form-control form-control-dark" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">المنطقة</label>
                    <input type="text" name="region" class="form-control form-control-dark">
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn-walim">إضافة</button>
            </div>
        </form>
    </div>
</div>
@endsection

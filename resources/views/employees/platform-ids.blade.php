@extends('layouts.app')
@section('title', 'IDs التطبيق — ' . ($employee->name_ar ?: $employee->name_en))
@section('page-title', 'إدارة IDs التطبيق')

@section('content')
<div class="row g-3">

    {{-- إضافة ID جديد --}}
    <div class="col-xl-4">
        <div class="chart-card fade-in">
            <div class="chart-title mb-3">➕ إضافة ID جديد</div>
            <div class="mb-3 p-3" style="background:var(--bg-hover);border-radius:10px;">
                <div style="font-size:0.8rem;color:var(--text-muted)">الموظف</div>
                <div class="fw-bold">{{ $employee->name_ar ?: $employee->name_en }}</div>
                <code style="color:var(--accent-light);font-size:0.8rem">{{ $employee->iqama_number }}</code>
            </div>
            <form method="POST" action="{{ route('employees.platform-ids', $employee) }}" id="addIdForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label-dark">التطبيق *</label>
                    <select name="platform_id" class="form-select form-select-dark" required>
                        <option value="">اختر التطبيق</option>
                        @foreach($platforms as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">Captain ID *</label>
                    <input type="number" name="captain_id" class="form-control form-control-dark"
                           placeholder="123456" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">اسم ID</label>
                    <input type="text" name="id_name" class="form-control form-control-dark"
                           placeholder="الاسم في التطبيق">
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">تاريخ البداية *</label>
                    <input type="date" name="start_date" class="form-control form-control-dark" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">تاريخ النهاية</label>
                    <input type="date" name="end_date" class="form-control form-control-dark">
                    <small class="text-muted">اتركه فارغاً إذا لا يزال فعّالاً</small>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">المدينة</label>
                    <input type="text" name="city" class="form-control form-control-dark" placeholder="الرياض">
                </div>
                <button type="submit" class="btn-walim w-100">
                    <i class="bi bi-plus-lg"></i> إضافة ID
                </button>
            </form>
        </div>
    </div>

    {{-- قائمة IDs --}}
    <div class="col-xl-8">
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div>
                    <div class="chart-title">IDs المسجّلة</div>
                    <div class="chart-subtitle">{{ $employee->platformIds->count() }} ID مسجّل</div>
                </div>
                <a href="{{ route('employees.show', $employee) }}" class="btn-ghost" style="font-size:0.8rem;padding:0.4rem 0.75rem">
                    <i class="bi bi-arrow-right"></i> رجوع للملف
                </a>
            </div>

            @if($employee->platformIds->isEmpty())
            <div class="text-center py-5" style="color:var(--text-muted)">
                <i class="bi bi-key" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
                لم يتم إضافة أي IDs بعد
            </div>
            @else
            <div class="table-responsive">
                <table class="table walim-table">
                    <thead>
                        <tr>
                            <th>التطبيق</th>
                            <th>Captain ID</th>
                            <th>اسم ID</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->platformIds as $pid)
                        <tr>
                            <td>
                                <span class="status-badge badge-done">{{ $pid->platform->name }}</span>
                            </td>
                            <td>
                                <code style="color:var(--accent-light);font-size:0.875rem">{{ $pid->captain_id }}</code>
                            </td>
                            <td>{{ $pid->id_name ?? '—' }}</td>
                            <td>{{ $pid->start_date?->format('Y/m/d') }}</td>
                            <td>{{ $pid->end_date ? $pid->end_date->format('Y/m/d') : '—' }}</td>
                            <td>
                                @if(!$pid->end_date || $pid->end_date >= now())
                                <span class="status-badge badge-active">فعّال</span>
                                @else
                                <span class="status-badge badge-loss">منتهي</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('employees.platform-ids', $employee) }}/{{ $pid->id }}"
                                      onsubmit="return confirm('حذف هذا ID؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn" style="width:28px;height:28px;color:var(--danger)">
                                        <i class="bi bi-trash" style="font-size:0.75rem"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

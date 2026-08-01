@extends('layouts.app')
@section('title', 'ملف ' . ($employee->name_ar ?: $employee->name_en))
@section('page-title', 'ملف الموظف')

@section('content')
<div class="row g-3">

    {{-- بطاقة الموظف --}}
    <div class="col-xl-4">
        <div class="chart-card fade-in text-center">
            <div style="width:72px;height:72px;background:var(--gradient-1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1rem;">
                🧑‍💼
            </div>
            <h5 class="fw-bold mb-1">{{ $employee->name_ar ?: $employee->name_en }}</h5>
            @if($employee->name_ar)
            <div class="text-muted mb-2" style="font-size:0.85rem">{{ $employee->name_en }}</div>
            @endif

            <div class="mb-3">
                <code style="background:var(--bg-hover);padding:4px 12px;border-radius:6px;color:var(--accent-light);font-size:0.85rem">
                    {{ $employee->iqama_number }}
                </code>
            </div>

            <span class="status-badge {{ $employee->employee_status === 'active' ? 'badge-active' : 'badge-loss' }} mb-3">
                {{ match($employee->employee_status) { 'active'=>'نشط','inactive'=>'غير نشط','suspended'=>'موقوف','resigned'=>'مستقيل',default=>$employee->employee_status } }}
            </span>

            <div class="d-flex gap-2 justify-content-center mt-3">
                <a href="{{ route('employees.edit', $employee) }}" class="btn-walim">
                    <i class="bi bi-pencil"></i> تعديل
                </a>
                <a href="{{ route('employees.platform-ids', $employee) }}" class="btn-ghost">
                    <i class="bi bi-key"></i> IDs
                </a>
            </div>
        </div>

        {{-- تفاصيل --}}
        <div class="chart-card fade-in mt-3">
            <div class="chart-title mb-3">📋 تفاصيل</div>
            @foreach([
                ['icon'=>'bi-building','label'=>'الفرع','value'=>$employee->branch?->name ?? '—'],
                ['icon'=>'bi-geo-alt','label'=>'المدينة','value'=>$employee->city ?? '—'],
                ['icon'=>'bi-shop','label'=>'التطبيق','value'=>$employee->platform?->name ?? '—'],
                ['icon'=>'bi-file-text','label'=>'نوع العقد','value'=>match($employee->contract_type){'salary'=>'راتب','commission'=>'عمولة','both'=>'راتب + عمولة',default=>$employee->contract_type}],
                ['icon'=>'bi-cash','label'=>'نظام الراتب','value'=>match($employee->salary_system){'fixed'=>'راتب ثابت','commission_tiered'=>'عمولة شرائح','hybrid'=>'مختلط',default=>$employee->salary_system}],
                ['icon'=>'bi-currency-dollar','label'=>'الراتب المتفق','value'=>number_format($employee->agreed_salary, 0).' ر.س'],
                ['icon'=>'bi-telephone','label'=>'الجوال','value'=>$employee->phone ?? '—'],
                ['icon'=>'bi-calendar','label'=>'تاريخ التوظيف','value'=>$employee->hire_date?->format('Y/m/d') ?? '—'],
            ] as $detail)
            <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                <div style="width:32px;height:32px;background:var(--bg-hover);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi {{ $detail['icon'] }}" style="color:var(--text-muted);font-size:0.9rem"></i>
                </div>
                <div style="flex:1">
                    <div style="font-size:0.7rem;color:var(--text-muted)">{{ $detail['label'] }}</div>
                    <div style="font-size:0.85rem;font-weight:600">{{ $detail['value'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- IDs والرواتب --}}
    <div class="col-xl-8">

        {{-- IDs التطبيق --}}
        <div class="chart-card fade-in mb-3">
            <div class="chart-header">
                <div class="chart-title">🔑 IDs التطبيق</div>
                <a href="{{ route('employees.platform-ids', $employee) }}" class="btn-ghost" style="font-size:0.8rem;padding:0.4rem 0.75rem">
                    إدارة <i class="bi bi-arrow-left"></i>
                </a>
            </div>
            @if($employee->platformIds->isEmpty())
            <p class="text-muted text-center py-2">لم يتم إضافة أي IDs بعد</p>
            @else
            <div class="table-responsive">
                <table class="table walim-table mb-0">
                    <thead><tr><th>التطبيق</th><th>Captain ID</th><th>اسم ID</th><th>من</th><th>إلى</th></tr></thead>
                    <tbody>
                        @foreach($employee->platformIds as $pid)
                        <tr>
                            <td>{{ $pid->platform->name }}</td>
                            <td><code style="color:var(--accent-light)">{{ $pid->captain_id }}</code></td>
                            <td>{{ $pid->id_name ?? '—' }}</td>
                            <td>{{ $pid->start_date?->format('Y/m/d') }}</td>
                            <td>{{ $pid->end_date ? $pid->end_date->format('Y/m/d') : '<span class="badge-active status-badge">فعّال</span>' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- آخر الرواتب --}}
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div class="chart-title">💰 آخر الرواتب</div>
            </div>
            @if($employee->payrollEntries->isEmpty())
            <p class="text-muted text-center py-3">لا توجد بيانات رواتب بعد</p>
            @else
            <div class="table-responsive">
                <table class="table walim-table mb-0">
                    <thead>
                        <tr><th>الشهر</th><th>الطلبات</th><th>الإيراد</th><th>الراتب الصافي</th><th>الباقي</th><th>الربحية</th></tr>
                    </thead>
                    <tbody>
                        @foreach($employee->payrollEntries as $entry)
                        <tr>
                            <td>{{ $entry->payrollPeriod?->month?->format('Y/m') }}</td>
                            <td>{{ number_format($entry->total_orders) }}</td>
                            <td>{{ number_format($entry->total_revenue, 0) }}</td>
                            <td>{{ number_format($entry->net_salary, 0) }}</td>
                            <td class="fw-bold">{{ number_format($entry->remaining_salary, 0) }}</td>
                            <td style="color:{{ $entry->profit_loss >= 0 ? 'var(--success)' : 'var(--danger)' }};font-weight:700">
                                {{ number_format($entry->profit_loss, 0) }}
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

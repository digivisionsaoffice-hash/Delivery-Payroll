@extends('layouts.app')
@section('title', 'استعراض بيانات الرفع')
@section('page-title', 'بيانات الاستيراد #' . $batch->id)

@section('content')
<div class="row g-3">
    <div class="col-12 mb-3">
        <a href="{{ route('import.index') }}" class="btn-ghost d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-right"></i> عودة لسجل الاستيرادات
        </a>
    </div>

    <div class="col-12">
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div class="chart-title">
                    تفاصيل الملف: <span style="color:var(--accent-light)">{{ $batch->file_name }}</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-4 mb-4 mt-2" style="background:var(--bg-hover);padding:1rem;border-radius:8px">
                <div><small class="text-muted d-block">المنصة</small> <span class="fw-bold">{{ $batch->platform->name ?? '—' }}</span></div>
                <div><small class="text-muted d-block">الشهر</small> <span class="fw-bold">{{ $batch->month->format('Y/m') }}</span></div>
                <div><small class="text-muted d-block">حالة الرفع</small> 
                    @if($batch->status === 'done')
                        <span class="text-success"><i class="bi bi-check-circle"></i> مكتمل</span>
                    @else
                        <span class="text-danger"><i class="bi bi-x-circle"></i> {{ $batch->status }}</span>
                    @endif
                </div>
                <div><small class="text-muted d-block">إجمالي المستورد</small> <span class="fw-bold text-success">{{ $batch->rows_imported }}</span></div>
                <div><small class="text-muted d-block">إجمالي الأخطاء</small> <span class="fw-bold text-danger">{{ $batch->rows_failed }}</span></div>
            </div>

            @if(!empty($batch->errors))
                <div class="alert-walim alert-danger mb-4" style="text-align: right;">
                    <h6 class="mb-2"><i class="bi bi-exclamation-octagon"></i> تفاصيل الأخطاء المكتشفة (أول 20 خطأ):</h6>
                    <ul class="mb-0" style="font-size:0.85rem; padding-right: 1.5rem;">
                        @foreach(is_array($batch->errors) ? $batch->errors : json_decode($batch->errors, true) as $error)
                            <li class="mb-1">
                                @if(isset($error['iqama'])) <strong style="color:var(--danger)">إقامة ({{ $error['iqama'] }})</strong>: @endif
                                {{ $error['message'] ?? 'خطأ غير معروف' }}
                                @if(isset($error['row'])) <br><code style="font-size:0.75rem; color:var(--text-muted)">{{ $error['row'] }}</code> @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($records->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size:3rem"></i>
                    <p class="mt-3 text-muted">لا يوجد أي بيانات صحيحة تم حفظها في قاعدة البيانات من هذا الملف.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table walim-table">
                        @if($batch->sheet_type === 'id_changes')
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>معرف التطبيق (ID)</th>
                                <th>تاريخ البدء</th>
                                <th>تاريخ الانتهاء</th>
                                <th>المدينة</th>
                                <th>ملاحظات ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $rec)
                            <tr>
                                <td>
                                    @if($rec->employee)
                                        <a href="{{ route('employees.show', $rec->employee_id) }}" class="text-decoration-none" style="color:var(--accent-light)">
                                            {{ $rec->employee->name_ar ?: $rec->employee->name_en }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><code>{{ $rec->captain_id }}</code></td>
                                <td>{{ \Carbon\Carbon::parse($rec->start_date)->format('Y-m-d') }}</td>
                                <td>{{ $rec->end_date ? \Carbon\Carbon::parse($rec->end_date)->format('Y-m-d') : '—' }}</td>
                                <td>{{ $rec->city ?? '—' }}</td>
                                <td>{{ $rec->id_name ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        @elseif($batch->sheet_type === 'employees')
                        <thead>
                            <tr>
                                <th>الإقامة</th>
                                <th>اسم الموظف</th>
                                <th>نوع العقد</th>
                                <th>الراتب الأساسي</th>
                                <th>نظام الراتب</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $rec)
                            <tr>
                                <td><code>{{ $rec->iqama_number }}</code></td>
                                <td>
                                    <a href="{{ route('employees.show', $rec->id) }}" class="text-decoration-none" style="color:var(--accent-light)">
                                        {{ $rec->name_ar ?: $rec->name_en }}
                                    </a>
                                </td>
                                <td>{{ $rec->contract_type ?? '—' }}</td>
                                <td>{{ number_format($rec->agreed_salary, 2) }}</td>
                                <td>{{ $rec->salary_system === 'fixed' ? 'ثابت' : 'عمولة' }}</td>
                                <td>
                                    @if($rec->employee_status === 'active')
                                        <span class="text-success">نشط</span>
                                    @elseif($rec->employee_status === 'inactive')
                                        <span class="text-danger">غير نشط</span>
                                    @else
                                        <span class="text-warning">موقوف</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @else
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>معرف التطبيق</th>
                                <th>اسم السائق</th>
                                <th>الموظف المربوط</th>
                                <th>الطلبات</th>
                                <th>المدفوع (صافي)</th>
                                <th>الاستقطاع/التسويات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $rec)
                            <tr>
                                <td>{{ $rec->record_date->format('Y-m-d') }}</td>
                                <td><code>{{ $rec->captain_id }}</code></td>
                                <td>{{ $rec->captain_name ?? '—' }}</td>
                                <td>
                                    @if($rec->employee)
                                        <a href="{{ route('employees.show', $rec->employee_id) }}" class="text-decoration-none" style="color:var(--accent-light)">
                                            {{ $rec->employee->name_ar ?: $rec->employee->name_en }}
                                        </a>
                                    @else
                                        <span class="text-muted">غير مربوط</span>
                                    @endif
                                </td>
                                <td>{{ $rec->orders }}</td>
                                <td>{{ number_format($rec->suppliers_costs, 2) }}</td>
                                <td>
                                    @if($rec->adjustments < 0)
                                        <span class="text-danger">{{ number_format($rec->adjustments, 2) }}</span>
                                    @else
                                        {{ number_format($rec->adjustments, 2) }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @endif
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

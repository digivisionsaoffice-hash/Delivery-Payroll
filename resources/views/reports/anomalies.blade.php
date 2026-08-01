@extends('layouts.app')

@section('title', 'تقرير الحالات الشاذة')
@section('page-title', 'تقرير الحالات الشاذة (Anomalies Report)')

@section('content')
<div class="row g-3">
    <div class="col-12 mb-3">
        <form action="{{ route('reports.anomalies') }}" method="GET" class="d-flex gap-2 w-25">
            <input type="month" name="month" class="form-control form-control-dark" value="{{ $month }}" onchange="this.form.submit()">
        </form>
    </div>

    {{-- 1. Multiple Rows per Captain ID --}}
    <div class="col-xl-6">
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div class="chart-title"><i class="bi bi-exclamation-triangle text-warning"></i> معرفات مكررة في نفس اليوم (في التقرير الأصلي)</div>
            </div>
            <div class="p-3">
                <small class="text-muted d-block mb-3">
                    هذه الحالات تحدث عندما يُصدر "نينجا" أداء المندوب على أكثر من سطر في نفس اليوم (مثلاً: دوام فترتين).
                    <br>
                    <strong class="text-info">ملاحظة:</strong> النظام يجمع هذه السطور تلقائياً ليعطي المندوب حقه كاملاً في التارجت اليومي!
                </small>
                
                @if($multipleRowsPerDay->isEmpty())
                    <div class="text-center py-4 text-muted">لا يوجد حالات شاذة من هذا النوع في هذا الشهر.</div>
                @else
                    <div class="table-responsive">
                        <table class="table walim-table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>رقم الكابتن</th>
                                    <th>التكرار</th>
                                    <th>تفاصيل الطلبات والساعات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($multipleRowsPerDay as $anomaly)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($anomaly->record_date)->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $anomaly->captain_id }}</span>
                                    </td>
                                    <td><span class="badge bg-danger">{{ $anomaly->rows_count }} أسطر</span></td>
                                    <td>
                                        <ul class="list-unstyled mb-0" style="font-size:0.8rem;">
                                            @foreach($anomaly->records as $record)
                                                <li>- <strong>{{ $record->orders }}</strong> طلب | <strong>{{ $record->working_hours }}</strong> ساعة</li>
                                            @endforeach
                                        </ul>
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

    {{-- 2. Multiple Captain IDs per Employee --}}
    <div class="col-xl-6">
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div class="chart-title"><i class="bi bi-people text-danger"></i> موظف يعمل بأكثر من يوزر (ID) في نفس اليوم</div>
            </div>
            <div class="p-3">
                <small class="text-muted d-block mb-3">
                    هذه الحالات تحدث عندما يقوم موظف واحد بالعمل باستخدام أكثر من رقم آي دي مختلف في نفس اليوم.
                </small>

                @if($multipleIdsPerDay->isEmpty())
                    <div class="text-center py-4 text-muted">لا يوجد حالات شاذة من هذا النوع في هذا الشهر.</div>
                @else
                    <div class="table-responsive">
                        <table class="table walim-table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الموظف</th>
                                    <th>عدد الآي دي هات</th>
                                    <th>تفاصيل المعرفات والطلبات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($multipleIdsPerDay as $anomaly)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($anomaly->record_date)->format('Y-m-d') }}</td>
                                    <td>
                                        {{ $anomaly->employee->name_en ?? 'بدون اسم' }}
                                        <br>
                                        <small class="text-muted">{{ $anomaly->employee->iqama_number }}</small>
                                    </td>
                                    <td><span class="badge bg-warning text-dark">{{ $anomaly->ids_count }} IDs</span></td>
                                    <td>
                                        <ul class="list-unstyled mb-0" style="font-size:0.8rem;">
                                            @foreach($anomaly->records as $record)
                                                <li>- ID: <strong class="text-primary">{{ $record->captain_id }}</strong> | <strong>{{ $record->orders }}</strong> طلب | <strong>{{ $record->working_hours }}</strong> ساعة</li>
                                            @endforeach
                                        </ul>
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
</div>
@endsection

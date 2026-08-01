@extends('layouts.app')
@section('title', 'الموظفون والسائقون')
@section('page-title', 'الموظفون والسائقون')

@section('content')

    {{-- Modal الاستيراد --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" class="modal-content" style="background:var(--bg-dark);border:1px solid var(--border-color)">
                @csrf
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title">استيراد الموظفين من Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert text-light mb-3" style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2)">
                        قم بتنزيل القالب، املأ البيانات، ثم ارفعه هنا. سيقوم النظام بإضافة الموظفين الجدد أو تحديث بيانات الموظفين الحاليين (عن طريق رقم الإقامة).
                    </div>
                    <a href="{{ route('employees.template') }}" class="btn-ghost mb-4 w-100 d-flex justify-content-center">
                        <i class="bi bi-download me-2"></i> تنزيل قالب Excel للموظفين
                    </a>
                    
                    <label class="form-label">ملف Excel (xlsx, csv)</label>
                    <input type="file" name="file" class="form-control-dark form-control mb-3" accept=".xlsx,.xls,.csv" required>

                    <div class="mt-3 p-3 rounded" style="background: var(--bg-card); border: 1px solid var(--border-color);">
                        <div class="fw-bold mb-2">خيارات الرفع:</div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing" checked value="1">
                            <label class="form-check-label text-light" for="update_existing">تحديث بيانات الموظفين الحاليين</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="add_new" name="add_new" checked value="1">
                            <label class="form-check-label text-light" for="add_new">إضافة موظفين جدد (غير موجودين في النظام)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn-walim"><i class="bi bi-upload"></i> رفع واستيراد</button>
                </div>
            </form>
        </div>
    </div>{{-- Stats --}}
@if(session('last_batch_id'))
    @php
        $lastBatch = \App\Models\ImportBatch::find(session('last_batch_id'));
    @endphp
    @if($lastBatch && !empty($lastBatch->errors))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="background:var(--bg-secondary); border-color:var(--warning); color:var(--text-primary)">
            <h5 class="alert-heading text-warning"><i class="bi bi-exclamation-triangle-fill"></i> تفاصيل السطور التي لم يتم استيرادها ({{ count($lastBatch->errors) }} سطر)</h5>
            <p>هذه الأسطر لم تُحفظ إما لأنها (مكررة ولا يوجد بها أي بيانات جديدة) أو لوجود بيانات ناقصة. إليك التفاصيل:</p>
            <div style="max-height: 200px; overflow-y: auto; background: var(--bg-dark); padding: 10px; border-radius: 5px;">
                <ul class="mb-0 text-muted" style="font-size: 0.85rem; font-family: monospace;">
                    @foreach(array_slice($lastBatch->errors, 0, 50) as $err)
                        <li class="mb-1"><strong>السطر {{ $err['row'] ?? '?' }}:</strong> {{ $err['message'] ?? 'خطأ غير معروف' }}</li>
                    @endforeach
                    @if(count($lastBatch->errors) > 50)
                        <li class="mt-2 text-warning">... بالإضافة إلى {{ count($lastBatch->errors) - 50 }} خطأ آخر (تم عرض أول 50 سطر فقط).</li>
                    @endif
                </ul>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'إجمالي الموظفين','value'=>$stats['total'],'icon'=>'bi-people-fill','color'=>'kpi-blue'],
        ['label'=>'نشط','value'=>$stats['active'],'icon'=>'bi-check-circle-fill','color'=>'kpi-green'],
        ['label'=>'راتب ثابت','value'=>$stats['fixed'],'icon'=>'bi-cash','color'=>'kpi-yellow'],
        ['label'=>'عمولة','value'=>$stats['commission'],'icon'=>'bi-percent','color'=>'kpi-red'],
    ] as $i => $s)
    <div class="col-xl-3 col-6 fade-in fade-in-{{ $i+1 }}">
        <div class="kpi-card {{ $s['color'] }}">
            <div class="kpi-icon"><i class="bi {{ $s['icon'] }}"></i></div>
            <div class="kpi-value">{{ $s['value'] }}</div>
            <div class="kpi-label">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters + Table --}}
<div class="chart-card fade-in">
    <div class="chart-header flex-wrap gap-2">
        <div class="chart-title">قائمة الموظفين</div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                <input type="text" name="search" class="form-control-dark form-control" style="width:200px"
                       placeholder="ابحث بالاسم أو الإقامة..." value="{{ request('search') }}">
                <select name="status" class="form-select-dark form-select" style="width:140px">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(request('status')=='active')>نشط</option>
                    <option value="inactive" @selected(request('status')=='inactive')>غير نشط</option>
                    <option value="suspended" @selected(request('status')=='suspended')>موقوف</option>
                </select>
                <select name="branch_id" class="form-select-dark form-select" style="width:150px">
                    <option value="">كل الفروع</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="platform_id" class="form-select-dark form-select" style="width:140px">
                    <option value="">كل التطبيقات</option>
                    @foreach($platforms as $p)
                    <option value="{{ $p->id }}" @selected(request('platform_id')==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <select name="salary_system" class="form-select-dark form-select" style="width:140px">
                    <option value="">كل الأنظمة</option>
                    <option value="fixed" @selected(request('salary_system')=='fixed')>راتب ثابت</option>
                    <option value="commission_tiered" @selected(request('salary_system')=='commission_tiered')>عمولة</option>
                </select>
                <button type="submit" class="btn-walim">
                    <i class="bi bi-search"></i> بحث
                </button>
                @if(request()->hasAny(['search','status','branch_id','platform_id','salary_system']))
                <a href="{{ route('employees.index') }}" class="btn-ghost">مسح</a>
                @endif
            </form>
            <div class="ms-auto d-flex gap-2">
                <form action="{{ route('employees.destroy_all') }}" method="POST" onsubmit="return confirm('⚠️ تحذير خطير: هل أنت متأكد من حذف جميع بيانات الموظفين والمناديب؟\nهذا الإجراء لا يمكن التراجع عنه وسيحذف جميع سجلاتهم من النظام!');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger h-100" style="border-radius: 8px; padding: 0.5rem 1rem;">
                        <i class="bi bi-trash"></i> حذف الجميع
                    </button>
                </form>
                <a href="{{ route('employees.export', request()->query()) }}" class="btn-ghost" style="border: 1px solid var(--accent); color: var(--accent);">
                    <i class="bi bi-file-earmark-excel"></i> تصدير للبحث (Excel)
                </a>
                <button type="button" class="btn-ghost" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-file-earmark-excel"></i> استيراد (Excel)
                </button>
                <a href="{{ route('employees.create') }}" class="btn-walim">
                    <i class="bi bi-plus-lg"></i> إضافة موظف
                </a>
            </div>
        </div>
    </div>



    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>رقم الإقامة</th>
                    <th>الاسم</th>
                    <th>الفرع / المدينة</th>
                    <th>نظام الراتب</th>
                    <th>الراتب المتفق</th>
                    <th>التطبيق</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td>
                        <code style="background:var(--bg-hover);padding:3px 8px;border-radius:5px;font-size:0.8rem;color:var(--accent-light)">
                            {{ $emp->iqama_number }}
                        </code>
                    </td>
                    <td>
                        <div class="fw-600">{{ $emp->name_ar ?: $emp->name_en }}</div>
                        @if($emp->name_ar) <small class="text-muted">{{ $emp->name_en }}</small> @endif
                    </td>
                    <td>
                        <div>{{ $emp->branch?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $emp->city }}</small>
                    </td>
                    <td>
                        <span class="status-badge {{ $emp->salary_system === 'fixed' ? 'badge-done' : 'badge-warning' }}"
                              style="{{ $emp->salary_system !== 'fixed' ? 'background:rgba(245,158,11,0.15);color:#f59e0b' : '' }}">
                            {{ match($emp->salary_system) {
                                'fixed' => 'راتب ثابت',
                                'commission_tiered' => 'عمولة',
                                'hybrid' => 'مختلط',
                                default => $emp->salary_system
                            } }}
                        </span>
                    </td>
                    <td class="fw-600">{{ number_format($emp->agreed_salary, 0) }} <small class="text-muted">ر.س</small></td>
                    <td>{{ $emp->platform?->name ?? '—' }}</td>
                    <td>
                        <span class="status-badge {{ match($emp->employee_status) {
                            'active' => 'badge-active',
                            'inactive','suspended' => 'badge-loss',
                            default => 'badge-neutral'
                        } }}">
                            {{ match($emp->employee_status) {
                                'active' => 'نشط', 'inactive' => 'غير نشط',
                                'suspended' => 'موقوف', 'resigned' => 'مستقيل', default => $emp->employee_status
                            } }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('employees.show', $emp) }}" class="icon-btn" title="عرض" style="width:30px;height:30px">
                                <i class="bi bi-eye" style="font-size:0.8rem"></i>
                            </a>
                            <a href="{{ route('employees.edit', $emp) }}" class="icon-btn" title="تعديل" style="width:30px;height:30px">
                                <i class="bi bi-pencil" style="font-size:0.8rem"></i>
                            </a>
                            <a href="{{ route('employees.platform-ids', $emp) }}" class="icon-btn" title="IDs التطبيق" style="width:30px;height:30px">
                                <i class="bi bi-key" style="font-size:0.8rem"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div style="color:var(--text-muted)">
                            <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
                            لا يوجد موظفون. <a href="{{ route('employees.create') }}" style="color:var(--accent-light)">أضف أول موظف</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">عرض {{ $employees->firstItem() }}–{{ $employees->lastItem() }} من {{ $employees->total() }} موظف</small>
        {{ $employees->links() }}
    </div>
@endsection

@extends('layouts.app')
@section('title', 'تعديل ' . ($employee->name_ar ?: $employee->name_en))
@section('page-title', 'تعديل بيانات الموظف')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <div class="chart-card fade-in">
                        <div class="chart-title mb-3">🪪 بيانات الهوية</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-dark">رقم الإقامة *</label>
                                <input type="text" name="iqama_number" class="form-control form-control-dark"
                                       value="{{ old('iqama_number', $employee->iqama_number) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الاسم بالإنجليزي *</label>
                                <input type="text" name="name_en" class="form-control form-control-dark"
                                       value="{{ old('name_en', $employee->name_en) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الاسم بالعربي</label>
                                <input type="text" name="name_ar" class="form-control form-control-dark"
                                       value="{{ old('name_ar', $employee->name_ar) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الجنسية</label>
                                <input type="text" name="nationality" class="form-control form-control-dark"
                                       value="{{ old('nationality', $employee->nationality) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">رقم الجوال</label>
                                <input type="text" name="phone" class="form-control form-control-dark"
                                       value="{{ old('phone', $employee->phone) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">تاريخ التوظيف</label>
                                <input type="date" name="hire_date" class="form-control form-control-dark"
                                       value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="chart-card fade-in fade-in-2">
                        <div class="chart-title mb-3">💼 بيانات العمل</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-dark">الفرع</label>
                                <select name="branch_id" class="form-select form-select-dark">
                                    <option value="">اختر الفرع</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(old('branch_id',$employee->branch_id)==$b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">المدينة</label>
                                <select name="city" class="form-select form-select-dark">
                                    <option value="">اختر المدينة</option>
                                    @foreach($cities as $c)
                                    <option value="{{ $c->name }}" @selected(old('city', $employee->city) == $c->name)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">التطبيق الحالي</label>
                                <select name="platform_id" class="form-select form-select-dark">
                                    <option value="">اختر التطبيق</option>
                                    @foreach($platforms as $p)
                                    <option value="{{ $p->id }}" @selected(old('platform_id',$employee->platform_id)==$p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">نوع العقد *</label>
                                <select name="contract_type" class="form-select form-select-dark" required>
                                    <option value="salary" @selected(old('contract_type',$employee->contract_type)=='salary')>راتب</option>
                                    <option value="commission" @selected(old('contract_type',$employee->contract_type)=='commission')>عمولة</option>
                                    <option value="both" @selected(old('contract_type',$employee->contract_type)=='both')>راتب + عمولة</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">نظام الراتب *</label>
                                <select name="salary_system" class="form-select form-select-dark" required>
                                    <option value="fixed" @selected(old('salary_system',$employee->salary_system)=='fixed')>راتب ثابت مشروط</option>
                                    <option value="commission_tiered" @selected(old('salary_system',$employee->salary_system)=='commission_tiered')>عمولة بالشرائح</option>
                                    <option value="hybrid" @selected(old('salary_system',$employee->salary_system)=='hybrid')>مختلط</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الراتب المتفق (ر.س)</label>
                                <input type="number" name="agreed_salary" class="form-control form-control-dark"
                                       value="{{ old('agreed_salary', $employee->agreed_salary) }}" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الحالة الوظيفية *</label>
                                <select name="employee_status" class="form-select form-select-dark" required>
                                    <option value="active" @selected(old('employee_status',$employee->employee_status)=='active')>نشط ✅</option>
                                    <option value="inactive" @selected(old('employee_status',$employee->employee_status)=='inactive')>غير نشط</option>
                                    <option value="suspended" @selected(old('employee_status',$employee->employee_status)=='suspended')>موقوف</option>
                                    <option value="resigned" @selected(old('employee_status',$employee->employee_status)=='resigned')>مستقيل</option>
                                </select>
                            </div>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end fade-in fade-in-3">
                    <a href="{{ route('employees.show', $employee) }}" class="btn-ghost">إلغاء</a>
                    <button type="submit" class="btn-walim"><i class="bi bi-check-lg"></i> حفظ التعديلات</button>
                </div>
            </div>
        </form>

        <div class="mt-3 text-start">
            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-ghost" style="color:var(--danger);border-color:rgba(239,68,68,0.3)">
                    <i class="bi bi-trash"></i> حذف الموظف
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

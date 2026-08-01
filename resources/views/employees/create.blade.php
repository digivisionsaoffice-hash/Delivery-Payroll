@extends('layouts.app')
@section('title', 'إضافة موظف جديد')
@section('page-title', 'إضافة موظف جديد')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf
            <div class="row g-3">

                {{-- بيانات الهوية --}}
                <div class="col-12">
                    <div class="chart-card fade-in">
                        <div class="chart-title mb-3">🪪 بيانات الهوية</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-dark">رقم الإقامة <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="iqama_number" class="form-control form-control-dark @error('iqama_number') border-danger @enderror"
                                       value="{{ old('iqama_number') }}" placeholder="1234567890" required>
                                @error('iqama_number') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الاسم بالإنجليزي <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="name_en" class="form-control form-control-dark @error('name_en') border-danger @enderror"
                                       value="{{ old('name_en') }}" placeholder="Ahmed Ali" required>
                                @error('name_en') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الاسم بالعربي</label>
                                <input type="text" name="name_ar" class="form-control form-control-dark"
                                       value="{{ old('name_ar') }}" placeholder="أحمد علي">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الجنسية</label>
                                <input type="text" name="nationality" class="form-control form-control-dark"
                                       value="{{ old('nationality') }}" placeholder="سعودي / يمني / ...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">رقم الجوال</label>
                                <input type="text" name="phone" class="form-control form-control-dark"
                                       value="{{ old('phone') }}" placeholder="05xxxxxxxx">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">تاريخ التوظيف</label>
                                <input type="date" name="hire_date" class="form-control form-control-dark"
                                       value="{{ old('hire_date') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- بيانات العمل --}}
                <div class="col-12">
                    <div class="chart-card fade-in fade-in-2">
                        <div class="chart-title mb-3">💼 بيانات العمل</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-dark">الفرع</label>
                                <select name="branch_id" class="form-select form-select-dark">
                                    <option value="">اختر الفرع</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">المدينة</label>
                                <select name="city" class="form-select form-select-dark">
                                    <option value="">اختر المدينة</option>
                                    @foreach($cities as $c)
                                    <option value="{{ $c->name }}" @selected(old('city') == $c->name)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">التطبيق الحالي</label>
                                <select name="platform_id" class="form-select form-select-dark">
                                    <option value="">اختر التطبيق</option>
                                    @foreach($platforms as $p)
                                    <option value="{{ $p->id }}" @selected(old('platform_id') == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">نوع العقد <span style="color:var(--danger)">*</span></label>
                                <select name="contract_type" class="form-select form-select-dark" required>
                                    <option value="salary" @selected(old('contract_type','salary')=='salary')>راتب</option>
                                    <option value="commission" @selected(old('contract_type')=='commission')>عمولة</option>
                                    <option value="both" @selected(old('contract_type')=='both')>راتب + عمولة</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">نظام الراتب <span style="color:var(--danger)">*</span></label>
                                <select name="salary_system" id="salary_system" class="form-select form-select-dark" required>
                                    <option value="fixed" @selected(old('salary_system','fixed')=='fixed')>راتب ثابت مشروط</option>
                                    <option value="commission_tiered" @selected(old('salary_system')=='commission_tiered')>عمولة بالشرائح (8 ريال)</option>
                                    <option value="hybrid" @selected(old('salary_system')=='hybrid')>مختلط</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الراتب المتفق عليه (ر.س)</label>
                                <input type="number" name="agreed_salary" class="form-control form-control-dark"
                                       value="{{ old('agreed_salary') }}" placeholder="2500" step="0.01" min="0">
                                <small class="text-muted">للعمولة: يُترك فارغاً أو قيمة الشريحة الكاملة</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-dark">الحالة الوظيفية <span style="color:var(--danger)">*</span></label>
                                <select name="employee_status" class="form-select form-select-dark" required>
                                    <option value="active" @selected(old('employee_status','active')=='active')>نشط ✅</option>
                                    <option value="inactive" @selected(old('employee_status')=='inactive')>غير نشط</option>
                                    <option value="suspended" @selected(old('employee_status')=='suspended')>موقوف</option>
                                    <option value="resigned" @selected(old('employee_status')=='resigned')>مستقيل</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- أزرار --}}
                <div class="col-12 d-flex gap-2 justify-content-end fade-in fade-in-3">
                    <a href="{{ route('employees.index') }}" class="btn-ghost">
                        <i class="bi bi-x"></i> إلغاء
                    </a>
                    <button type="submit" class="btn-walim">
                        <i class="bi bi-check-lg"></i> حفظ الموظف
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($errors->has('iqama_number'))
            Swal.fire({
                icon: 'error',
                title: 'تكرار موظف!',
                text: '{!! $errors->first("iqama_number") !!}',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#0052cc'
            });
        @endif
    });
</script>
@endpush
@endsection

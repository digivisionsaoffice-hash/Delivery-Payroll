@extends('layouts.app')
@section('title', 'اعدادات ' . $platform->name)
@section('page-title', 'اعدادات منصة: ' . $platform->name)

@section('content')
<div class="row g-3">

    {{-- ضبط شهر جديد --}}
    @php
        $latestSetting = $platform->settings->sortByDesc('month')->first();
        $defaultMonth = $latestSetting ? \Carbon\Carbon::parse($latestSetting->month)->addMonth()->format('Y-m') : date('Y-m');
        $defaultAppName = $latestSetting->app_name ?? $platform->name;
        $isKeetaSlabs = ($platform->report_format === 'keeta_slabs');

        // keeta slabs defaults
        $ksCfg = $latestSetting?->keeta_slabs_config ?? [];

        // ninja defaults
        $defaultDailyTarget = $latestSetting->daily_target ?? 0;
        $defaultBasicSalary = $latestSetting->basic_salary ?? '';
        $defaultTargetWorkingDays = $latestSetting->target_working_days ?? 26;
        $defaultAbsenceType = $latestSetting->absence_deduction_type ?? 'worked_days_only';
        $defaultAbsenceRate = $latestSetting->absence_deduction_rate ?? 1.0;
        $defaultExtraBonusRate = $latestSetting->extra_day_bonus_rate ?? 1.0;
        $defaultBonus = $latestSetting->bonus_per_excess_order ?? 0;
        $defaultHours = $latestSetting->min_working_hours_per_day ?? 7;
        $defaultLinkTargetToHours = $latestSetting->link_target_to_hours ?? false;
    @endphp

    <div class="col-xl-5">
        <div class="chart-card fade-in">
            <div class="chart-title mb-4">⚙️ ضبط شهر جديد (نسخ من الاعدادات السابقة)</div>
            <form method="POST" action="{{ route('platforms.settings.store', $platform) }}">
                @csrf
                {{-- حقل مخفي لنوع الحساب --}}
                <input type="hidden" name="calc_mode" value="{{ $isKeetaSlabs ? 'keeta_slabs' : 'ninja' }}">

                <div class="mb-3">
                    <label class="form-label-dark">الشهر *</label>
                    <input type="month" name="month" class="form-control form-control-dark" value="{{ $defaultMonth }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">اسم التطبيق في التقرير</label>
                    <input type="text" name="app_name" class="form-control form-control-dark" placeholder="{{ $platform->name }}" value="{{ $defaultAppName }}">
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">الحد الادنى لساعات العمل (يوم دوام واحد) *</label>
                    <input type="number" name="min_working_hours_per_day" class="form-control form-control-dark"
                           placeholder="7" step="0.5" value="{{ $defaultHours }}" required min="1" max="24">
                </div>

                {{-- ======================================================= --}}
                {{--        نينجا: الراتب الثابت + التارجت                      --}}
                {{-- ======================================================= --}}
                @if(!$isKeetaSlabs)
                <hr style="border-color:var(--border);margin:1.25rem 0">
                <div class="mb-2" style="font-size:0.8rem;font-weight:700;color:var(--accent)">
                    ⚡ اعدادات الراتب الثابت
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">الراتب الاساسي للتطبيق (اختياري)</label>
                    <input type="number" name="basic_salary" class="form-control form-control-dark" placeholder="مثال: 3000" value="{{ $defaultBasicSalary }}" min="0" step="0.01">
                    <small class="text-muted">اذا تم تعيينه سيعتمد كراتب اساسي بدلاً من الراتب المتفق عليه للموظف.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">ايام العمل المستهدفة لاستحقاق الراتب الكامل *</label>
                    <input type="number" name="target_working_days" class="form-control form-control-dark" placeholder="26" value="{{ $defaultTargetWorkingDays }}" required min="1">
                    <small class="text-muted">اذا عمل الموظف اياما اكثر، يحسب له يومية اضافية.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">معادلة احتساب الراتب الاساسي والخصومات *</label>
                    <select name="absence_deduction_type" class="form-select form-control-dark">
                        <option value="worked_days_only" {{ $defaultAbsenceType == 'worked_days_only' ? 'selected' : '' }}>1. نظام الشركة الحالي: راتب كامل عند المستهدف فاكثر، وفي حال النقص (الراتب ÷ 30 × ايام العمل)</option>
                        <option value="strict_daily_unless_exceeded" {{ $defaultAbsenceType == 'strict_daily_unless_exceeded' ? 'selected' : '' }}>2. الدفع بالايام: راتب كامل فقط عند تجاوز المستهدف، وعند المستهدف او اقل (الراتب ÷ 30 × ايام)</option>
                        <option value="standard_deduction" {{ $defaultAbsenceType == 'standard_deduction' ? 'selected' : '' }}>3. النظام القياسي: راتب كامل عند تحقيق المستهدف وفي حال النقص يخصم (ايام الغياب × مضاعف الخصم)</option>
                        <option value="pure_daily" {{ $defaultAbsenceType == 'pure_daily' ? 'selected' : '' }}>4. نظام المياومة البحت: الدفع دائما (الراتب ÷ 30 × ايام العمل) بدون بونص التارجت</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label-dark">مضاعف خصم الغياب *</label>
                        <input type="number" name="absence_deduction_rate" class="form-control form-control-dark" placeholder="1.0" value="{{ $defaultAbsenceRate }}" required min="0" step="0.1">
                        <small class="text-muted">معدل خصم اليوم الغائب</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label-dark">مضاعف العمل الاضافي *</label>
                        <input type="number" name="extra_day_bonus_rate" class="form-control form-control-dark" placeholder="1.0" value="{{ $defaultExtraBonusRate }}" required min="0" step="0.1">
                        <small class="text-muted">معدل بونص اليوم الاضافي</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">التارجت اليومي (عدد الطلبيات)</label>
                    <input type="number" name="daily_target" class="form-control form-control-dark" placeholder="20" value="{{ $defaultDailyTarget }}" required min="0">
                    <small class="text-muted">الزيادة فوق هذا الرقم × قيمة الحافز = البونص اليومي</small>
                </div>
                <div class="mb-3">
                    <label class="form-label-dark">قيمة الحافز لكل طلبية زائدة (ر.س) *</label>
                    <input type="number" name="bonus_per_excess_order" class="form-control form-control-dark"
                           placeholder="2.5" step="0.01" value="{{ $defaultBonus }}" required min="0">
                </div>
                <div class="mb-3 form-check form-switch" style="background: var(--bg-hover); padding: 1rem 1rem 1rem 3rem; border-radius: 8px;">
                    <input class="form-check-input" type="checkbox" role="switch" id="link_target_to_hours" name="link_target_to_hours" value="1" {{ $defaultLinkTargetToHours ? 'checked' : '' }}>
                    <label class="form-check-label form-label-dark ms-2 mb-0" for="link_target_to_hours">ربط بونص التارجت باكمال ساعات العمل</label>
                    <div class="text-muted mt-1" style="font-size: 0.75rem;">اذا تم التفعيل، لن يحصل الموظف على التارجت الاضافي في الايام التي لم يكمل فيها ساعات العمل.</div>
                </div>
                @else
                {{-- ======================================================= --}}
                {{--        كيتا شرايح: نظام مخصص                              --}}
                {{-- ======================================================= --}}
                {{-- حقول وهمية لتجاوز الـ validation (قيم افتراضية) --}}
                <input type="hidden" name="daily_target" value="0">
                <input type="hidden" name="target_working_days" value="26">
                <input type="hidden" name="absence_deduction_type" value="worked_days_only">
                <input type="hidden" name="absence_deduction_rate" value="1">
                <input type="hidden" name="extra_day_bonus_rate" value="1">
                <input type="hidden" name="bonus_per_excess_order" value="0">

                <hr style="border-color:var(--border);margin:1.25rem 0">
                <div class="mb-2" style="font-size:0.8rem;font-weight:700;color:var(--success)">
                    📊 نظام كيتا شرايح
                </div>

                <div class="row">
                    <div class="col-12 mb-3" id="baseSalaryDiv">
                        <label class="form-label-dark">الراتب الأساسي الافتراضي (ر.س)</label>
                        <input type="number" name="ks[base_salary_value]" class="form-control form-control-dark" placeholder="2500" value="{{ $ksCfg['base_salary_value'] ?? 2500 }}" min="0" step="1">
                        <small class="text-muted" style="display:block;margin-top:0.4rem">
                            <i class="bi bi-info-circle"></i> يتم الاعتماد كلياً على بيانات الموظف (نظام 8، 7، أو راتب ثابت). وإذا لم يكن مسجلاً له راتب، سيتم احتساب هذا الراتب الأساسي الافتراضي له.
                        </small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-dark">الحد الادنى للطلبات (تفعيل الراتب) *</label>
                    <input type="number" name="ks[base_min_orders]" class="form-control form-control-dark" placeholder="450" value="{{ $ksCfg['base_min_orders'] ?? 450 }}" min="0" required>
                    <small class="text-muted">عند الوصول لهذا العدد أو تجاوزه يفعّل الراتب الثابت / نظام 8</small>
                </div>

                <hr style="border-color:var(--border);margin:1.25rem 0">
                <div class="mb-2" style="font-size:0.8rem;font-weight:700;color:var(--accent)">
                    📶 شرائح العمولة الاساسية (ما دون الحد الادنى)
                </div>
                @php
                    $defaultKsTiers = $ksCfg['tiers'] ?? [
                        ['from'=>0,'to'=>200,'rate'=>3],
                        ['from'=>200,'to'=>350,'rate'=>3],
                        ['from'=>350,'to'=>449,'rate'=>4],
                    ];
                @endphp
                <div id="tiersContainer">
                    @foreach($defaultKsTiers as $ti => $tier)
                    <div class="tier-row row g-2 align-items-end mb-2" data-index="{{ $ti }}">
                        <div class="col-3">
                            <label class="form-label-dark" style="font-size:0.75rem">من (طلب)</label>
                            <input type="number" name="ks[tiers][{{ $ti }}][from]" class="form-control form-control-dark" value="{{ $tier['from'] }}" min="0" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label-dark" style="font-size:0.75rem">الى (طلب)</label>
                            <input type="number" name="ks[tiers][{{ $ti }}][to]" class="form-control form-control-dark" value="{{ $tier['to'] }}" min="0" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label-dark" style="font-size:0.75rem">السعر (ر.س)</label>
                            <input type="number" name="ks[tiers][{{ $ti }}][rate]" class="form-control form-control-dark" value="{{ $tier['rate'] }}" min="0" step="0.01" required>
                        </div>
                        <div class="col-3">
                            @if($ti > 0)
                            <button type="button" class="btn-ghost remove-tier" style="color:var(--danger);padding:0.4rem 0.6rem">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="addTierBtn" class="btn-ghost mb-3" style="font-size:0.8rem">
                    <i class="bi bi-plus-circle"></i> اضافة شريحة
                </button>

                <hr style="border-color:var(--border);margin:1.25rem 0">
                <div class="mb-2" style="font-size:0.8rem;font-weight:700;color:var(--warning)">
                    🎯 حوافز الدرجات (Grade Incentives)
                </div>
                <div class="mb-2" style="background:rgba(255,193,7,0.08);border:1px solid rgba(255,193,7,0.2);border-radius:8px;padding:0.7rem 0.9rem;font-size:0.75rem;color:var(--text-muted)">
                    <i class="bi bi-info-circle"></i>
                    <strong>تعدد المدن:</strong> يمكنك كتابة سعر مختلف لكل مدينة في حقل الحافز بصيغة:
                    <code style="background:rgba(0,0,0,0.2);padding:0.1rem 0.4rem;border-radius:4px">جدة:7, الطائف:6, الافتراضي:5</code><br>
                    أو رقم واحد للجميع مثل: <code style="background:rgba(0,0,0,0.2);padding:0.1rem 0.4rem;border-radius:4px">7</code>
                </div>
                @php
                    $defaultGrades = $ksCfg['grades'] ?? [
                        ['min'=>2001,'max'=>null,'incentive'=>'جدة:7, الطائف:6','is_punishment'=>false],
                        ['min'=>1301,'max'=>2000,'incentive'=>'جدة:6, الطائف:5','is_punishment'=>false],
                        ['min'=>401, 'max'=>1300,'incentive'=>'جدة:5, الطائف:4','is_punishment'=>false],
                        ['min'=>0,   'max'=>400, 'incentive'=>'4','is_punishment'=>true],
                    ];
                @endphp
                <div id="gradesContainer">
                    @foreach($defaultGrades as $gi => $grade)
                    <div class="grade-row mb-3" style="background:var(--bg-hover);border-radius:10px;padding:0.8rem;border:1px solid {{ ($grade['is_punishment'] ?? false) ? 'rgba(220,53,69,0.4)' : 'var(--border)' }}">
                        <div class="row g-2 align-items-center">
                            <div class="col-3">
                                <label class="form-label-dark" style="font-size:0.72rem">من (ريال)</label>
                                <input type="number" name="ks[grades][{{ $gi }}][min]" class="form-control form-control-dark" value="{{ $grade['min'] ?? 0 }}" min="0">
                            </div>
                            <div class="col-3">
                                <label class="form-label-dark" style="font-size:0.72rem">الى (ريال) <small style="opacity:.6">فارغ=لا حد</small></label>
                                <input type="number" name="ks[grades][{{ $gi }}][max]" class="form-control form-control-dark" value="{{ $grade['max'] ?? '' }}" min="0" placeholder="فارغ=لا حد">
                            </div>
                            <div class="col-{{ $gi > 0 ? '4' : '5' }}">
                                <label class="form-label-dark" style="font-size:0.72rem">الحافز (ر.س/طلب) أو مدن</label>
                                <input type="text" name="ks[grades][{{ $gi }}][incentive]" class="form-control form-control-dark" value="{{ $grade['incentive'] ?? '' }}" placeholder="جدة:7, الطائف:6, الافتراضي:5">
                            </div>
                            <div class="col-{{ $gi > 0 ? '2' : '1' }}" style="display:flex;flex-direction:column;align-items:center;gap:4px">
                                @if($gi > 0)
                                <button type="button" class="btn-ghost remove-grade" style="color:var(--danger);padding:0.3rem 0.5rem;font-size:0.8rem">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2" style="font-size:0.77rem">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input punishment-toggle" type="checkbox"
                                    name="ks[grades][{{ $gi }}][is_punishment]"
                                    id="punishment_{{ $gi }}"
                                    value="1"
                                    {{ ($grade['is_punishment'] ?? false) ? 'checked' : '' }}
                                    onchange="updateGradeBorder(this)">
                                <label class="form-check-label" for="punishment_{{ $gi }}" style="color:var(--danger);font-weight:600">
                                    ⚠️ قريد عقوبة (يلغي الراتب الثابت ويحسب: طلبات × الحافز)
                                </label>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="addGradeBtn" class="btn-ghost mb-3" style="font-size:0.8rem">
                    <i class="bi bi-plus-circle"></i> اضافة درجة
                </button>

                <hr style="border-color:var(--border);margin:1.25rem 0">
                <div class="mb-2" style="font-size:0.8rem;font-weight:700;color:var(--success)">
                    🎁 البونص الاضافي الثابت
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label-dark">الحد الادنى للطلبات (لاستحقاق البونص)</label>
                        <input type="number" name="ks[bonus_min_orders]" class="form-control form-control-dark" placeholder="680" value="{{ $ksCfg['bonus_min_orders'] ?? 680 }}" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label-dark">قيمة البونص (ر.س)</label>
                        <input type="number" name="ks[bonus_value]" class="form-control form-control-dark" placeholder="300" value="{{ $ksCfg['bonus_value'] ?? 300 }}" min="0" step="0.01">
                    </div>
                </div>
                @endif

                <button type="submit" class="btn-walim w-100">
                    <i class="bi bi-save"></i> حفظ اعدادات الضبط
                </button>
            </form>
        </div>
    </div>

    {{-- سجل الاعدادات --}}
    <div class="col-xl-7">
        <div class="chart-card fade-in">
            <div class="chart-header">
                <div class="chart-title">📅 سجل الاعدادات الشهرية</div>
            </div>
            @if($platform->settings->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-gear" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
                لم يتم تحديد ضبط بعد. اضف اعدادات اول شهر.
            </div>
            @else
            <div class="table-responsive">
                <table class="table walim-table">
                    <thead>
                        <tr>
                            <th>الشهر</th>
                            <th>النوع</th>
                            @if($platform->report_format !== 'keeta_slabs')
                            <th>التارجت اليومي</th>
                            <th>قيمة الحافز</th>
                            @else
                            <th>الحد الادنى</th>
                            <th>نظام الراتب</th>
                            @endif
                            <th>ساعات الدوام</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($platform->settings->sortByDesc('month') as $setting)
                        <tr>
                            <td class="fw-bold">{{ \Carbon\Carbon::parse($setting->month)->format('Y/m') }}</td>
                            <td>
                                @if($setting->calc_mode === 'keeta_slabs')
                                <span class="status-badge badge-done">كيتا شرايح</span>
                                @else
                                <span class="status-badge badge-neutral">نينجا</span>
                                @endif
                            </td>
                            @if($platform->report_format !== 'keeta_slabs')
                            <td>{{ $setting->daily_target }} طلبية</td>
                            <td>{{ $setting->bonus_per_excess_order }} ر.س</td>
                            @else
                            <td>{{ $setting->keeta_slabs_config['base_min_orders'] ?? '—' }} طلب</td>
                            @if($isKeetaSlabs)
                            <td>
                                <span class="status-badge badge-active">{{ $setting->keeta_slabs_config['per_order_rate'] ?? 8 }} ر.س/طلب</span>
                                <span class="status-badge badge-profit">أو راتب الموظف الثابت</span>
                            </td>
                            @endif
                            @endif
                            <td>{{ $setting->min_working_hours_per_day }} ساعة</td>
                            <td>
                                <form action="{{ route('platforms.settings.destroy', $setting) }}" method="POST" onsubmit="return confirm('هل انت متاكد من حذف هذه الاعدادات؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-danger" style="padding:0.2rem 0.5rem;font-size:0.8rem">
                                        <i class="bi bi-trash"></i>
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

@if($isKeetaSlabs ?? false)
@push('scripts')
<script>
// لا حاجة لـ toggleModeFields لأن نوع الراتب يحدد من بيانات الموظف تلقائيا

// اضافة/حذف شريحة
let tierCount = {{ count($defaultKsTiers ?? []) }};
document.getElementById('addTierBtn')?.addEventListener('click', () => {
    const container = document.getElementById('tiersContainer');
    const idx = tierCount++;
    const row = document.createElement('div');
    row.className = 'tier-row row g-2 align-items-end mb-2';
    row.innerHTML = `
        <div class="col-3"><label class="form-label-dark" style="font-size:0.75rem">من (طلب)</label>
            <input type="number" name="ks[tiers][${idx}][from]" class="form-control form-control-dark" value="0" min="0" required></div>
        <div class="col-3"><label class="form-label-dark" style="font-size:0.75rem">الى (طلب)</label>
            <input type="number" name="ks[tiers][${idx}][to]" class="form-control form-control-dark" value="0" min="0" required></div>
        <div class="col-3"><label class="form-label-dark" style="font-size:0.75rem">السعر (ر.س)</label>
            <input type="number" name="ks[tiers][${idx}][rate]" class="form-control form-control-dark" value="0" min="0" step="0.01" required></div>
        <div class="col-3"><button type="button" class="btn-ghost remove-tier" style="color:var(--danger);padding:0.4rem 0.6rem"><i class="bi bi-trash"></i></button></div>`;
    container.appendChild(row);
});
document.getElementById('tiersContainer')?.addEventListener('click', e => {
    if (e.target.closest('.remove-tier')) e.target.closest('.tier-row').remove();
});

// اضافة/حذف درجة
let gradeCount = {{ count($defaultGrades ?? []) }};
document.getElementById('addGradeBtn')?.addEventListener('click', () => {
    const container = document.getElementById('gradesContainer');
    const idx = gradeCount++;
    const row = document.createElement('div');
    row.className = 'grade-row mb-3';
    row.style.cssText = 'background:var(--bg-hover);border-radius:10px;padding:0.8rem;border:1px solid var(--border)';
    row.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-3"><label class="form-label-dark" style="font-size:0.72rem">من (ريال)</label>
                <input type="number" name="ks[grades][${idx}][min]" class="form-control form-control-dark" value="0" min="0"></div>
            <div class="col-3"><label class="form-label-dark" style="font-size:0.72rem">الى (ريال) <small style="opacity:.6">فارغ=لا حد</small></label>
                <input type="number" name="ks[grades][${idx}][max]" class="form-control form-control-dark" value="" min="0" placeholder="فارغ=لا حد"></div>
            <div class="col-4"><label class="form-label-dark" style="font-size:0.72rem">الحافز (ر.س/طلب) أو مدن</label>
                <input type="text" name="ks[grades][${idx}][incentive]" class="form-control form-control-dark" value="" placeholder="جدة:7, الطائف:6, الافتراضي:5"></div>
            <div class="col-2" style="display:flex;align-items:center;justify-content:center">
                <button type="button" class="btn-ghost remove-grade" style="color:var(--danger);padding:0.3rem 0.5rem;font-size:0.8rem"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="mt-2" style="font-size:0.77rem">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input punishment-toggle" type="checkbox"
                    name="ks[grades][${idx}][is_punishment]" id="punishment_${idx}" value="1"
                    onchange="updateGradeBorder(this)">
                <label class="form-check-label" for="punishment_${idx}" style="color:var(--danger);font-weight:600">
                    ⚠️ قريد عقوبة (يلغي الراتب الثابت ويحسب: طلبات × الحافز)
                </label>
            </div>
        </div>`;
    container.appendChild(row);
});
document.getElementById('gradesContainer')?.addEventListener('click', e => {
    if (e.target.closest('.remove-grade')) e.target.closest('.grade-row').remove();
});

function updateGradeBorder(checkbox) {
    const row = checkbox.closest('.grade-row');
    if (row) {
        row.style.borderColor = checkbox.checked ? 'rgba(220,53,69,0.5)' : 'var(--border)';
    }
}
</script>
@endpush
@endif
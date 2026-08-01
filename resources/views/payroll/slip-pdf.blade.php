<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #111; background: #fff; direction: rtl; }
    .header { background: #1e3a5f; color: white; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { font-size: 18px; }
    .header .sub { font-size: 11px; opacity: 0.8; margin-top: 3px; }
    .logo { font-size: 28px; }
    .section { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; }
    .section-title { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; font-weight: bold; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .field { }
    .field .label { font-size: 10px; color: #9ca3af; margin-bottom: 2px; }
    .field .value { font-size: 13px; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; }
    table th { background: #f3f4f6; padding: 8px; text-align: right; font-size: 11px; color: #374151; border: 1px solid #e5e7eb; }
    table td { padding: 8px; border: 1px solid #e5e7eb; font-size: 12px; }
    .plus  { color: #059669; font-weight: bold; }
    .minus { color: #dc2626; font-weight: bold; }
    .total-row td { background: #f9fafb; font-weight: bold; font-size: 13px; }
    .net-row td { background: #1e3a5f; color: white; font-weight: bold; font-size: 14px; }
    .footer { padding: 12px 24px; text-align: center; font-size: 10px; color: #9ca3af; }
    .stamp { border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; color: #d1d5db; font-size: 11px; }
    .profit-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 8px 12px; }
    .loss-box   { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 8px 12px; }
</style>
</head>
<body>

<div class="header">
    <div>
        <h1>قسيمة الراتب الشهرية</h1>
        <div class="sub">{{ $period->platform->name }} — {{ \Carbon\Carbon::parse($period->month)->format('F Y') }}</div>
        <div class="sub">رقم القسيمة: #{{ $entry->id }} | صدرت: {{ date('Y/m/d') }}</div>
    </div>
    <div class="logo">🚀</div>
</div>

{{-- بيانات الموظف --}}
<div class="section">
    <div class="section-title">بيانات الموظف</div>
    <div class="grid-3">
        <div class="field"><div class="label">الاسم</div><div class="value">{{ $entry->employee?->display_name ?? '—' }}</div></div>
        <div class="field"><div class="label">رقم الإقامة</div><div class="value">{{ $entry->iqama_number }}</div></div>
        <div class="field"><div class="label">الفرع / المدينة</div><div class="value">{{ $entry->branch ?: $entry->city }}</div></div>
        <div class="field"><div class="label">نوع العقد</div><div class="value">{{ $entry->contract_type }}</div></div>
        <div class="field"><div class="label">نظام الراتب</div><div class="value">{{ match($entry->salary_system) {'fixed'=>'راتب ثابت','commission_tiered'=>'عمولة',default=>$entry->salary_system} }}</div></div>
        <div class="field"><div class="label">ID التطبيق</div><div class="value">{{ $entry->id_numbers ?: '—' }}</div></div>
        @if(!empty($entry->employee->notes))
        <div class="field"><div class="label">ملاحظات</div><div class="value" style="color:var(--text-danger); font-weight:bold;">{{ $entry->employee->notes }}</div></div>
        @endif
    </div>
</div>

{{-- أداء الشهر --}}
<div class="section">
    <div class="section-title">أداء الشهر</div>
    <div class="grid-3">
        <div class="field"><div class="label">إجمالي الطلبات</div><div class="value plus">{{ number_format($entry->total_orders) }}</div></div>
        <div class="field"><div class="label">أيام الدوام</div><div class="value">{{ $entry->working_days }}</div></div>
        <div class="field"><div class="label">إجمالي الإيراد</div><div class="value plus">{{ number_format($entry->total_revenue, 2) }} ر.س</div></div>
    </div>
</div>

{{-- مكونات الراتب --}}
<div class="section">
    <div class="section-title">تفاصيل الراتب</div>
    <table>
        <tr>
            <th>البند</th><th>المبلغ</th>
        </tr>
        <tr><td>الراتب الأساسي / العمولة المحسوبة</td><td class="plus">{{ number_format($entry->basic_salary, 2) }} ر.س</td></tr>
        @if($entry->bonus > 0)
        <tr><td>بونص الحوافز ({{ $entry->daily_target_excess }} طلبة إضافية)</td><td class="plus">{{ number_format($entry->bonus, 2) }} ر.س</td></tr>
        @endif
        <tr class="total-row"><td>إجمالي المستحقات</td><td>{{ number_format($entry->total_salary, 2) }} ر.س</td></tr>
    </table>
</div>

{{-- الخصومات --}}
@if($entry->total_deductions > 0)
<div class="section">
    <div class="section-title">الخصومات</div>
    <table>
        <tr><th>نوع الخصم</th><th>المبلغ</th></tr>
        @foreach([
            ['تسويات التطبيق', $entry->app_settlements],
            ['السلف النقدية', $entry->advances],
            ['المخالفات المرورية', $entry->traffic_violations],
            ['قطع الغيار (سوء استخدام)', $entry->spare_parts],
            ['الصيانة اليدوية', $entry->maintenance],
            ['جزاءات الشركة', $entry->company_discount],
            ['البنزين', $entry->fuel],
            ['السكن', $entry->housing],
            ['الباقات', $entry->packages],
            ['صيانة استهلاكية', $entry->consumable_maintenance],

        ] as [$label, $amount])
        @if($amount > 0)
        <tr><td>{{ $label }}</td><td class="minus">{{ number_format($amount, 2) }} ر.س</td></tr>
        @endif
        @endforeach
        <tr class="total-row"><td>إجمالي الخصومات</td><td class="minus">{{ number_format($entry->total_deductions, 2) }} ر.س</td></tr>
    </table>
</div>
@endif

{{-- الصافي --}}
<div class="section">
    <table>
        <tr class="net-row">
            <td>💰 صافي الراتب المستحق</td>
            <td>{{ number_format($entry->net_salary, 2) }} ر.س</td>
        </tr>
        @if($entry->pre_salary_paid > 0)
        <tr class="total-row">
            <td>مدد مصروفة مسبقاً</td>
            <td class="minus">{{ number_format($entry->pre_salary_paid, 2) }} ر.س</td>
        </tr>
        <tr class="net-row">
            <td>💵 المبلغ المتبقي للصرف</td>
            <td>{{ number_format($entry->remaining_salary, 2) }} ر.س</td>
        </tr>
        @endif
    </table>
</div>

{{-- الربحية --}}
<div class="section">
    <div class="section-title">مؤشر الربحية</div>
    <div class="{{ $entry->profit_loss >= 0 ? 'profit-box' : 'loss-box' }}" style="display:inline-block;min-width:200px">
        <div style="font-size:10px;color:{{ $entry->profit_loss >= 0 ? '#059669' : '#dc2626' }}">صافي الربح / الخسارة</div>
        <div style="font-size:18px;font-weight:bold;color:{{ $entry->profit_loss >= 0 ? '#059669' : '#dc2626' }}">
            {{ $entry->profit_loss >= 0 ? '+' : '' }}{{ number_format($entry->profit_loss, 2) }} ر.س
        </div>
    </div>
</div>

{{-- التوقيعات --}}
<div class="section">
    <div class="grid-3">
        <div class="stamp">توقيع الموظف</div>
        <div class="stamp">توقيع المحاسب</div>
        <div class="stamp">ختم الشركة</div>
    </div>
</div>

<div class="footer">
    تم إصدار هذه القسيمة بواسطة نظام وليم لإدارة الرواتب — {{ date('Y') }}
</div>

</body>
</html>

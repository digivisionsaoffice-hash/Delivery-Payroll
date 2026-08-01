<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\PlatformSettings;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    public function index()
    {
        $platforms = Platform::withCount(['employees', 'payrollPeriods'])->get();
        return view('platforms.index', compact('platforms'));
    }

    public function create()
    {
        return view('platforms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'name_en'      => 'required|string|max:100',
            'billing_type' => 'required|in:per_order,tiered,fixed',
        ]);
        Platform::create($data + ['is_active' => true]);
        return redirect()->route('platforms.index')->with('success', 'تم إضافة المنصة');
    }

    public function show(Platform $platform)
    {
        $platform->load('settings');
        return view('platforms.show', compact('platform'));
    }

    public function edit(Platform $platform)
    {
        return view('platforms.edit', compact('platform'));
    }

    public function update(Request $request, Platform $platform)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'name_en'      => 'required|string|max:100',
            'billing_type' => 'required|in:per_order,tiered,fixed',
            'is_active'    => 'boolean',
        ]);
        $platform->update($data);
        return redirect()->route('platforms.show', $platform)->with('success', 'تم تحديث المنصة');
    }

    public function destroy(Platform $platform)
    {
        $platform->delete();
        return redirect()->route('platforms.index')->with('success', 'تم الحذف');
    }

    public function storeSettings(Request $request, Platform $platform)
    {
        $isKeetaSlabs = ($platform->report_format === 'keeta_slabs');

        $data = $request->validate([
            'month'                      => 'required|date_format:Y-m',
            'app_name'                   => 'nullable|string|max:100',
            'calc_mode'                  => 'nullable|string|max:50',
            'daily_target'               => 'required|integer|min:0',
            'target_working_days'        => 'required|integer|min:1',
            'absence_deduction_type'     => 'required|in:worked_days_only,standard_deduction,strict_daily_unless_exceeded,pure_daily',
            'absence_deduction_rate'     => 'required|numeric|min:0',
            'extra_day_bonus_rate'       => 'required|numeric|min:0',
            'basic_salary'               => 'nullable|numeric|min:0',
            'bonus_per_excess_order'     => 'required|numeric|min:0',
            'min_working_hours_per_day'  => 'required|numeric|min:0|max:24',
            'link_target_to_hours'       => 'nullable|boolean',
            'monthly_target'             => 'nullable|integer|min:0',
            'commission_tiers'           => 'nullable|json',
        ]);

        $data['link_target_to_hours'] = $request->has('link_target_to_hours');
        $data['calc_mode'] = $isKeetaSlabs ? 'keeta_slabs' : 'ninja';

        if (!empty($data['commission_tiers'])) {
            $data['commission_tiers'] = json_decode($data['commission_tiers'], true);
        }

        // --- بناء keeta_slabs_config ---
        if ($isKeetaSlabs && $request->has('ks')) {
            $ks = $request->input('ks');

            // تنظيف الشرائح
            $tiers = [];
            foreach (($ks['tiers'] ?? []) as $tier) {
                if (isset($tier['from'], $tier['to'], $tier['rate'])) {
                    $tiers[] = [
                        'from' => (int) $tier['from'],
                        'to'   => (int) $tier['to'],
                        'rate' => (float) $tier['rate'],
                    ];
                }
            }
            // تنظيف الدرجات
            $grades = [];
            foreach (($ks['grades'] ?? []) as $grade) {
                $incentiveRaw = trim($grade['incentive'] ?? '0');
                $grades[] = [
                    'min'          => (int) ($grade['min'] ?? 0),
                    'max'          => isset($grade['max']) && $grade['max'] !== '' ? (int) $grade['max'] : null,
                    'incentive'    => $incentiveRaw, // نص مثل "جدة:7, الطائف:6, الافتراضي:5" أو رقم مجرد
                    'is_punishment'=> isset($grade['is_punishment']) && $grade['is_punishment'] == '1',
                ];
            }

            $data['keeta_slabs_config'] = [
                'salary_mode'      => $ks['salary_mode']      ?? 'fixed',
                'base_salary_value'=> (float) ($ks['base_salary_value'] ?? 2500),
                'per_order_rate'   => (float) ($ks['per_order_rate']    ?? 8),
                'base_min_orders'  => (int)   ($ks['base_min_orders']   ?? 450),
                'tiers'            => $tiers,
                'grades'           => $grades,
                'bonus_min_orders' => (int)   ($ks['bonus_min_orders']  ?? 0),
                'bonus_value'      => (float) ($ks['bonus_value']       ?? 0),
            ];
        }

        PlatformSettings::updateOrCreate(
            ['platform_id' => $platform->id, 'month' => $data['month'] . '-01'],
            array_merge($data, ['month' => $data['month'] . '-01', 'platform_id' => $platform->id])
        );

        return redirect()->route('platforms.show', $platform)
            ->with('success', 'تم حفظ اعدادات الضبط لشهر ' . $data['month']);
    }

    public function destroySettings(PlatformSettings $setting)
    {
        // التحقق من عدم وجود مسير رواتب معتمد أو قيد المراجعة لهذا الشهر والمنصة
        $payrollExists = \App\Models\PayrollPeriod::where('platform_id', $setting->platform_id)
            ->where('month', $setting->month)
            ->exists();

        if ($payrollExists) {
            return back()->with('error', 'لا يمكن حذف إعدادات شهر تم فتح مسير رواتب له. يرجى حذف مسير الرواتب أولاً.');
        }

        $setting->delete();
        return back()->with('success', 'تم حذف الإعدادات الشهرية بنجاح.');
    }
}

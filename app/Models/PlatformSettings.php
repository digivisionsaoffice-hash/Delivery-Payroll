<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSettings extends Model
{
    protected $fillable = [
        'platform_id',
        'month',
        'app_name',
        'calc_mode',
        'daily_target',
        'target_working_days',
        'absence_deduction_type',
        'absence_deduction_rate',
        'extra_day_bonus_rate',
        'basic_salary',
        'bonus_per_excess_order',
        'min_working_hours_per_day',
        'link_target_to_hours',
        'monthly_target',
        'commission_tiers',
        'keeta_slabs_config',
    ];

    protected $casts = [
        'month'                    => 'date',
        'commission_tiers'         => 'array',
        'keeta_slabs_config'       => 'array',
        'daily_target'             => 'integer',
        'target_working_days'      => 'integer',
        'absence_deduction_type'   => 'string',
        'absence_deduction_rate'   => 'decimal:2',
        'extra_day_bonus_rate'     => 'decimal:2',
        'bonus_per_excess_order'   => 'decimal:2',
        'min_working_hours_per_day' => 'decimal:1',
        'link_target_to_hours'     => 'boolean',
        'monthly_target'           => 'integer',
    ];

    /**
     * هل هذا الضبط لنينجا (الراتب الثابت)؟
     */
    public function isNinja(): bool
    {
        return !$this->calc_mode || $this->calc_mode === 'ninja';
    }

    /**
     * هل هذا الضبط لكيتا شرايح؟
     */
    public function isKeetaSlabs(): bool
    {
        return $this->calc_mode === 'keeta_slabs';
    }


    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * احتساب العمولة بنظام الشرائح
     * الزيادة فوق monthly_target × bonus_per_excess_order
     */
    public function calculateTieredCommission(int $totalOrders): float
    {
        if (!$this->commission_tiers || empty($this->commission_tiers)) {
            return 0;
        }

        $total = 0;
        foreach ($this->commission_tiers as $tier) {
            $from = $tier['from'] ?? 0;
            $to   = $tier['to'] ?? null;
            $rate = $tier['rate'] ?? 0;

            if ($totalOrders <= $from) break;

            $ordersInTier = $to !== null
                ? min($totalOrders, $to) - $from
                : $totalOrders - $from;

            $total += $ordersInTier * $rate;
        }

        return round($total, 2);
    }

    /**
     * احتساب الزيادة فوق التارجت الشهري
     * (600 - 450) × قيمة الحافز من الضبط
     */
    public function calculateMonthlyTargetBonus(int $totalOrders): float
    {
        if ($totalOrders <= $this->monthly_target) {
            return 0;
        }
        $excess = $totalOrders - $this->monthly_target;
        return round($excess * $this->bonus_per_excess_order, 2);
    }
}

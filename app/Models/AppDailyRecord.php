<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDailyRecord extends Model
{
    protected $fillable = [
        'import_batch_id', 'platform_id', 'record_date', 'supplier_id',
        'supplier_name', 'contract_type', 'captain_id', 'shift_id',
        'captain_name', 'branch_name', 'wallet_note', 'working_hours',
        'dynamic_per_hour', 'orders', 'suppliers_costs', 'bonus_ftr',
        'bonus_capacity', 'bonus_trial', 'food_damage', 'tga_discount',
        'adjustments', 'net_cost', 'vat_15', 'total_dues',
        'resolved_iqama', 'resolve_method', 'employee_id', 'is_settlement',
    ];

    protected $casts = [
        'record_date'    => 'date',
        'working_hours'  => 'decimal:2',
        'orders'         => 'integer',
        'suppliers_costs' => 'decimal:2',
        'bonus_capacity' => 'decimal:2',
        'bonus_trial'    => 'decimal:2',
        'food_damage'    => 'decimal:2',
        'tga_discount'   => 'decimal:2',
        'adjustments'    => 'decimal:2',
        'net_cost'       => 'decimal:2',
        'is_settlement'  => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /**
     * هل هذا السطر يُعدّ يوم دوام حقيقي؟
     * (إيراد > 0 + ساعات > الحد المطلوب)
     */
    public function isValidWorkDay(float $minHours): bool
    {
        return $this->suppliers_costs > 0 && $this->working_hours >= $minHours;
    }
}

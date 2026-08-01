<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePlatformId extends Model
{
    protected $fillable = [
        'employee_id', 'platform_id', 'captain_id',
        'id_name', 'start_date', 'end_date', 'city', 'import_batch_id',
        'adjustment_amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * ابحث عن رقم الإقامة لـ captain_id معين في تاريخ معين
     */
    public static function resolveIqama(int $captainId, string $date): ?string
    {
        $record = static::where('captain_id', $captainId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->with('employee')
            ->first();

        return $record?->employee?->iqama_number;
    }
}

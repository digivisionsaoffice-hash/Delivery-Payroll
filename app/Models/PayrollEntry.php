<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    protected $guarded = [];

    public function payrollPeriod(): BelongsTo { return $this->belongsTo(PayrollPeriod::class); }
    public function employee(): BelongsTo      { return $this->belongsTo(Employee::class); }

    public function getProfitStatusAttribute(): string
    {
        return match (true) {
            $this->profit_loss > 0   => 'profit',
            $this->profit_loss < 0   => 'loss',
            default                  => 'neutral',
        };
    }
}

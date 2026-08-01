<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficViolation extends Model
{
    protected $fillable = ['employee_id', 'import_batch_id', 'payroll_month', 'violation_number', 'violation_type', 'violation_date', 'city', 'amount', 'plate_number'];
    protected $casts    = ['payroll_month' => 'date', 'violation_date' => 'date', 'amount' => 'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

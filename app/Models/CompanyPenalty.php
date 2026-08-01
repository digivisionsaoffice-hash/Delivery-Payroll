<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPenalty extends Model
{
    protected $table    = 'company_penalties';
    protected $fillable = ['employee_id', 'import_batch_id', 'payroll_month', 'violation_title', 'discount_amount', 'penalty_date', 'notes'];
    protected $casts    = ['payroll_month' => 'date', 'penalty_date' => 'date', 'discount_amount' => 'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

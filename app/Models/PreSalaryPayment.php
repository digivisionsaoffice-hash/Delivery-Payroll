<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreSalaryPayment extends Model
{
    protected $fillable = ['employee_id', 'import_batch_id', 'payroll_month', 'amount', 'notes'];
    protected $casts    = ['payroll_month' => 'date', 'amount' => 'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

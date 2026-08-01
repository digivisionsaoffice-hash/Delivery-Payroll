<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthlyExpense extends Model
{
    protected $fillable = ['employee_id', 'payroll_month', 'fuel', 'housing', 'packages', 'consumable_maintenance', 'import_batch_id'];
    protected $casts    = ['payroll_month' => 'date'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartsMisuse extends Model
{
    protected $table    = 'spare_parts_misuse';
    protected $fillable = ['employee_id', 'import_batch_id', 'payroll_month', 'cost', 'quantity', 'total_value', 'notes'];
    protected $casts    = ['payroll_month' => 'date', 'cost' => 'decimal:2', 'total_value' => 'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

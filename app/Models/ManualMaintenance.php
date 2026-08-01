<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualMaintenance extends Model
{
    protected $table = 'manual_maintenance';
    protected $fillable = ['employee_id', 'import_batch_id', 'payroll_month', 'plate_number', 'spare_parts', 'reason', 'comments', 'discount_amount'];
    protected $casts    = ['payroll_month' => 'date', 'discount_amount' => 'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

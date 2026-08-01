<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIdDailyRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_id', 'employee_id', 'iqama_number',
        'captain_id', 'work_date', 'month',
    ];

    protected $casts = [
        'work_date' => 'date',
        'month'     => 'date',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function platform(): BelongsTo { return $this->belongsTo(Platform::class); }
}

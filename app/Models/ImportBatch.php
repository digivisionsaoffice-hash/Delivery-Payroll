<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    protected $fillable = [
        'platform_id', 'month', 'sheet_type', 'file_name',
        'rows_imported', 'rows_failed', 'errors', 'status', 'imported_by',
    ];

    protected $casts = [
        'month'  => 'date',
        'errors' => 'array',
    ];

    public function platform(): BelongsTo { return $this->belongsTo(Platform::class); }
    public function importedBy(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
    public function appDailyRecords() { return $this->hasMany(AppDailyRecord::class); }
    public function employeePlatformIds() { return $this->hasMany(EmployeePlatformId::class); }
}

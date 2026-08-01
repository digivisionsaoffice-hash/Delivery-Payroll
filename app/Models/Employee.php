<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'iqama_number', 'name_ar', 'name_en', 'branch_id', 'city',
        'contract_type', 'salary_system', 'agreed_salary', 'platform_id',
        'employee_status', 'hire_date', 'phone', 'nationality', 'photo', 'notes', 'import_batch_id',
        'app_id', 'vehicle_number', 'discount_factor',
    ];

    protected $casts = [
        'agreed_salary' => 'decimal:2',
        'hire_date'     => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Mutator to ensure iqama_number is always stored without spaces or hidden characters
     */
    public function setIqamaNumberAttribute($value)
    {
        $this->attributes['iqama_number'] = preg_replace('/\s+/', '', (string) $value);
    }

    public function platformIds(): HasMany
    {
        return $this->hasMany(EmployeePlatformId::class);
    }

    public function activePlatformIds(): HasMany
    {
        return $this->hasMany(EmployeePlatformId::class)->whereNull('end_date');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    public function trafficViolations(): HasMany
    {
        return $this->hasMany(TrafficViolation::class);
    }

    public function sparePartsMisuse(): HasMany
    {
        return $this->hasMany(SparePartsMisuse::class);
    }

    public function manualMaintenance(): HasMany
    {
        return $this->hasMany(ManualMaintenance::class);
    }

    public function companyPenalties(): HasMany
    {
        return $this->hasMany(CompanyPenalty::class);
    }

    public function preSalaryPayments(): HasMany
    {
        return $this->hasMany(PreSalaryPayment::class);
    }

    public function monthlyExpenses(): HasMany
    {
        return $this->hasMany(EmployeeMonthlyExpense::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(AppDailyRecord::class);
    }

    /**
     * هل هذا الموظف يعمل بنظام العمولة (8 ريال)?
     */
    public function isCommissionSystem(): bool
    {
        return $this->salary_system === 'commission_tiered';
    }

    /**
     * الاسم المعروض (عربي إن وجد وإلا إنجليزي)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name_en;
    }
}

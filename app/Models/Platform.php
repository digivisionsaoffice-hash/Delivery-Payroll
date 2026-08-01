<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Platform extends Model
{
    protected $fillable = ['name', 'name_en', 'logo', 'billing_type', 'report_format', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * الحصول على تعريف الأعمدة الخاص بهذه المنصة
     */
    public function columnDef(): array
    {
        return \App\Support\PlatformColumnMap::get($this->report_format ?? 'ninja');
    }

    /**
     * الأعمدة المتوقعة لتقرير هذه المنصة
     */
    public function expectedColumns(): array
    {
        $def = $this->columnDef();
        return $def['columns'] ?? [];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(PlatformSettings::class);
    }

    public function settingsForMonth(string $month): ?PlatformSettings
    {
        return $this->settings()->where('month', $month)->first();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }
}

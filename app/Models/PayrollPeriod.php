<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPeriod extends Model
{
    protected $fillable = ['platform_id', 'month', 'status', 'approved_by', 'approved_at', 'notes'];
    protected $casts    = ['month' => 'date', 'approved_at' => 'datetime'];

    public function platform(): BelongsTo { return $this->belongsTo(Platform::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
    public function entries() { return $this->hasMany(PayrollEntry::class); }
}

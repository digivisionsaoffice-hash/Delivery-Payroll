<?php

namespace App\Exports;

use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomValueBinder
{
    protected $period;

    public function __construct(PayrollPeriod $period)
    {
        $this->period = $period;
    }

    public function collection()
    {
        return PayrollEntry::where('payroll_period_id', $this->period->id)
            ->with('employee.branch')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        $isKeeta = $this->period->platform->settingsForMonth($this->period->month->format('Y-m-01'))?->isKeetaSlabs();
        
        $headings = [
            'Iqama Number',
            'Riders Name',
            'Agreed salary',
            'Contract Type',
            'Application Name',
            'Branch',
            'ID Number',
            'City',
            'Orders',
        ];

        if (!$isKeeta) {
            $headings[] = 'Working Days';
        }

        $headings[] = 'Revenue';

        if ($isKeeta) {
            $headings[] = 'Grade';
        }

        $headings[] = 'Basic Salary';

        if ($isKeeta) {
            $headings[] = 'Incentive';
        } else {
            $headings[] = 'Daily Target Excess';
        }

        return array_merge($headings, [
            'Bonus',
            'Total Salary',
            'App Settlements',
            'Advance',
            'Traffic Violations',
            'Spare Parts',
            'Maintenance',
            'Company Discount',
            'Fuel',
            'Housing',
            'Internet',
            'Total Deductions',
            'Net salary',
            'Pre Salary (Madad)',
            'Remaining Salary'
        ]);
    }

    public function map($entry): array
    {
        $isKeeta = $this->period->platform->settingsForMonth($this->period->month->format('Y-m-01'))?->isKeetaSlabs();

        $map = [
            $entry->employee->iqama_number,
            $entry->employee->name_en ?: $entry->employee->name_ar,
            $entry->agreed_salary,
            $entry->employee->contract_type,
            $this->period->platform->name_en ?: $this->period->platform->name,
            $entry->employee->branch?->name_en ?: $entry->employee->branch?->name ?: 'N/A',
            $entry->platform_id_number ?? $entry->id_numbers,
            $entry->employee->city ?: 'N/A',
            $entry->total_orders,
        ];

        if (!$isKeeta) {
            $map[] = $entry->working_days;
        }

        $map[] = round((float)$entry->total_revenue, 2);

        if ($isKeeta) {
            $map[] = $entry->grade ?? '';
        }

        $map[] = round((float)$entry->basic_salary, 2);

        if ($isKeeta) {
            $map[] = round((float)$entry->daily_target_excess, 2);
        } else {
            $map[] = round((float)$entry->daily_target_excess, 2);
        }
        
        return array_merge($map, [
            $entry->bonus,
            $entry->total_salary,
            $entry->app_settlements,
            $entry->advances,
            $entry->traffic_violations,
            $entry->spare_parts,
            $entry->maintenance,
            $entry->company_discount,
            $entry->fuel,
            $entry->housing,
            $entry->packages,
            $entry->total_deductions,
            $entry->net_salary,
            $entry->pre_salary_paid,
            $entry->remaining_salary,
        ]);
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), ['A', 'G'])) { // Iqama Number and ID Number
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}

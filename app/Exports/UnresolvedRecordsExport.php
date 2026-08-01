<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UnresolvedRecordsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $records;
    protected $platformName;

    public function __construct($records, $platformName)
    {
        $this->records = $records;
        $this->platformName = $platformName;
    }

    public function collection()
    {
        $data = [];
        $isKeetaSlabs = str_contains($this->platformName, 'كيتا شرائح') || str_contains($this->platformName, 'keeta_slabs');

        foreach ($this->records as $record) {
            $row = [
                'resolved_iqama'  => $record->resolved_iqama ?? 'غير محلول',
                'resolve_method'  => $this->translateMethod($record->resolve_method),
                'record_date'     => $record->record_date ? $record->record_date->format('Y-m-d') : '',
                'supplier_id'     => $record->supplier_id,
                'supplier_name'   => $record->supplier_name,
                'contract_type'   => $record->contract_type,
                'captain_id'      => $record->captain_id,
                'captain_name'    => $record->captain_name,
                'target'          => $record->target,
            ];

            if ($isKeetaSlabs) {
                $row['adjustments'] = $record->total_dues; // بدل التسويات نضع إجمالي المستحق
                // الإيراد في كيتا شرائح: العميل يعتبر الإيراد إما صفر أو قيمة أخرى، لكن suppliers_costs هو الخصم
                // سنضع الإيراد (Suppliers Costs) كما هو أو صفر لتجنب اللبس، لكن العميل قال "الإيراد مش مطابق"
                // ربما كان يعتبر "إجمالي المستحق" هو الإيراد؟ لا، هو حدد أن إجمالي المستحق يجب أن يكون "بدل التسويات".
                // بالنسبة للإيراد، سنضع قيمة الطلبات التقريبية إذا أمكن أو نتركها للخصم؟
                // دعنا نعرض الخصم في عمود الخصم، ونضع (إجمالي المستحق) في عمود التسويات كما طلب.
                $row['suppliers_costs'] = $record->suppliers_costs; 
            } else {
                $row['adjustments'] = $record->adjustments;
                $row['suppliers_costs'] = $record->suppliers_costs;
            }

            $row['orders'] = $record->orders;
            
            if ($isKeetaSlabs) {
                $row['food_damage']  = $record->food_damage;
                $row['tga_discount'] = $record->tga_discount;
            }

            $row['city_name']   = $record->city_name;
            $row['branch_name'] = $record->branch_name;
            $row['shift_id']    = $record->shift_id;
            $row['wallet_note'] = $record->wallet_note;
            $row['fill_iqama']  = '';

            $data[] = $row;
        }
        return collect($data);
    }

    public function headings(): array
    {
        $isKeetaSlabs = str_contains($this->platformName, 'كيتا شرائح') || str_contains($this->platformName, 'keeta_slabs');

        $headers = [
            'رقم الإقامة المربوط',
            'طريقة المطابقة',
            'التاريخ',
            'Supplier ID',
            'Supplier Name',
            'Contract Type',
            'Captain ID',
            'Captain Name',
            'Target',
            $isKeetaSlabs ? 'Total Dues (إجمالي المستحق)' : 'Adjustments (التسويات)',
            'Suppliers Costs (الإيراد/الخصم)',
            'Orders',
        ];

        if ($isKeetaSlabs) {
            $headers[] = 'Food Damage';
            $headers[] = 'TGA Discount';
        }

        $headers = array_merge($headers, [
            'City Name',
            'Branch Name',
            'Shift ID',
            'Wallet Note',
            'رقم الإقامة',
        ]);

        return $headers;
    }

    private function translateMethod($method)
    {
        $map = [
            'single_user_id'  => 'معرف ثابت',
            'direct'          => 'ربط مباشر',
            'shift_match'     => 'رقم الشفت',
            'wallet_date'     => 'تاريخ المحفظة',
            'wallet_fallback' => 'تراجع (تاريخ المحفظة)',
            'date_fallback'   => 'تراجع تاريخي',
            'unresolved'      => 'غير محلول',
        ];
        return $map[$method] ?? $method;
    }
}

<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ErrorsExport implements FromCollection, WithHeadings, \Maatwebsite\Excel\Concerns\WithEvents
{
    protected Collection $rows;
    protected array $cellColors;

    public function __construct(Collection $rows, array $cellColors = [])
    {
        $this->rows = $rows;
        $this->cellColors = $cellColors;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        if ($this->rows->isEmpty()) {
            return [];
        }

        return array_keys($this->rows->first());
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                foreach ($this->cellColors as $cell => $color) {
                    $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($color);
                }
            },
        ];
    }
}

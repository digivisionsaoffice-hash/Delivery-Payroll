<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Support\PlatformColumnMap;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateController extends Controller
{
    /**
     * تحميل قالب Excel لتقرير تطبيق محدد (حسب المنصة)
     * مثال: GET /templates/platform/1
     */
    public function platformReport(int $platformId): StreamedResponse
    {
        $platform = Platform::findOrFail($platformId);
        $format   = $platform->report_format ?? 'ninja';
        $def      = PlatformColumnMap::get($format);

        $spreadsheet = $this->buildReportSheet($platform->name, $format, $def);
        $filename    = 'template_app_report_' . \Str::slug($platform->name) . '_' . date('Y') . '.xlsx';

        return $this->streamDownload($spreadsheet, $filename);
    }

    /**
     * تحميل قالب Excel لنوع ورقة آخر (خصومات، مصاريف، IDs)
     * مثال: GET /templates/sheet/advances
     */
    public function sheetType(string $type): StreamedResponse
    {
        $def = PlatformColumnMap::getSheetDef($type);
        if (empty($def)) abort(404, 'نوع القالب غير موجود');

        $spreadsheet = $this->buildSheetTemplate($type, $def);
        $filename    = 'template_' . $type . '_' . date('Y') . '.xlsx';

        return $this->streamDownload($spreadsheet, $filename);
    }

    // ===================================================================
    // بناء القوالب
    // ===================================================================

    private function buildReportSheet(string $platformName, string $format, array $def): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('وليم — نظام الرواتب')
            ->setTitle("قالب تقرير {$platformName} النهائي")
            ->setDescription("قالب استيراد تقرير التطبيق النهائي — {$platformName}");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('تقرير التطبيق');
        $sheet->setRightToLeft(true);

        $columns  = $def['columns'];
        $required = $def['required'] ?? [];
        $idAsText = $def['id_as_text'] ?? false;
        $usedCalc = $def['used_in_calc'] ?? [];
        $infoOnly = $def['info_only'] ?? [];
        $colCount = count($columns);
        $lastCol  = $this->colLetter($colCount);

        // --- صف رؤوس الأعمدة ---
        $captainIdColIdx = null;
        foreach ($columns as $idx => $colName) {
            $colLetter = $this->colLetter($idx + 1);
            $cell      = $colLetter . '1';
            $sheet->setCellValue($cell, $colName);

            // تحديد اللون بناءً على نوع العمود
            if (in_array($colName, $required)) {
                $bgColor  = '2563EB';  // أزرق → مطلوب للحسبة
            } elseif (in_array($colName, $usedCalc)) {
                $bgColor  = '0891B2';  // أزرق فاتح → يدخل الحسبة
            } elseif (in_array($colName, $infoOnly)) {
                $bgColor  = '78716C';  // رمادي بني → معلوماتي فقط
            } else {
                $bgColor  = '374151';  // رمادي → اختياري
            }

            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(32);

            // captain_id column index
            if (strtolower($colName) === 'captain_id') {
                $captainIdColIdx = $idx + 1;
            }
        }

        // --- 5 صفوف بيانات نموذجية ---
        $sampleRows = $this->getReportSampleRows($format, $columns);
        foreach ($sampleRows as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            foreach ($columns as $idx => $colName) {
                $colLetter = $this->colLetter($idx + 1);
                $cell      = $colLetter . $excelRow;
                $value     = $rowData[$idx] ?? '';

                if ($idAsText && $captainIdColIdx === ($idx + 1)) {
                    $sheet->getCellByColumnAndRow($idx + 1, $excelRow)
                          ->setValueExplicit((string)$value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }

                $bg = ($rowIdx % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
                $sheet->getStyle($cell)->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }
        }

        // --- إذا كان captain_id نص: تأكيد تنسيق العمود كله كـ نص ---
        if ($idAsText && $captainIdColIdx) {
            $colLetter = $this->colLetter($captainIdColIdx);
            $sheet->getStyle("{$colLetter}2:{$colLetter}1000")
                  ->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        // --- عرض الأعمدة ---
        foreach ($columns as $idx => $colName) {
            $sheet->getColumnDimension($this->colLetter($idx + 1))->setWidth($this->guessWidth($colName));
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        // --- شيت الملاحظات ---
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('تعليمات');
        $notesSheet->setRightToLeft(true);
        $this->buildNotesSheet($notesSheet, $platformName, $def);

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    private function buildSheetTemplate(string $type, array $def): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('وليم — نظام الرواتب')
            ->setTitle($def['label']);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('البيانات');
        $sheet->setRightToLeft(true);

        $columns  = $def['columns'];
        $required = $def['required'] ?? [];
        $idAsText = $def['id_as_text'] ?? false;
        $colCount = count($columns);
        $lastCol  = $this->colLetter($colCount);

        // رؤوس
        $captainIdColIdx = null;
        foreach ($columns as $idx => $colName) {
            $cell    = $this->colLetter($idx + 1) . '1';
            $bgColor = in_array($colName, $required) ? '2563EB' : '334155';
            $sheet->setCellValue($cell, $colName);
            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);
            if (strtolower($colName) === 'captain_id') $captainIdColIdx = $idx + 1;
        }

        // بيانات نموذجية
        $samples = $this->getSheetSampleRows($type, $columns);
        foreach ($samples as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            foreach ($columns as $idx => $colName) {
                $colLetter = $this->colLetter($idx + 1);
                $cell      = $colLetter . $excelRow;
                $value     = $rowData[$idx] ?? '';

                if ($idAsText && $captainIdColIdx === ($idx + 1)) {
                    $sheet->getCellByColumnAndRow($idx + 1, $excelRow)
                          ->setValueExplicit((string)$value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }

                $bg = ($rowIdx % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
                $sheet->getStyle($cell)->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }
        }

        if ($idAsText && $captainIdColIdx) {
            $cl = $this->colLetter($captainIdColIdx);
            $sheet->getStyle("{$cl}2:{$cl}1000")->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        foreach ($columns as $idx => $colName) {
            $sheet->getColumnDimension($this->colLetter($idx + 1))->setWidth($this->guessWidth($colName));
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        // ملاحظات
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('تعليمات');
        $notesSheet->setRightToLeft(true);
        $this->buildNotesSheet($notesSheet, $def['label'], $def);

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    private function buildNotesSheet($sheet, string $title, array $def): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', '📖 تعليمات — ' . $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $rows = [];
        if (!empty($def['notes'])) {
            $rows[] = ['⚠️ ملاحظات مهمة:', '', '', ''];
            foreach ($def['notes'] as $note) {
                $rows[] = ['  • ' . $note, '', '', ''];
            }
            $rows[] = ['', '', '', ''];
        }

        $rows[] = ['🟦 الأعمدة المطلوبة:', implode(' | ', $def['required'] ?? []), '', ''];
        $rows[] = ['', '', '', ''];
        $rows[] = ['📋 الأسماء المقبولة لكل عمود:', '', '', ''];
        $rows[] = ['الحقل', 'الأسماء المقبولة', '', ''];

        foreach ($def['map'] ?? [] as $field => $aliases) {
            $rows[] = [$field, implode(' / ', $aliases), '', ''];
        }

        $rows[] = ['', '', '', ''];
        $rows[] = ['✅ قواعد عامة:', '', '', ''];
        $rows[] = ['  • النظام يتعرف على الأعمدة بأي ترتيب', '', '', ''];
        $rows[] = ['  • الأعمدة الإضافية ستُكتشف وتُعرض كتحذير', '', '', ''];
        $rows[] = ['  • الصفوف الفارغة يتجاهلها النظام تلقائياً', '', '', ''];

        foreach ($rows as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            foreach ($rowData as $colIdx => $value) {
                $sheet->setCellValue($this->colLetter($colIdx + 1) . $excelRow, $value);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(65);
    }

    // ===================================================================
    // بيانات نموذجية
    // ===================================================================

    private function getReportSampleRows(string $format, array $columns): array
    {
        $samples = match($format) {
            'ninja' => [
                ['2026-06-01', 'SUP001', 'شركة نينجا', 'موظف', '12345', 'SH001', 'أحمد محمد', 'الرياض', 'دفعة رواتب', 8.5, 15, 25, 312.50, 50, 0, 312.50, 46.88, 359.38],
                ['2026-06-01', 'SUP001', 'شركة نينجا', 'موظف', '12346', 'SH002', 'خالد عبدالله', 'جدة', 'دفعة رواتب', 9.0, 18, 30, 375.00, 60, -20, 355.00, 53.25, 408.25],
                ['2026-06-02', 'SUP001', 'شركة نينجا', 'موظف', '12345', 'SH003', 'أحمد محمد', 'الرياض', 'تسوية', 0, 0, 0, 0, 0, -50, 0, 0, 0],
            ],
            'keeta_orders' => [
                ['2026-06-01', '19283746501234567', 'فهد علي', 'SH001', 8.5, 22, 275.00, 0, 275.00, 316.25],
                ['2026-06-01', '19283746501234568', 'محمد سالم', 'SH002', 9.0, 28, 350.00, -15, 335.00, 385.25],
                ['2026-06-02', '19283746501234567', 'فهد علي', 'SH003', 7.5, 19, 237.50, 0, 237.50, 273.13],
            ],
            'keeta_slabs' => [
                ['2026-06-01', '19283746501234567', 'فهد علي', 'SH001', 8.5, 450, 50, 30, 2500, 0, 2500, 2875],
                ['2026-06-01', '19283746501234568', 'محمد سالم', 'SH002', 9.0, 380, 0, 20, 2100, -50, 2050, 2357.5],
                ['2026-06-02', '19283746501234567', 'فهد علي', 'SH003', 7.0, 290, 0, 0, 1600, 0, 1600, 1840],
            ],
            'hunger' => [
                ['2026-06-01', 'HS001', 'شركة أ', '7001', 'يوسف أحمد', 'الرياض', 8.0, 20, 250.00, 0, 250.00, 287.50],
                ['2026-06-01', 'HS001', 'شركة أ', '7002', 'عمر خالد', 'جدة', 9.5, 26, 325.00, -10, 315.00, 362.25],
                ['2026-06-02', 'HS001', 'شركة أ', '7001', 'يوسف أحمد', 'الرياض', 7.0, 15, 187.50, 0, 187.50, 215.63],
            ],
            'jahez' => [
                ['2026-06-01', '8001', 'ناصر محمد', 'الرياض', 8.0, 22, 275.00, 0, 275.00, 316.25],
                ['2026-06-01', '8002', 'سلطان علي', 'جدة', 9.0, 28, 350.00, -20, 330.00, 379.50],
                ['2026-06-02', '8001', 'ناصر محمد', 'الرياض', 7.5, 18, 225.00, 0, 225.00, 258.75],
            ],
            default => [
                ['2026-06-01', '12345', 'اسم السائق', 8.0, 20, 250.00, 0, 250.00],
                ['2026-06-01', '12346', 'اسم آخر', 9.0, 25, 312.50, -10, 302.50],
            ],
        };

        // التأكد من تطابق عدد العناصر مع عدد الأعمدة
        return array_map(fn($r) => array_pad($r, count($columns), ''), $samples);
    }

    private function getSheetSampleRows(string $type, array $columns): array
    {
        $samples = match($type) {
            'id_changes' => [
                ['1', 'أحمد محمد', '2345678901', '12345', 'الـ ID الأصلي', '2026-01-01', '2026-06-30', 'الرياض', 'Ninja'],
                ['2', 'خالد عبدالله', '3456789012', '19283746501234567', 'كيتا ID', '2026-03-01', '', 'جدة', 'Keeta'],
            ],
            'advances', 'pre_salary' => [
                ['2345678901', 'أحمد محمد', 500, '2026-06-05', 'سلفة شخصية'],
                ['3456789012', 'خالد عبدالله', 1000, '2026-06-10', ''],
                ['4567890123', 'محمد علي', 300, '2026-06-15', 'طارئة'],
            ],
            'violations' => [
                ['2345678901', 'أحمد محمد', 'VIO-001', 'سرعة زائدة', '2026-06-03', 'الرياض', 500, 'أ ب ج 1234'],
                ['3456789012', 'خالد عبدالله', 'VIO-002', 'تجاوز إشارة', '2026-06-07', 'جدة', 300, 'ه و ز 5678'],
            ],
            'spare_parts' => [
                ['2345678901', 'أحمد محمد', 'إطار', 2, 150, 300],
                ['3456789012', 'خالد عبدالله', 'بطارية', 1, 200, 200],
            ],
            'maintenance' => [
                ['2345678901', 'أحمد محمد', 'أ ب ج 1234', 'فلتر هواء', 'إهمال', '', 80],
                ['3456789012', 'خالد عبدالله', 'ه و ز 5678', 'دهان', 'حادث', 'بسيط', 500],
            ],
            'penalties' => [
                ['2345678901', 'أحمد محمد', 'تأخر في العمل', 100, '2026-06-05', 'تأخر 3 مرات'],
                ['3456789012', 'خالد عبدالله', 'شكوى عميل', 200, '2026-06-08', ''],
            ],
            default => [
                ['2345678901', 'أحمد محمد', 500, '2026-06', ''],
                ['3456789012', 'خالد عبدالله', 300, '2026-06', ''],
            ],
        };

        return array_map(fn($r) => array_pad($r, count($columns), ''), $samples);
    }

    // ===================================================================
    // Utilities
    // ===================================================================

    private function streamDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Content-Disposition' => 'attachment',
        ]);
    }

    private function colLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $mod    = ($col - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $col    = (int)(($col - $mod) / 26);
        }
        return $letter;
    }

    private function guessWidth(string $colName): float
    {
        $lower = strtolower($colName);
        if (str_contains($lower, 'name') || str_contains($lower, 'اسم')) return 22;
        if (str_contains($lower, 'note') || str_contains($lower, 'reason') || str_contains($lower, 'comment')) return 25;
        if (str_contains($lower, 'captain_id') || str_contains($lower, 'supplier_id')) return 22;
        if (str_contains($lower, 'date') || str_contains($lower, 'تاريخ')) return 16;
        if (str_contains($lower, 'iqama') || str_contains($lower, 'إقامة')) return 18;
        return 14;
    }
}

<?php
namespace App\Imports;
use App\Models\TrafficViolation;
use Carbon\Carbon;
class ViolationsImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        $date = null;
        if (!empty($row['date_of_violation'])) {
            $v = $row['date_of_violation'];
            $date = is_numeric($v) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d') : Carbon::parse($v)->format('Y-m-d');
        }
        TrafficViolation::updateOrCreate(
            ['employee_id' => $empId, 'payroll_month' => $this->payrollMonth()],
            ['import_batch_id'=>$this->batch->id,'violation_number'=>$row['violation_number']??null,'violation_type'=>$row['type_of_violation']??null,'violation_date'=>$date,'city'=>$row['city']??null,'amount'=>(float)($row['the_value_of_the_violation']??0),'plate_number'=>$row['plate_number']??null]
        );
    }
}


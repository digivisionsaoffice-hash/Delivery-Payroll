<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employees = \App\Models\Employee::all();
$fixed = 0;
foreach($employees as $emp) {
    $clean = preg_replace('/\s+/', '', $emp->iqama_number); // Remove ALL whitespace (spaces, newlines, etc)
    if ($clean !== $emp->iqama_number) {
        $emp->iqama_number = $clean;
        $emp->save();
        $fixed++;
    }
}
echo "Fixed $fixed employees.";

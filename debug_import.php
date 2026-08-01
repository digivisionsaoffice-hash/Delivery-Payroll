<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// آخر batch مع أخطاء
$batch = \App\Models\ImportBatch::where('rows_failed', '>', 0)
    ->orWhere('status', 'failed')
    ->latest()
    ->first();

if (!$batch) {
    echo "لا يوجد batch بأخطاء\n";
    exit;
}

echo "=== Batch ID: {$batch->id} ===\n";
echo "Platform: " . $batch->platform->name . "\n";
echo "Status: {$batch->status}\n";
echo "Rows imported: {$batch->rows_imported}\n";
echo "Rows failed: {$batch->rows_failed}\n";
echo "Sheet type: {$batch->sheet_type}\n";
echo "File: {$batch->file_name}\n\n";

$errors = $batch->errors ?? [];
echo "=== أول 5 أخطاء ===\n";
foreach (array_slice($errors, 0, 5) as $i => $err) {
    echo ($i+1) . ". " . json_encode($err, JSON_UNESCAPED_UNICODE) . "\n";
}

// أيضاً: فحص الملف الأخير هل هو موجود
$storage = \Illuminate\Support\Facades\Storage::disk('local');
$files = $storage->files('imports');
echo "\n=== آخر 5 ملفات في imports/ ===\n";
foreach (array_slice($files, -5) as $f) {
    echo $f . " (" . $storage->size($f) . " bytes)\n";
}

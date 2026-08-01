<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\PlatformSettings::where('platform_id', 2)->first();
if ($p) {
    $cfg = $p->keeta_slabs_config ?? [];
    $cfg['grades'] = [
        ['min' => 2001, 'max' => null,  'incentive' => 'جدة:7, الطائف:6, الافتراضي:6', 'is_punishment' => false],
        ['min' => 1301, 'max' => 2000,  'incentive' => 'جدة:6, الطائف:5, الافتراضي:5', 'is_punishment' => false],
        ['min' => 401,  'max' => 1300,  'incentive' => 'جدة:5, الطائف:4, الافتراضي:4', 'is_punishment' => false],
        ['min' => 0,    'max' => 400,   'incentive' => '4',                              'is_punishment' => true],
    ];
    $p->keeta_slabs_config = $cfg;
    $p->save();
    echo "Settings updated with dynamic city grades.\n";
    echo "New grades: " . json_encode($cfg['grades'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Platform 2 settings not found.\n";
}

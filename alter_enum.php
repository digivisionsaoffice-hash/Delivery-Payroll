<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::statement("ALTER TABLE app_daily_records MODIFY resolve_method ENUM('direct','shift_match','wallet_date','date_fallback','unresolved','single_user_id','wallet_fallback')");
echo "Done";

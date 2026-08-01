<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$period = \App\Models\PayrollPeriod::where('platform_id', 2)->latest()->first();
if (!$period) die("No payroll period found.\n");

echo "الفترة: " . $period->month->format('Y-m') . "\n";
echo str_repeat('=', 90) . "\n";

$entries = \App\Models\PayrollEntry::where('payroll_period_id', $period->id)
    ->where('total_revenue', '>', 0)
    ->orderByDesc('total_revenue')
    ->get();

// --- السطر الحالي ---
printf("%-14s %-8s %-6s %-8s %-10s %-8s %-8s | %-8s %-8s %-8s\n",
    'إقامة', 'مدينة', 'قريد', 'طلبات', 'إيراد', 'راتب', 'نسبة%',
    '40%', '45%', 'فرق45%');
echo str_repeat('-', 90) . "\n";

$totalRevenue = 0; $totalSalary = 0;
$totalAt40 = 0; $totalAt45 = 0;

foreach ($entries as $e) {
    $rev = (float) $e->total_revenue;
    $sal = (float) $e->total_salary;
    $ratio = round(($sal / $rev) * 100, 1);
    $at40  = round($rev * 0.40, 0);
    $at45  = round($rev * 0.45, 0);
    $diff  = $at45 - $sal;

    printf("%-14s %-8s %-6s %-8s %-10s %-8s %-8s | %-8s %-8s %-8s\n",
        $e->iqama_number,
        mb_substr($e->city ?? '—', 0, 6),
        $e->grade ?? '—',
        $e->total_orders,
        number_format($rev, 0),
        number_format($sal, 0),
        $ratio . '%',
        number_format($at40, 0),
        number_format($at45, 0),
        ($diff >= 0 ? '+' : '') . number_format($diff, 0)
    );
    $totalRevenue += $rev;
    $totalSalary  += $sal;
    $totalAt40    += $at40;
    $totalAt45    += $at45;
}

echo str_repeat('=', 90) . "\n";
$avgRatio = round(($totalSalary / $totalRevenue) * 100, 1);
printf("TOTAL: إيراد=%-10s راتب=%-10s نسبة=%s%%\n",
    number_format($totalRevenue, 0),
    number_format($totalSalary, 0),
    $avgRatio
);
printf("لو 40%%: إجمالي رواتب = %s | توفير = %s\n",
    number_format($totalAt40, 0),
    number_format($totalSalary - $totalAt40, 0)
);
printf("لو 45%%: إجمالي رواتب = %s | توفير = %s\n",
    number_format($totalAt45, 0),
    number_format($totalSalary - $totalAt45, 0)
);

// --- تأثير التغيير على كل موظف ---
echo "\n\n--- من سيرتفع راتبه لو 45%؟ (يعني هو الحين أقل من 45%) ---\n";
$gainers = $entries->filter(fn($e) => ($e->total_salary / $e->total_revenue) < 0.45);
$losers  = $entries->filter(fn($e) => ($e->total_salary / $e->total_revenue) > 0.45);
echo "  عدد مستفيدين (راتبهم سيزيد): " . $gainers->count() . "\n";
echo "  عدد خاسرين (راتبهم سينخفض): " . $losers->count() . "\n";

echo "\n--- من سيرتفع راتبه لو 40%؟ ---\n";
$gainers40 = $entries->filter(fn($e) => ($e->total_salary / $e->total_revenue) < 0.40);
$losers40  = $entries->filter(fn($e) => ($e->total_salary / $e->total_revenue) > 0.40);
echo "  عدد مستفيدين (راتبهم سيزيد): " . $gainers40->count() . "\n";
echo "  عدد خاسرين (راتبهم سينخفض): " . $losers40->count() . "\n";

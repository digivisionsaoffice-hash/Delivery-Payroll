<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== الصلاحيات =====
        $permissions = [
            // لوحة التحكم
            'view dashboard',
            // الموظفون
            'view employees', 'create employees', 'edit employees', 'delete employees',
            // الرواتب
            'view payroll', 'create payroll', 'approve payroll', 'export payroll',
            // الاستيراد
            'import data', 'view imports',
            // التقارير
            'view reports', 'export reports',
            // المنصات
            'view platforms', 'manage platforms',
            // الإعدادات
            'manage settings', 'manage users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ===== الأدوار =====
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'view dashboard', 'view employees', 'create employees', 'edit employees',
            'view payroll', 'create payroll', 'approve payroll', 'export payroll',
            'import data', 'view imports', 'view reports', 'export reports',
            'view platforms',
        ]);

        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            'view dashboard', 'view employees',
            'view payroll', 'create payroll', 'export payroll',
            'import data', 'view imports', 'view reports', 'export reports',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions(['view dashboard', 'view employees', 'view payroll', 'view reports']);

        // ===== المستخدم الافتراضي =====
        $admin = User::firstOrCreate(
            ['email' => 'admin@walim.test'],
            [
                'name'     => 'مدير النظام',
                'password' => bcrypt('Walim@2026'),
            ]
        );
        $admin->assignRole('super_admin');

        // ===== الفروع =====
        $branches = [
            ['name' => 'الرياض - حي النخيل', 'city' => 'الرياض', 'region' => 'الرياض'],
            ['name' => 'الرياض - حي العليا', 'city' => 'الرياض', 'region' => 'الرياض'],
            ['name' => 'جدة - حي الحمدانية', 'city' => 'جدة', 'region' => 'مكة المكرمة'],
            ['name' => 'الدمام', 'city' => 'الدمام', 'region' => 'الشرقية'],
        ];
        foreach ($branches as $b) {
            Branch::firstOrCreate(['name' => $b['name']], $b);
        }

        // ===== المنصات =====
        $platforms = [
            [
                'name'         => 'نينجا',
                'name_en'      => 'Ninja',
                'billing_type' => 'per_order',
                'is_active'    => true,
            ],
            [
                'name'         => 'هنقرستيشن',
                'name_en'      => 'HungerStation',
                'billing_type' => 'per_order',
                'is_active'    => true,
            ],
            [
                'name'         => 'جاهز',
                'name_en'      => 'Jahez',
                'billing_type' => 'tiered',
                'is_active'    => true,
            ],
        ];
        foreach ($platforms as $p) {
            Platform::firstOrCreate(['name_en' => $p['name_en']], $p);
        }
    }
}

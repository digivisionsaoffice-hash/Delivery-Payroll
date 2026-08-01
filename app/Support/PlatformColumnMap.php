<?php

namespace App\Support;

/**
 * ===================================================================
 * PlatformColumnMap — مرجع الأعمدة لكل منصة
 * ===================================================================
 *
 * المفاتيح هي report_format المخزون في جدول platforms:
 *   ninja        → نينجا
 *   keeta_orders → كيتا بالطلبات
 *   keeta_slabs  → كيتا بالشرائح
 *   hunger       → هنقرستيشن
 *   jahez        → جاهز
 *   generic      → عام (أي تطبيق آخر)
 *
 * جميع الأنواع تُستورد بـ sheet_type = 'app_report' في قاعدة البيانات
 * المنصة (platform.report_format) هي التي تحدد شكل القالب والأعمدة
 */
class PlatformColumnMap
{
    // ===================================================================
    // تعريفات الأنواع حسب report_format
    // ===================================================================

    public static function get(string $reportFormat): array
    {
        return match($reportFormat) {

            // -----------------------------------------------------------
            // نينجا — التقرير النهائي
            // -----------------------------------------------------------
            'ninja' => [
                'label'      => 'تقرير نينجا النهائي',
                'id_as_text' => false,
                'columns'    => [
                    'Date', 'supplier_id', 'Supplier Name', 'delivery_returning_perc',
                    'captain_id', 'shift_id', 'captain_name', 'branch_name',
                    'wallet_transaction_note', 'Working Hour', 'Dynamic_Per_Hour',
                    'Orders', 'Suppliers costs', 'Bouns Ftr', 'Adjustments +-',
                    'Net Cost', 'Vat 15%', 'Total Dues',
                ],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => [
                    'captain_id يمكن أن يكون رقماً أو نصاً',
                    'Adjustments +- قد تكون سالبة (تسويات)',
                    'سطور التسويات: Suppliers costs = 0 وAdjustments ≠ 0',
                ],
                // الأعمدة التي تُستورد فعلياً (مدمجة في الحسبة)
                'used_in_calc' => [
                    'Date', 'captain_id', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Bouns Ftr', 'Adjustments +-',
                ],
                // أعمدة موجودة في الملف لكن لا تدخل الحسبة حالياً
                'info_only' => [
                    'supplier_id', 'Supplier Name', 'delivery_returning_perc', 'shift_id',
                    'captain_name', 'branch_name', 'wallet_transaction_note',
                    'Dynamic_Per_Hour', 'Net Cost', 'Vat 15%', 'Total Dues',
                ],
                'map' => [
                    'date'             => ['date'],
                    'supplier_id'      => ['supplier_id', 'supplierid', 'supplier id', 'supplier_no'],
                    'supplier_name'    => ['supplier_name', 'supplier name', 'suppliername'],
                    'contract_type'    => ['contract_type', 'contract type', 'contracttype'],
                    'captain_id'       => ['captain_id', 'captainid', 'captain id', 'driver_id', 'driverid'],
                    'shift_id'         => ['shift_id', 'shiftid', 'shift id'],
                    'captain_name'     => ['captain_name', 'captain name', 'captainname', 'driver_name', 'driver name'],
                    'branch_name'      => ['branch_name', 'branch name', 'branchname'],
                    'wallet_note'      => ['wallet_transaction_note', 'wallet transaction note', 'wallettransactionnote', 'wallet_note', 'note'],
                    'working_hours'    => ['working_hour', 'working hour', 'workinghour', 'working_hours', 'hours'],
                    'dynamic_per_hour' => ['dynamic_per_hour', 'dynamic per hour', 'dynamicperhour', 'dynamic'],
                    'orders'           => ['orders', 'order', 'total_orders', 'total orders'],
                    'suppliers_costs'  => ['suppliers_costs', 'suppliers costs', 'supplierscosts', 'cost', 'costs', 'revenue', 'amount'],
                    'bonus_ftr'        => ['bouns_ftr', 'bonus_ftr', 'bonus ftr', 'bonusftr', 'ftr_bonus', 'bouns ftr'],
                    'adjustments'      => ['adjustments_-', 'adjustments +-', 'adjustments', 'adjustment', 'adjustments+_-'],
                    'net_cost'         => ['net_cost', 'net cost', 'netcost'],
                    'vat_15'           => ['vat_15', 'vat 15%', 'vat15', 'vat', 'vat_15_percent'],
                    'total_dues'       => ['total_dues', 'total dues', 'totaldues', 'total'],
                ],
            ],

            // -----------------------------------------------------------
            // كيتا — بالطلبات (Per Order)
            // -----------------------------------------------------------
            'keeta_orders' => [
                'label'      => 'تقرير كيتا النهائي — بالطلبات',
                'id_as_text' => true,
                'columns'    => [
                    'Date', 'captain_id', 'captain_name', 'shift_id',
                    'Working Hour', 'Orders', 'Suppliers costs',
                    'Adjustments +-', 'Net Cost', 'Total Dues',
                ],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => [
                    '⚠️ captain_id في كيتا رقم طويل — العمود مُعَرَّف كـ "نص" في القالب تلقائياً',
                    'لا يوجد supplier_id أو Dynamic_Per_Hour في كيتا',
                ],
                'used_in_calc' => [
                    'Date', 'captain_id', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Adjustments +-',
                ],
                'info_only' => [
                    'captain_name', 'shift_id', 'Net Cost', 'Total Dues',
                ],
                'map' => [
                    'date'            => ['date'],
                    'captain_id'      => ['captain_id', 'captainid', 'captain id', 'driver_id', 'id', 'captain'],
                    'captain_name'    => ['captain_name', 'captain name', 'captainname', 'driver_name', 'name'],
                    'shift_id'        => ['shift_id', 'shiftid', 'shift id', 'shift'],
                    'working_hours'   => ['working_hour', 'working hour', 'workinghour', 'working_hours', 'hours'],
                    'orders'          => ['orders', 'order', 'total_orders'],
                    'suppliers_costs' => ['suppliers_costs', 'suppliers costs', 'supplierscosts', 'cost', 'costs', 'amount', 'revenue'],
                    'adjustments'     => ['adjustments_-', 'adjustments +-', 'adjustments', 'adjustment'],
                    'net_cost'        => ['net_cost', 'net cost', 'netcost'],
                    'total_dues'      => ['total_dues', 'total dues', 'totaldues', 'total'],
                ],
            ],

            // -----------------------------------------------------------
            // كيتا — بالشرائح (Slabs)
            // -----------------------------------------------------------
            'keeta_slabs' => [
                'label'      => 'تقرير كيتا النهائي — بالشرائح',
                'id_as_text' => true,
                'columns'    => [
                    'معرّف الشريك', 'اسم الشريك', 'دورة الفاتورة', 'معرّف سائق التوصيل', 'اسم سائق التوصيل',
                    'متاح', 'السبب', 'أيام الاتصال عبر الإنترنت', 'ساعات الاتصال اليومي/ساعة', 'ساعات الاتصال اليومي خلال وقت الذروة/ساعة',
                    'الطلبات المُسلمة', 'مسافة التوصيل', 'التسعير حسب الطلب', 'المسافة من ارتفاع السعر.',
                    'حوافز سعة الطلب المتاحة (السعة)', 'حوافز تجربة التوصيل', 'DIGY', 'الإلغاء',
                    'الأنشطة والمكافآت الأخرى', 'الخصم', 'تعويض عن تلف الطعام', 'رسوم خدمة التسجيل',
                    'تعديل آخر', 'الإكرامية (باستثناء الضريبة)', 'خصم TGA (بدون ضريبة القيمة المضافة).', 'إجمالي المبلغ المستحق2'
                ],
                'required'   => ['دورة الفاتورة', 'معرّف سائق التوصيل', 'الطلبات المُسلمة'],
                'notes'      => [
                    '⚠️ captain_id في كيتا رقم طويل — العمود مُعَرَّف كـ "نص" في القالب تلقائياً',
                    'الخصم سيُسجل كـ suppliers_costs',
                    'تعويض عن تلف الطعام و خصم TGA يسجل في خصومات التطبيق الإضافية لكيتا'
                ],
                'used_in_calc' => [
                    'دورة الفاتورة', 'معرّف سائق التوصيل', 'ساعات الاتصال اليومي/ساعة', 'الطلبات المُسلمة',
                    'الخصم', 'حوافز سعة الطلب المتاحة (السعة)', 'حوافز تجربة التوصيل', 'تعديل آخر', 'تعويض عن تلف الطعام', 'خصم TGA (بدون ضريبة القيمة المضافة).'
                ],
                'info_only' => [
                    'اسم سائق التوصيل', 'إجمالي المبلغ المستحق2',
                    'رسوم خدمة التسجيل', 'الإلغاء', 'DIGY', 'المسافة من ارتفاع السعر.', 'التسعير حسب الطلب', 'مسافة التوصيل',
                    'أيام الاتصال عبر الإنترنت', 'ساعات الاتصال اليومي خلال وقت الذروة/ساعة', 'السبب', 'متاح', 'اسم الشريك', 'معرّف الشريك', 'الأنشطة والمكافآت الأخرى', 'الإكرامية (باستثناء الضريبة)'
                ],
                'map' => [
                    'date'            => ['دورة الفاتورة', 'دورة_الفاتورة', 'date'],
                    'captain_id'      => ['معرّف سائق التوصيل', 'معرّف_سائق_التوصيل', 'معرف سائق التوصيل', 'captain_id'],
                    'captain_name'    => ['اسم سائق التوصيل', 'اسم_سائق_التوصيل', 'captain_name'],
                    'working_hours'   => ['ساعات الاتصال اليومي/ساعة', 'ساعات_الاتصال_اليومي_ساعة', 'ساعات_الاتصال_اليومي', 'ساعات الاتصال اليومي المتاحة', 'working_hour'],
                    'orders'          => ['الطلبات المُسلمة', 'الطلبات_المُسلمة', 'الطلبات المسلمة', 'الطلبات المكتملة لساعات الاتصال اليومي خلال وقت الذروة', 'orders'],
                    'bonus_capacity'  => ['حوافز سعة الطلب المتاحة (السعة)', 'حوافز_سعة_الطلب_المتاحة_السعة', 'حوافز_سعة_الطلب_المتاحة_(السعة)', 'حافز سعة'],
                    'bonus_trial'     => ['حوافز تجربة التوصيل', 'حوافز_تجربة_التوصيل', 'حافز تجربة'],
                    'suppliers_costs' => ['الخصم'],
                    'adjustments'     => ['تعديل آخر', 'تعديل_آخر', 'adjustments_-', 'adjustments'],
                    'food_damage'     => ['تعويض تلف الطعام', 'تعويض_تلف_الطعام', 'تعويض عن تلف الطعام', 'تعويض_عن_تلف_الطعام'],
                    'bonus_other'     => ['الأنشطة والمكافآت الأخرى', 'الأنشطة_والمكافآت_الأخرى', 'الأنشطة ومكافآت أخرى'],
                    'tga_discount'    => ['خصم tga (بدون ضريبة القيمة المضافة).', 'خصم_tga_بدون_ضريبة_القيمة_المضافة.', 'tga خصم الإيرادات (استثناء ضريبة القيمة المضافة)'],
                    'total_dues'      => ['إجمالي المبلغ المستحق 2', 'إجمالي المبلغ المستحق2', 'إجمالي_المبلغ_المستحق2', 'إجمالي المبلغ المستحق (بدون ضريبة القيمة المضافة)'],
                    'supplier_id'     => ['معرّف الشريك', 'معرّف_الشريك', 'معرف الشريك'],
                    'supplier_name'   => ['اسم الشريك', 'اسم_الشريك'],
                    'available'       => ['متاح'],
                    'reason'          => ['السبب'],
                    'online_days'     => ['أيام الاتصال عبر الإنترنت', 'أيام_الاتصال_عبر_الإنترنت'],
                    'delivery_distance' => ['مسافة التوصيل', 'مسافة_التوصيل'],
                    'pricing_per_order' => ['التسعير حسب الطلب', 'التسعير_حسب_الطلب'],
                    'distance_from'   => ['المسافة من ارتفاع السعر.', 'المسافة_من_ارتفاع_السعر.', 'المسافة من'],
                    'digy'            => ['digy'],
                    'cancellation'    => ['الإلغاء'],
                    'food_damage_compensation' => ['تعويض عن تلف الطعام', 'تعويض_عن_تلف_الطعام', 'تعويض عن تلف الطعام '],
                    'registration_service_fee' => ['رسوم خدمة التسجيل', 'رسوم_خدمة_التسجيل', 'رسوم خدمة التوصيل'],
                    'tips'            => ['الإكرامية (باستثناء الضريبة)', 'الإكرامية_باستثناء_الضريبة'],
                ],
            ],

            // -----------------------------------------------------------
            // هنقرستيشن — التقرير النهائي
            // -----------------------------------------------------------
            'hunger' => [
                'label'      => 'تقرير هنقرستيشن النهائي',
                'id_as_text' => false,
                'columns'    => [
                    'Date', 'supplier_id', 'Supplier Name', 'captain_id',
                    'captain_name', 'branch_name', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Adjustments +-', 'Net Cost', 'Total Dues',
                ],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => [
                    'مشابه لنينجا لكن بدون shift_id وDynamic_Per_Hour',
                ],
                'used_in_calc' => [
                    'Date', 'captain_id', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Adjustments +-',
                ],
                'info_only' => [
                    'supplier_id', 'Supplier Name', 'captain_name',
                    'branch_name', 'Net Cost', 'Total Dues',
                ],
                'map' => [
                    'date'            => ['date'],
                    'supplier_id'     => ['supplier_id', 'supplierid', 'supplier id'],
                    'supplier_name'   => ['supplier_name', 'supplier name'],
                    'captain_id'      => ['captain_id', 'captainid', 'captain id', 'driver_id'],
                    'captain_name'    => ['captain_name', 'captain name', 'driver_name'],
                    'branch_name'     => ['branch_name', 'branch name', 'branchname'],
                    'working_hours'   => ['working_hour', 'working hour', 'working_hours', 'hours'],
                    'orders'          => ['orders', 'order', 'total_orders'],
                    'suppliers_costs' => ['suppliers_costs', 'suppliers costs', 'cost', 'costs', 'amount'],
                    'adjustments'     => ['adjustments_-', 'adjustments +-', 'adjustments', 'adjustment'],
                    'net_cost'        => ['net_cost', 'net cost', 'netcost'],
                    'total_dues'      => ['total_dues', 'total dues', 'totaldues', 'total'],
                ],
            ],

            // -----------------------------------------------------------
            // جاهز — التقرير النهائي
            // -----------------------------------------------------------
            'jahez' => [
                'label'      => 'تقرير جاهز النهائي',
                'id_as_text' => false,
                'columns'    => [
                    'Date', 'captain_id', 'captain_name', 'city',
                    'Working Hour', 'Orders', 'Suppliers costs',
                    'Adjustments +-', 'Net Cost', 'Total Dues',
                ],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => [
                    'يحتوي على عمود city بدلاً من branch_name',
                ],
                'used_in_calc' => [
                    'Date', 'captain_id', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Adjustments +-',
                ],
                'info_only' => [
                    'captain_name', 'city', 'Net Cost', 'Total Dues',
                ],
                'map' => [
                    'date'            => ['date'],
                    'captain_id'      => ['captain_id', 'captainid', 'captain id', 'driver_id', 'id'],
                    'captain_name'    => ['captain_name', 'captain name', 'driver_name', 'name'],
                    'branch_name'     => ['city', 'branch_name', 'branch', 'region'],
                    'working_hours'   => ['working_hour', 'working hour', 'working_hours', 'hours'],
                    'orders'          => ['orders', 'order', 'total_orders'],
                    'suppliers_costs' => ['suppliers_costs', 'suppliers costs', 'cost', 'costs', 'amount'],
                    'adjustments'     => ['adjustments_-', 'adjustments +-', 'adjustments'],
                    'net_cost'        => ['net_cost', 'net cost', 'netcost'],
                    'total_dues'      => ['total_dues', 'total dues', 'total'],
                ],
            ],

            // -----------------------------------------------------------
            // عام (أي تطبيق آخر)
            // -----------------------------------------------------------
            'generic' => [
                'label'      => 'تقرير التطبيق النهائي',
                'id_as_text' => false,
                'columns'    => [
                    'Date', 'captain_id', 'captain_name',
                    'Working Hour', 'Orders', 'Suppliers costs',
                    'Adjustments +-', 'Net Cost',
                ],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => [
                    'قالب مرن يقبل الأعمدة بأي ترتيب',
                    'يمكنك إضافة أعمدة إضافية وستُكتشف تلقائياً',
                ],
                'used_in_calc' => [
                    'Date', 'captain_id', 'Working Hour', 'Orders',
                    'Suppliers costs', 'Adjustments +-',
                ],
                'info_only' => ['captain_name', 'Net Cost'],
                'map' => [
                    'date'            => ['date'],
                    'captain_id'      => ['captain_id', 'captainid', 'captain id', 'driver_id', 'id'],
                    'captain_name'    => ['captain_name', 'captain name', 'driver_name', 'name'],
                    'working_hours'   => ['working_hour', 'working hour', 'working_hours', 'hours'],
                    'orders'          => ['orders', 'order', 'total_orders'],
                    'suppliers_costs' => ['suppliers_costs', 'suppliers costs', 'cost', 'costs', 'amount'],
                    'adjustments'     => ['adjustments_-', 'adjustments +-', 'adjustments'],
                    'net_cost'        => ['net_cost', 'net cost', 'netcost'],
                    'total_dues'      => ['total_dues', 'total dues', 'total'],
                ],
            ],

            default => [
                'label'      => 'تقرير التطبيق النهائي',
                'id_as_text' => false,
                'columns'    => ['Date', 'captain_id', 'captain_name', 'Working Hour', 'Orders', 'Suppliers costs', 'Adjustments +-'],
                'required'   => ['Date', 'captain_id', 'Orders', 'Suppliers costs'],
                'notes'      => ['نوع غير معروف — سيتم معالجته كتقرير عام'],
                'used_in_calc' => ['Date', 'captain_id', 'Working Hour', 'Orders', 'Suppliers costs', 'Adjustments +-'],
                'info_only' => ['captain_name'],
                'map' => [
                    'date'            => ['date'],
                    'captain_id'      => ['captain_id', 'captainid', 'captain id', 'driver_id', 'id'],
                    'captain_name'    => ['captain_name', 'captain name', 'driver_name', 'name'],
                    'working_hours'   => ['working_hour', 'working hour', 'working_hours', 'hours'],
                    'orders'          => ['orders', 'order', 'total_orders'],
                    'suppliers_costs' => ['suppliers_costs', 'suppliers costs', 'cost', 'costs', 'amount'],
                    'adjustments'     => ['adjustments_-', 'adjustments +-', 'adjustments'],
                ],
            ],
        };
    }

    // ===================================================================
    // أنواع الأوراق الأخرى (الخصومات، المصاريف)
    // ===================================================================

    public static function getSheetDef(string $sheetType): array
    {
        return match($sheetType) {
            'id_changes' => [
                'label'   => 'تحديث المعرفات حسب كشف التشغيل',
                'id_as_text' => true,
                'columns' => [
                    'Sr. No', 'Riders Name', 'Iqama Number',
                    'captain_id', 'ID Name', 'Start Date/ details',
                    'End Date', 'City', 'app'
                ],
                'required' => ['Iqama Number', 'captain_id'],
                'notes'    => [
                    '⚠️ captain_id يجب تنسيقه كـ "نص" إذا كان طويلاً (كيتا)',
                    'Start Date وEnd Date: تاريخ بدء وانتهاء صلاحية الـ ID',
                    'عمود (التسويات): يستخدم للتوزيع التفاعلي للتسويات المجهولة',
                    'عمود (banned): اكتب yes أو نعم إذا كان الموظف انحظر واستخرجت له ID جديد لتفادي خطأ التضارب',
                ],
                'map' => [
                    'sr_no'        => ['sr_no', 'sr. no', 'sr no', 'no', '#', 'رقم'],
                    'riders_name'  => ['riders_name', 'riders name', 'driver_name', 'name', 'اسم السائق', 'actual_riders_name', 'actual_rider_name'],
                    'iqama_number' => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة', 'actual_iqama_number', 'actual_iqama'],
                    'captain_id'   => ['captain_id', 'captainid', 'captain id', 'id', 'driver_id', 'رقم التطبيق', 'معرف الكابتن', 'رقم الكابتن'],
                    'id_name'      => ['id_name', 'id name', 'idname'],
                    'start_date'   => ['start_date_details', 'start date/ details', 'start_date', 'start date', 'startdate', 'تاريخ البداية'],
                    'end_date'     => ['end_date', 'end date', 'enddate', 'تاريخ الانتهاء'],
                    'city'         => ['city', 'المدينة'],
                    'app'          => ['app', 'application', 'التطبيق'],
                    'adjustment'   => ['adjustment', 'adjustments', 'تسوية', 'تسويات', 'التسويات'],
                    'unknown_revenue' => ['unknown_revenue', 'الإيراد المجهول'],
                    'orders'       => ['orders', 'الطلبات'],
                    'shift_id'     => ['shift_id', 'shift id', 'shift'],
                    'banned'       => ['banned', 'ban', 'محظور', 'حظر', 'انحظر'],
                ],
            ],
            'unified_deductions' => [
                'label'    => 'الخصومات المجمعة (سلف، مخالفات، قطع غيار)',
                'id_as_text' => false,
                'columns'  => ['Iqama Number', 'Riders Name', 'Amount', 'Type'],
                'required' => ['Iqama Number', 'Amount', 'Type'],
                'notes'    => [
                    'Amount: المبلغ بالريال',
                    'Type: نوع السلفة (السلف النقدية، المخالفات المرورية، قطع الغيار)'
                ],
                'map' => [
                    'iqama_number' => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة'],
                    'riders_name'  => ['riders_name', 'riders name', 'name', 'اسم السائق', 'اسم الموظف'],
                    'amount'       => ['amount', 'المبلغ', 'القيمة', 'قيمة السلفة'],
                    'type'         => ['type', 'advance_type', 'نوع السلفة', 'النوع'],
                ],
            ],
            'pre_salary' => [
                'label'    => 'رواتب مدد (مُسبقة)',
                'id_as_text' => false,
                'columns'  => ['Iqama Number', 'Riders Name', 'Amount', 'Date', 'Notes'],
                'required' => ['Iqama Number', 'Amount'],
                'notes'    => ['Amount: المبلغ بالريال'],
                'map' => [
                    'iqama_number' => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة'],
                    'riders_name'  => ['riders_name', 'riders name', 'name', 'اسم السائق'],
                    'amount'       => ['amount', 'المبلغ', 'قيمة السلفة'],
                    'date'         => ['date', 'التاريخ'],
                    'notes'        => ['notes', 'ملاحظات'],
                ],
            ],
            'maintenance' => [
                'label'    => 'الصيانة اليدوية',
                'id_as_text' => false,
                'columns'  => ['Iqama Number', 'Riders Name', 'Plate number', 'Spare parts', 'the reason', 'comments', 'Discount Amount'],
                'required' => ['Iqama Number', 'Discount Amount'],
                'notes'    => ['Discount Amount: المبلغ المُخصوم'],
                'map' => [
                    'iqama_number' => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة'],
                    'riders_name'  => ['riders_name', 'riders name', 'name', 'اسم السائق'],
                    'plate_number' => ['plate_number', 'plate number', 'رقم اللوحة'],
                    'spare_parts'  => ['spare_parts', 'spare parts', 'قطع الغيار'],
                    'reason'       => ['the_reason', 'the reason', 'reason', 'السبب'],
                    'comments'     => ['comments', 'ملاحظات'],
                    'amount'       => ['discount_amount', 'discount amount', 'amount', 'قيمة الخصم'],
                ],
            ],
            'penalties' => [
                'label'    => 'جزاءات الشركة',
                'id_as_text' => false,
                'columns'  => ['Iqama Number', 'Riders Name', 'Title of Violation', 'Discount Amount', 'Date', 'Notes'],
                'required' => ['Iqama Number', 'Discount Amount'],
                'notes'    => ['Discount Amount: مبلغ الجزاء'],
                'map' => [
                    'iqama_number'    => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة'],
                    'riders_name'     => ['riders_name', 'riders name', 'name', 'اسم السائق'],
                    'violation_title' => ['title_of_violation', 'title of violation', 'title', 'سبب الجزاء'],
                    'amount'          => ['discount_amount', 'discount amount', 'amount', 'قيمة الجزاء'],
                    'date'            => ['date', 'التاريخ'],
                    'notes'           => ['notes', 'ملاحظات'],
                ],
            ],
            'fuel', 'housing', 'packages' => [
                'label' => match($sheetType) {
                    'fuel'             => 'البنزين',
                    'housing'          => 'السكن',
                    'packages'         => 'الباقات (نت / شرائح)',
                },
                'id_as_text' => false,
                'columns'  => ['Iqama Number', 'Riders Name', 'Amount', 'Month', 'Notes'],
                'required' => ['Iqama Number', 'Amount'],
                'notes'    => ['تُخصم من عمولة نظام الـ 8 ريال فقط'],
                'map' => [
                    'iqama_number' => ['iqama_number', 'iqama number', 'iqama', 'رقم الإقامة'],
                    'riders_name'  => ['riders_name', 'riders name', 'name', 'اسم السائق'],
                    'amount'       => ['amount', 'المبلغ'],
                    'month'        => ['month', 'الشهر'],
                    'notes'        => ['notes', 'ملاحظات'],
                ],
            ],
            default => [],
        };
    }

    // ===================================================================
    // الأنواع المجمّعة للفورم
    // ===================================================================

    public static function sheetGroups(): array
    {
        return [
            'سجل استلام المعرفات' => [
                'id_changes' => 'تحديث المعرفات حسب كشف التشغيل',
            ],
            'الخصومات' => [
                'unified_deductions' => 'الخصومات المجمعة (سلف، مخالفات، قطع غيار)',
                'maintenance' => 'الصيانة اليدوية',
                'penalties'   => 'جزاءات الشركة',
                'pre_salary'  => 'رواتب مدد (مُسبقة)',
            ],
            'مصاريف العمولة' => [
                'fuel'             => 'البنزين',
                'housing'          => 'السكن',
                'packages'         => 'الباقات (نت / شرائح)',
            ],
        ];
    }

    // ===================================================================
    // resolveRow — تعرف ذكي على الأعمدة
    // ===================================================================

    /**
     * يأخذ صف من Excel ويُرجع بيانات معيارية بالمفاتيح الداخلية
     * يعمل مع report_format للمنصة أو sheetType للورقة
     */
    public static function resolveRow(array $row, string $reportFormat, bool $isAppReport = true): array
    {
        $def = $isAppReport ? self::get($reportFormat) : self::getSheetDef($reportFormat);
        if (empty($def) || empty($def['map'])) {
            return $row;
        }

        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $nk = self::normalizeKey((string)$key);
            $normalizedRow[$nk] = $value;
        }

        $resolved = [];
        foreach ($def['map'] as $internalKey => $aliases) {
            $found = false;
            foreach ($aliases as $alias) {
                $na = self::normalizeKey($alias);
                if (array_key_exists($na, $normalizedRow)) {
                    $resolved[$internalKey] = $normalizedRow[$na];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $resolved[$internalKey] = null;
            }
        }

        return $resolved;
    }

    /**
     * اكتشاف الأعمدة غير المعروفة (الموجودة في الملف ولكن غير مُعرَّفة في الخريطة)
     * يُستخدم للتنبيه على الحقول التي قد تحتوي على بيانات مهمة لم تُدرَج في الحسبة
     */
    public static function detectUnknownColumns(array $headers, string $reportFormat): array
    {
        $def = self::get($reportFormat);
        if (empty($def['map'])) return [];

        // جمع كل الأسماء المعروفة (normalized)
        $knownNormalized = [];
        foreach ($def['map'] as $aliases) {
            foreach ($aliases as $alias) {
                $knownNormalized[] = self::normalizeKey($alias);
            }
        }

        $unknown = [];
        foreach ($headers as $header) {
            $nh = self::normalizeKey((string)$header);
            if ($nh === '' || $nh === 'null') continue;
            if (!in_array($nh, $knownNormalized)) {
                $unknown[] = $header;
            }
        }

        return $unknown;
    }

    /**
     * تطبيع اسم العمود
     */
    public static function normalizeKey(string $key): string
    {
        $key = mb_strtolower(trim($key));
        $key = str_replace([' ', '-', '+', '%', '.', '/', '\\', '(', ')', '*', '#', '&'], '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        return trim($key, '_');
    }

    /**
     * الأسماء العربية للأنواع
     */
    public static function label(string $sheetType): string
    {
        if ($sheetType === 'app_report') return 'تقرير التطبيق النهائي';
        $def = self::getSheetDef($sheetType);
        return $def['label'] ?? $sheetType;
    }

    /**
     * هل هذا النوع هو تقرير تطبيق؟
     */
    public static function isAppReport(string $sheetType): bool
    {
        return $sheetType === 'app_report';
    }

    /**
     * الـ report_format المتاحة مع أسمائها للعرض في صفحة المنصات
     */
    public static function reportFormats(): array
    {
        return [
            'ninja'        => 'نينجا',
            'keeta_orders' => 'كيتا — بالطلبات (Per Order)',
            'keeta_slabs'  => 'كيتا — بالشرائح (Slabs)',
            'hunger'       => 'هنقرستيشن',
            'jahez'        => 'جاهز',
            'generic'      => 'عام / أخرى',
        ];
    }
}

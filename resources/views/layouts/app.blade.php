<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — وليم | نظام الرواتب والربحية</title>
    <meta name="description" content="نظام متكامل لإدارة رواتب السائقين والربحية لشركات التوصيل">

    <!-- Google Fonts - Cairo + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
    <!-- ApexCharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.css">

    <style>
        :root {
            /* ── Enterprise Clean Palette ── */
            --bg-primary:   #f4f5f7;
            --bg-secondary: #ffffff;
            --bg-card:      #ffffff;
            --bg-hover:     #ebecf0;
            --bg-hover2:    #dfe1e6;
            --border:       #dfe1e6;
            --border-light: #ebecf0;
            --accent:       #0052cc;
            --accent-hover: #0047b3;
            --accent-light: #4c9aff;
            --accent-glow:  rgba(0, 82, 204, 0.15);
            --accent-soft:  #deebff;
            --success:      #00875a;
            --success-soft: #e3fcef;
            --warning:      #ff991f;
            --warning-soft: #fffae6;
            --danger:       #de350b;
            --danger-soft:  #ffebe6;
            --info:         #00b8d9;
            --info-soft:    #e6fcff;
            --text-primary: #172b4d;
            --text-secondary:#42526e;
            --text-muted:   #5e6c84;
            --text-light:   #8993a4;
            --sidebar-w:    264px;
            --shadow-xs:    0 1px 3px rgba(9, 30, 66, 0.05), 0 1px 2px rgba(9, 30, 66, 0.04);
            --shadow-sm:    0 3px 8px rgba(9, 30, 66, 0.07), 0 1px 3px rgba(9, 30, 66, 0.05);
            --shadow-md:    0 5px 16px rgba(9, 30, 66, 0.09), 0 2px 6px rgba(9, 30, 66, 0.06);
            --shadow-lg:    0 10px 30px rgba(9, 30, 66, 0.12), 0 4px 12px rgba(9, 30, 66, 0.08);
            --radius-sm:    6px;
            --radius-md:    10px;
            --radius-lg:    14px;
            --radius-xl:    18px;
            --gradient-1:   linear-gradient(135deg, #0052cc 0%, #0747a6 100%);
            --gradient-2:   linear-gradient(135deg, #00875a 0%, #006644 100%);
            --gradient-3:   linear-gradient(135deg, #ff991f 0%, #e38318 100%);
            --gradient-4:   linear-gradient(135deg, #de350b 0%, #bf2600 100%);
            --gradient-blue:linear-gradient(135deg, #0052cc 0%, #0065ff 100%);
        }

        * { box-sizing: border-box; margin: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0; right: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--bg-secondary);
            border-left: 1px solid var(--border);
            box-shadow: -2px 0 20px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }

        .sidebar-logo {
            padding: 1.25rem 1.25rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo .logo-icon {
            width: 40px; height: 40px;
            background: var(--gradient-blue);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(37,99,235,0.35);
        }

        .sidebar-logo .logo-text {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-primary);
        }

        .sidebar-logo .logo-sub {
            font-size: 0.68rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 0.75rem 0; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .nav-section-title {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 0.75rem 1.25rem 0.3rem;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.55rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.855rem;
            font-weight: 500;
            border-radius: var(--radius-sm);
            margin: 0.08rem 0.6rem;
            transition: all 0.18s ease;
            position: relative;
        }

        .nav-item-custom:hover {
            background: var(--bg-hover2);
            color: var(--text-secondary);
        }

        .nav-item-custom.active {
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 600;
        }

        .nav-item-custom.active::before {
            content: '';
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 55%;
            background: var(--accent);
            border-radius: 3px 0 0 3px;
        }

        .nav-item-custom .nav-icon {
            width: 30px; height: 30px;
            background: var(--border-light);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
            transition: all 0.18s ease;
            color: var(--text-muted);
        }

        .nav-item-custom.active .nav-icon {
            background: var(--accent);
            color: white;
            box-shadow: 0 2px 8px rgba(37,99,235,0.3);
        }

        .nav-item-custom:hover .nav-icon {
            background: var(--bg-hover2);
            color: var(--text-secondary);
        }

        .sidebar-footer {
            padding: 0.85rem;
            border-top: 1px solid var(--border-light);
        }

        .user-card {
            display: flex; align-items: center; gap: 0.7rem;
            padding: 0.65rem 0.75rem;
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .user-card:hover { background: var(--bg-hover2); border-color: var(--border); }

        .user-avatar {
            width: 34px; height: 34px;
            background: var(--gradient-blue);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 0.78rem;
            color: white;
            flex-shrink: 0;
        }

        .user-name { font-size: 0.8rem; font-weight: 600; color: var(--text-primary); }
        .user-role { font-size: 0.67rem; color: var(--text-muted); }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-right: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 0 rgba(0,0,0,0.04);
            padding: 0 1.75rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .topbar-actions { display: flex; align-items: center; gap: 0.6rem; }

        .icon-btn {
            width: 36px; height: 36px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.18s;
            text-decoration: none;
        }

        .icon-btn:hover {
            background: var(--bg-hover2);
            color: var(--text-primary);
            border-color: var(--border);
        }

        .notification-dot { position: relative; }
        .notification-dot::after {
            content: '';
            position: absolute;
            top: 6px; left: 6px;
            width: 8px; height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }

        /* ===== PAGE CONTENT ===== */
        .page-content { padding: 1.5rem 1.75rem; flex: 1; }

        /* ===== KPI CARDS ===== */
        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.4rem 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: var(--shadow-xs);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* شريط ملوّن في الأعلى لكل كرت */
        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .kpi-card.kpi-blue::after   { background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .kpi-card.kpi-green::after  { background: linear-gradient(90deg, #059669, #34d399); }
        .kpi-card.kpi-yellow::after { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .kpi-card.kpi-red::after    { background: linear-gradient(90deg, #dc2626, #f87171); }

        .kpi-icon {
            width: 46px; height: 46px;
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 0.9rem;
        }

        .kpi-card.kpi-blue   .kpi-icon { background: var(--accent-soft); color: var(--accent); }
        .kpi-card.kpi-green  .kpi-icon { background: var(--success-soft); color: var(--success); }
        .kpi-card.kpi-yellow .kpi-icon { background: var(--warning-soft); color: var(--warning); }
        .kpi-card.kpi-red    .kpi-icon { background: var(--danger-soft);  color: var(--danger); }

        .kpi-value {
            font-size: 1.7rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .kpi-label { color: var(--text-muted); font-size: 0.78rem; font-weight: 500; }

        .kpi-trend {
            display: flex; align-items: center; gap: 0.3rem;
            font-size: 0.73rem;
            margin-top: 0.6rem;
            font-weight: 500;
        }

        .kpi-trend.up   { color: var(--success); }
        .kpi-trend.down { color: var(--danger); }

        /* ===== CHART CARDS ===== */
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.4rem 1.5rem;
            box-shadow: var(--shadow-xs);
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid var(--border-light);
        }

        .chart-title { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); }

        .chart-subtitle { font-size: 0.75rem; color: var(--text-muted); }

        /* ===== TABLES ===== */
        .walim-table { color: var(--text-primary) !important; border-collapse: separate; border-spacing: 0; }
        .walim-table thead th {
            background: #f8fafc !important;
            color: var(--text-muted) !important;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-color: var(--border) !important;
            border-top: none !important;
            padding: 0.7rem 1rem !important;
            white-space: nowrap;
        }

        .walim-table tbody td {
            border-color: var(--border-light) !important;
            padding: 0.8rem 1rem !important;
            font-size: 0.855rem;
            vertical-align: middle;
            color: var(--text-secondary);
        }

        .walim-table tbody tr:nth-child(even) td { background: #fafbfc; }
        .walim-table tbody tr:hover td { background: var(--accent-soft) !important; }

        /* ===== BADGES ===== */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.28rem 0.7rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .status-badge::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        .badge-profit  { background: var(--success-soft); color: var(--success); }
        .badge-loss    { background: var(--danger-soft);  color: var(--danger); }
        .badge-neutral { background: var(--warning-soft); color: var(--warning); }
        .badge-active  { background: var(--success-soft); color: var(--success); }
        .badge-inactive{ background: #f1f5f9; color: var(--text-muted); }
        .badge-pending { background: var(--warning-soft); color: var(--warning); }
        .badge-done    { background: var(--accent-soft);  color: var(--accent); }

        /* ===== BTN CUSTOM ===== */
        .btn-walim {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: var(--radius-sm);
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            font-size: 0.855rem;
            transition: all 0.18s;
            display: inline-flex; align-items: center; gap: 0.45rem;
            cursor: pointer;
        }

        .btn-walim:hover {
            background: var(--accent-hover);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .btn-walim:active { transform: translateY(0); }

        .btn-ghost {
            background: white;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            padding: 0.5rem 1.2rem;
            border-radius: var(--radius-sm);
            font-family: 'Cairo', sans-serif;
            font-weight: 500;
            font-size: 0.855rem;
            transition: all 0.18s;
            display: inline-flex; align-items: center; gap: 0.45rem;
            cursor: pointer;
        }

        .btn-ghost:hover {
            background: var(--bg-hover2);
            color: var(--text-primary);
            border-color: var(--text-light);
        }

        .btn-danger-soft {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid rgba(220,38,38,0.2);
            padding: 0.5rem 1.2rem;
            border-radius: var(--radius-sm);
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            font-size: 0.855rem;
            transition: all 0.18s;
            display: inline-flex; align-items: center; gap: 0.45rem;
            cursor: pointer;
        }

        .btn-danger-soft:hover { background: var(--danger); color: white; }

        /* ===== FORM CONTROLS ===== */
        .form-control-dark,
        .form-select-dark {
            background: white !important;
            border: 1px solid var(--border) !important;
            color: var(--text-primary) !important;
            border-radius: var(--radius-sm) !important;
            font-family: 'Cairo', sans-serif;
            font-size: 0.855rem;
            transition: border-color 0.18s, box-shadow 0.18s;
        }

        .form-control-dark:focus,
        .form-select-dark:focus {
            background: white !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px var(--accent-glow) !important;
            color: var(--text-primary) !important;
            outline: none;
        }

        .form-control-dark::placeholder { color: var(--text-light) !important; }

        .form-label-dark {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.35rem;
            display: block;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        .fade-in { animation: fadeInUp 0.35s ease both; }
        .fade-in-1 { animation-delay: 0.04s; }
        .fade-in-2 { animation-delay: 0.08s; }
        .fade-in-3 { animation-delay: 0.12s; }
        .fade-in-4 { animation-delay: 0.16s; }

        /* ===== ALERTS ===== */
        .alert-walim {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.9rem 1.1rem;
            display: flex; align-items: flex-start; gap: 0.7rem;
            box-shadow: var(--shadow-xs);
        }

        .alert-walim.alert-danger  { border-color: rgba(220,38,38,0.25);  background: var(--danger-soft); }
        .alert-walim.alert-success { border-color: rgba(5,150,105,0.25);  background: var(--success-soft); }
        .alert-walim.alert-warning { border-color: rgba(217,119,6,0.25);  background: var(--warning-soft); }
        .alert-walim.alert-info    { border-color: rgba(8,145,178,0.25);  background: var(--info-soft); }

        /* ===== DATATABLES LIGHT ===== */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: white !important;
            border: 1px solid var(--border) !important;
            color: var(--text-primary) !important;
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
            padding: 0.3rem 0.6rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            color: var(--text-muted) !important;
            font-size: 0.78rem;
        }

        /* Pagination */
        .page-link {
            background: white !important;
            border-color: var(--border) !important;
            color: var(--text-muted) !important;
            font-size: 0.8rem;
            font-family: 'Cairo', sans-serif;
        }
        .page-link:hover { background: var(--bg-hover2) !important; color: var(--accent) !important; }
        .page-item.active .page-link { background: var(--accent) !important; border-color: var(--accent) !important; color: white !important; }
        .page-item.disabled .page-link { background: var(--bg-hover) !important; }

        /* ===== SECTION HEADERS ===== */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.2rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex; align-items: center; gap: 0.5rem;
        }

        .section-title .section-icon {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            background: var(--accent-soft);
            color: var(--accent);
        }

        /* ===== DIVIDER ===== */
        .walim-divider {
            border: none;
            border-top: 1px solid var(--border-light);
            margin: 1.2rem 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-right: 0; }
            .page-content { padding: 1rem; }
        }

        /* ===== SCROLL ===== */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ===== MISC UTILITIES ===== */
        .text-accent { color: var(--accent) !important; }
        .text-success-custom { color: var(--success) !important; }
        .text-danger-custom { color: var(--danger) !important; }
        .bg-accent-soft { background: var(--accent-soft) !important; }
        .rounded-walim { border-radius: var(--radius-md) !important; }
        .shadow-walim { box-shadow: var(--shadow-sm) !important; }
    </style>

    @stack('styles')
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🚀</div>
        <div>
            <div class="logo-text">وليم</div>
            <div class="logo-sub">نظام الرواتب والربحية</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">الرئيسية</div>

        <a href="{{ route('dashboard') }}"
           class="nav-item-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-grid-fill"></i></div>
            لوحة التحكم
        </a>

        <div class="nav-section-title mt-2">البيانات</div>

        <a href="{{ route('employees.index') }}"
           class="nav-item-custom {{ request()->routeIs('employees.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-people-fill"></i></div>
            الموظفون والسائقون
        </a>

        <a href="{{ route('platforms.index') }}"
           class="nav-item-custom {{ request()->routeIs('platforms.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-shop"></i></div>
            منصات التوصيل
        </a>

        <a href="{{ route('branches.index') }}"
           class="nav-item-custom {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-diagram-2-fill"></i></div>
            الفروع
        </a>

        <a href="{{ route('cities.index') }}"
           class="nav-item-custom {{ request()->routeIs('cities.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-geo-alt-fill"></i></div>
            المدن والمناطق
        </a>

        <div class="nav-section-title mt-2">العمليات</div>

        <a href="{{ route('import.index') }}"
           class="nav-item-custom {{ request()->routeIs('import.index') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-cloud-upload-fill"></i></div>
            استيراد البيانات
        </a>

        <a href="{{ route('import.processing') }}"
           class="nav-item-custom {{ request()->routeIs('import.processing', 'import.reconciliation', 'import.records') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-cpu-fill"></i></div>
            معالجة البيانات
        </a>

        <a href="{{ route('payroll.index') }}"
           class="nav-item-custom {{ request()->routeIs('payroll.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-cash-stack"></i></div>
            مسير الرواتب
        </a>

        <div class="nav-section-title mt-2">التحليل</div>

        <a href="{{ route('profitability.index') }}"
           class="nav-item-custom {{ request()->routeIs('profitability.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-graph-up-arrow"></i></div>
            الربحية والأداء
        </a>

        <a href="{{ route('reports.index') }}"
           class="nav-item-custom {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
            التقارير
        </a>
        @if(request()->routeIs('reports.*'))
        <div style="padding-right:2.2rem">
            <a href="{{ route('reports.performance') }}" class="nav-item-custom {{ request()->routeIs('reports.performance') ? 'active' : '' }}" style="font-size:0.78rem; padding:0.3rem 0.75rem">
                <i class="bi bi-bar-chart-line" style="width:16px;text-align:center"></i> الأداء المقارن
            </a>
            <a href="{{ route('reports.deductions') }}" class="nav-item-custom {{ request()->routeIs('reports.deductions') ? 'active' : '' }}" style="font-size:0.78rem; padding:0.3rem 0.75rem">
                <i class="bi bi-wallet2" style="width:16px;text-align:center"></i> الخصومات
            </a>
            <a href="{{ route('reports.inactive') }}" class="nav-item-custom {{ request()->routeIs('reports.inactive') ? 'active' : '' }}" style="font-size:0.78rem; padding:0.3rem 0.75rem">
                <i class="bi bi-person-dash" style="width:16px;text-align:center"></i> بدون نشاط
            </a>
            <a href="{{ route('reports.anomalies') }}" class="nav-item-custom {{ request()->routeIs('reports.anomalies') ? 'active' : '' }}" style="font-size:0.78rem; padding:0.3rem 0.75rem">
                <i class="bi bi-exclamation-triangle" style="width:16px;text-align:center"></i> الشذوذات
            </a>
        </div>
        @endif

        @can('manage settings')
        <div class="nav-section-title mt-2">الإدارة</div>
        <a href="{{ route('settings.index') }}"
           class="nav-item-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-gear-fill"></i></div>
            الإعدادات
        </a>
        <a href="{{ route('users.index') }}"
           class="nav-item-custom {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <div class="nav-icon"><i class="bi bi-person-lock"></i></div>
            المستخدمون
        </a>
        @endcan
    </nav>

    <div class="sidebar-footer">
        <div class="user-card" onclick="document.getElementById('logout-form').submit()">
            <div class="user-avatar">{{ substr(auth()->user()->name ?? 'M', 0, 1) }}</div>
            <div>
                <div class="user-name">{{ auth()->user()->name ?? 'مدير النظام' }}</div>
                <div class="user-role">{{ auth()->user()->getRoleNames()->first() ?? 'super_admin' }}</div>
            </div>
            <i class="bi bi-box-arrow-left me-auto" style="color: var(--text-muted); font-size:0.85rem;"></i>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
</aside>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="icon-btn d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">@yield('page-title', 'لوحة التحكم')</div>
        </div>
        <div class="topbar-actions">
            @if(isset($pageActions))
                {!! $pageActions !!}
            @endif
            <a href="#" class="icon-btn notification-dot" title="الإشعارات">
                <i class="bi bi-bell"></i>
            </a>
            <a href="{{ route('settings.index') }}" class="icon-btn" title="الإعدادات">
                <i class="bi bi-gear"></i>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="mx-4 mt-3 alert-walim alert-success fade-in">
        <i class="bi bi-check-circle-fill" style="color: var(--success); flex-shrink:0;"></i>
        <div>
            {{ session('success') }}
            @if(session('failed_excel_link'))
                <a href="{{ session('failed_excel_link') }}" class="ms-2 btn btn-sm btn-light text-danger fw-bold" style="border-radius: 6px;">
                    <i class="bi bi-download"></i> تحميل سجلات الإقامات المفقودة
                </a>
            @endif
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="mx-4 mt-3 alert-walim alert-danger fade-in">
        <i class="bi bi-exclamation-circle-fill" style="color: var(--danger); flex-shrink:0;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <!-- Page Content -->
    <div class="page-content">
        @yield('content')
    </div>
</div>

<!-- Modals -->
@stack('modals')

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
<script>
// إعداد Chart.js للتصميم الفاتح
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#e2e8f0';
Chart.defaults.font.family = 'Cairo';

// دالة مساعدة لتنسيق الأرقام
function formatMoney(n) {
    return new Intl.NumberFormat('ar-SA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(n) + ' ر.س';
}

function formatNum(n) {
    return new Intl.NumberFormat('ar-SA').format(n);
}

// إعدادات ApexCharts الافتراضية
ApexCharts.exec = ApexCharts.exec || function() {};
window.walimChartDefaults = {
    fontFamily: 'Cairo, sans-serif',
    foreColor: '#64748b',
    toolbar: { show: false },
    colors: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2'],
    grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    tooltip: { theme: 'light' },
    legend: { fontFamily: 'Cairo', fontSize: '12px' }
};
</script>

@stack('scripts')
</body>
</html>

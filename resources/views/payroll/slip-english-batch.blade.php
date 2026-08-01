<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Slips - {{ $period->platform->name_en ?: $period->platform->name }} - {{ $period->month->format('M Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .slip-container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2B547E;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2B547E;
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-grid td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-grid .label {
            background-color: #f9f9f9;
            font-weight: bold;
            width: 25%;
            color: #2B547E;
        }
        .info-grid .value {
            width: 25%;
        }
        
        .section-title {
            background-color: #2B547E;
            color: white;
            padding: 5px 10px;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .financial-table th, .financial-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .financial-table th {
            background-color: #f5f5f5;
            color: #333;
        }
        .text-right {
            text-align: right !important;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
        }
        .net-salary-box {
            border: 2px solid #28a745;
            background-color: #d4edda;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
            border-radius: 5px;
        }
        .net-salary-box h2 {
            margin: 0;
            color: #155724;
            font-size: 24px;
        }
        .net-salary-box p {
            margin: 5px 0 0 0;
            color: #155724;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature-area {
            width: 100%;
            margin-top: 50px;
            table-layout: fixed;
        }
        .signature-area td {
            text-align: center;
            border: none;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
        }
    </style>
</head>
<body>

@foreach($entries as $index => $entry)
    @php
        $employee = $entry->employee;
    @endphp

    <div class="slip-container">
        <div class="header">
            <h1>PAYROLL SLIP</h1>
            <p>{{ $period->platform->name_en ?: $period->platform->name }} | {{ $period->month->format('F Y') }}</p>
        </div>

        <div class="section-title">Employee Details</div>
        <table class="info-grid">
            <tr>
                <td class="label">Employee Name</td>
                <td class="value" colspan="3">
                    {{ $employee->name_en ?: $employee->name_ar }}
                    @if($entry->id_numbers)
                        <span style="font-size: 11px; color: #666; margin-left: 10px;">(IDs: {{ $entry->id_numbers }})</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Iqama Number</td>
                <td class="value">{{ $employee->iqama_number }}</td>
                <td class="label">Platform ID</td>
                <td class="value">{{ $entry->platform_id_number }}</td>
            </tr>
            <tr>
                <td class="label">Branch / City</td>
                <td class="value">{{ $employee->branch?->name_en ?: $employee->branch?->name ?: 'N/A' }}</td>
                <td class="label">Contract Type</td>
                <td class="value">{{ ucfirst($employee->contract_type) }}</td>
            </tr>
            @if(!empty($employee->notes))
            <tr>
                <td class="label">Notes</td>
                <td class="value" colspan="3" style="color:red; font-weight:bold;">{{ $employee->notes }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Total Orders</td>
                <td class="value">{{ number_format($entry->total_orders) }}</td>
                <td class="label">Working Days</td>
                <td class="value">{{ $entry->working_days }}</td>
            </tr>
        </table>

        <div class="section-title">Earnings</div>
        <table class="financial-table">
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (SAR)</th>
            </tr>
            <tr>
                <td>Basic Salary</td>
                <td class="text-right">{{ number_format($entry->basic_salary, 2) }}</td>
            </tr>
            <tr>
                <td>Bonus / Commission</td>
                <td class="text-right">{{ number_format($entry->bonus, 2) }}</td>
            </tr>
            <tr>
                <td>App Settlements / Adjustments</td>
                <td class="text-right">{{ number_format($entry->app_settlements, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Gross Salary</td>
                <td class="text-right">{{ number_format($entry->basic_salary + $entry->bonus + $entry->app_settlements, 2) }}</td>
            </tr>
        </table>

        @if($entry->total_deductions > 0)
        <div class="section-title">Deductions</div>
        <table class="financial-table">
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (SAR)</th>
            </tr>
            @if($entry->advances > 0)
            <tr>
                <td>Cash Advance</td>
                <td class="text-right">{{ number_format($entry->advances, 2) }}</td>
            </tr>
            @endif
            @if($entry->traffic_violations > 0)
            <tr>
                <td>Traffic Violations</td>
                <td class="text-right">{{ number_format($entry->traffic_violations, 2) }}</td>
            </tr>
            @endif
            @if($entry->spare_parts > 0)
            <tr>
                <td>Spare Parts (Misuse)</td>
                <td class="text-right">{{ number_format($entry->spare_parts, 2) }}</td>
            </tr>
            @endif
            @if($entry->maintenance > 0)
            <tr>
                <td>Manual Maintenance</td>
                <td class="text-right">{{ number_format($entry->maintenance, 2) }}</td>
            </tr>
            @endif
            @if($entry->company_discount > 0)
            <tr>
                <td>Company Penalties</td>
                <td class="text-right">{{ number_format($entry->company_discount, 2) }}</td>
            </tr>
            @endif
            @if($entry->fuel > 0)
            <tr>
                <td>Fuel Deduction</td>
                <td class="text-right">{{ number_format($entry->fuel, 2) }}</td>
            </tr>
            @endif
            @if($entry->housing > 0)
            <tr>
                <td>Housing Deduction</td>
                <td class="text-right">{{ number_format($entry->housing, 2) }}</td>
            </tr>
            @endif
            @if($entry->packages > 0)
            <tr>
                <td>Internet / Packages Deduction</td>
                <td class="text-right">{{ number_format($entry->packages, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total Deductions</td>
                <td class="text-right">{{ number_format($entry->total_deductions, 2) }}</td>
            </tr>
        </table>
        @endif

        <div class="net-salary-box">
            <p>NET PAYABLE SALARY</p>
            <h2>SAR {{ number_format($entry->net_salary, 2) }}</h2>
        </div>

        <table class="signature-area">
            <tr>
                <td>
                    <div class="signature-line">Employer Signature</div>
                </td>
                <td>
                    <div class="signature-line">
                        Employee Signature<br>
                        @if($entry->id_numbers)
                            <span style="font-size: 10px; color: #777;">(IDs: {{ $entry->id_numbers }})</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated by Walim System on {{ date('Y-m-d H:i') }}
        </div>
    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>

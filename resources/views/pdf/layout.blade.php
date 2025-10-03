<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #666;
        }
        .report-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .report-period {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .bg-gray {
            background-color: #f9f9f9;
        }
        tfoot td {
            background-color: #e9e9e9;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="company-name">{{ $company->company_name ?? 'Entreprise' }}</div>
    <div class="company-info">
        @if($company)
            {{ $company->address }}, {{ $company->city }}<br>
            Tél: {{ $company->phone }} | Email: {{ $company->email }}<br>
            NIF: {{ $company->tax_number }}
        @endif
    </div>
</div>

@yield('content')

<div class="footer">
    Édité le {{ now()->format('d/m/Y à H:i') }} | Page <span class="page-number"></span>
</div>
</body>
</html>

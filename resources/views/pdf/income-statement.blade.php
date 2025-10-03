@extends('pdf.layout')

@section('title', 'Compte de Résultat')

@section('content')
    <div class="report-title">Compte de Résultat</div>
    <div class="report-period">
        Du {{ \Carbon\Carbon::parse($data['period']['start_date'])->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($data['period']['end_date'])->format('d/m/Y') }}
    </div>

    <table style="width: 48%; float: left; margin-right: 4%;">
        <thead>
        <tr>
            <th colspan="2" style="background-color: #ffe6e6;">CHARGES</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data['expenses']['accounts'] as $account)
            <tr>
                <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
                <td class="text-right">{{ number_format(abs($account['balance']), 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td class="font-bold">TOTAL CHARGES</td>
            <td class="text-right font-bold">{{ number_format($data['expenses']['total'], 0, ',', ' ') }}</td>
        </tr>
        </tfoot>
    </table>

    <table style="width: 48%; float: right;">
        <thead>
        <tr>
            <th colspan="2" style="background-color: #e6ffe6;">PRODUITS</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data['revenues']['accounts'] as $account)
            <tr>
                <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
                <td class="text-right">{{ number_format(abs($account['balance']), 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td class="font-bold">TOTAL PRODUITS</td>
            <td class="text-right font-bold">{{ number_format($data['revenues']['total'], 0, ',', ' ') }}</td>
        </tr>
        </tfoot>
    </table>

    <div style="clear: both; margin-top: 30px; padding: 20px; background-color: #f0f0f0; text-align: center;">
        <strong style="font-size: 14pt;">RÉSULTAT NET :
            <span style="color: {{ $data['net_income'] >= 0 ? 'green' : 'red' }};">
                {{ number_format($data['net_income'], 0, ',', ' ') }} FCFA
            </span>
        </strong>
        <br>
        <span style="font-size: 11pt;">
            {{ $data['net_income'] >= 0 ? '(Bénéfice)' : '(Perte)' }}
        </span>
    </div>
@endsection

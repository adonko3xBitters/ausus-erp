@extends('pdf.layout')

@section('title', 'Balance Générale')

@section('content')
    <div class="report-title">Balance Générale</div>
    <div class="report-period">
        Du {{ \Carbon\Carbon::parse($data['period']['start_date'])->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($data['period']['end_date'])->format('d/m/Y') }}
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 10%;">Code</th>
            <th style="width: 40%;">Intitulé du compte</th>
            <th class="text-right" style="width: 16%;">Débit</th>
            <th class="text-right" style="width: 16%;">Crédit</th>
            <th class="text-right" style="width: 18%;">Solde</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data['accounts'] as $account)
            <tr>
                <td class="font-bold">{{ $account['account_code'] }}</td>
                <td>{{ $account['account_name'] }}</td>
                <td class="text-right">{{ number_format($account['debit'], 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($account['credit'], 2, ',', ' ') }}</td>
                <td class="text-right font-bold">{{ number_format($account['balance'], 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2" class="text-right">TOTAUX</td>
            <td class="text-right">{{ number_format($data['totals']['debit'], 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($data['totals']['credit'], 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($data['totals']['difference'], 2, ',', ' ') }}</td>
        </tr>
        </tfoot>
    </table>
@endsection

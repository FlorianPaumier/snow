{{-- Printable payment receipt: standalone HTML shared by the on-screen
document page and the Gotenberg PDF render. Self-contained (inline CSS,
no external assets) so the PDF conversion needs nothing but this file. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('documents.receipt_page_title', ['client' => $client['name'], 'season' => $contract['season']]) }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.55;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            max-width: 780px;
            margin: 16px auto 0;
            padding: 0 24px;
        }

        .toolbar button {
            border: 1px solid #d4d4d8;
            border-radius: 8px;
            background: #fff;
            color: #18181b;
            font: inherit;
            font-weight: 500;
            padding: 7px 14px;
            cursor: pointer;
        }

        .toolbar button:hover {
            background: #fafafa;
        }

        .sheet {
            max-width: 780px;
            margin: 16px auto 40px;
            padding: 48px;
            background: #fff;
            border: 1px solid #e4e4e7;
            box-shadow: 0 1px 2px rgb(0 0 0 / 6%);
        }

        .doc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #18181b;
            padding-bottom: 20px;
        }

        .issuer {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .doc-title {
            margin: 2px 0 0;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #71717a;
        }

        .issued-on {
            margin: 0;
            white-space: nowrap;
            color: #71717a;
        }

        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            padding: 24px 0;
        }

        .meta h2 {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #71717a;
        }

        .client-name {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }

        .client-number {
            margin: 0 0 8px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #71717a;
        }

        address {
            font-style: normal;
            margin: 0 0 8px;
        }

        .meta dl {
            margin: 0;
        }

        .meta dt {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #71717a;
        }

        .meta dd {
            margin: 0 0 10px;
            font-weight: 500;
        }

        section + section {
            margin-top: 24px;
        }

        section > h2 {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #71717a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 8px 0;
            border-bottom: 1px solid #e4e4e7;
            vertical-align: top;
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .summary {
            margin-left: auto;
            width: 280px;
        }

        .summary td {
            padding: 6px 0;
            border-bottom: 1px solid #e4e4e7;
        }

        .summary .balance td {
            border-bottom: none;
            border-top: 2px solid #18181b;
            font-weight: 700;
            font-size: 14px;
        }

        .doc-footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #e4e4e7;
            color: #71717a;
            font-size: 11px;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
@php
    $docLocale = app()->getLocale();
    $moneyLocale = $docLocale === 'fr' ? 'fr-CA' : 'en-CA';
    $formatMoney = fn (int $cents): string => \Illuminate\Support\Number::currency($cents / 100, 'CAD', locale: $moneyLocale);
    $formatDate = fn (?Carbon\CarbonInterface $date): string => $date === null ? '' : $date->locale($docLocale)->translatedFormat('j F Y');
@endphp

@if ($toolbar)
    <div class="toolbar">
        <button type="button" onclick="window.print()">{{ __('documents.print') }}</button>
    </div>
@endif

<article class="sheet">
    <header class="doc-header">
        <div>
            <p class="issuer">{{ $organization['name'] }}</p>
            <p class="doc-title">{{ __('documents.receipt_title') }}</p>
        </div>
        <p class="issued-on">{{ __('documents.issued_on', ['date' => $formatDate($issuedAt)]) }}</p>
    </header>

    <section class="meta">
        <div>
            <h2>{{ __('documents.client') }}</h2>
            <p class="client-number">{{ $client['number'] }}</p>
            <p class="client-name">{{ $client['name'] }}</p>
            <address>
                {{ $client['address']['street'] }}<br>
                {{ $client['address']['city'] }}, {{ $client['address']['postalCode'] }}<br>
                {{ $client['address']['country'] }}
            </address>
            @if ($client['phone'] !== null)
                <p>{{ $client['phone'] }}</p>
            @endif
            @if ($client['email'] !== null)
                <p>{{ $client['email'] }}</p>
            @endif
        </div>
        <div>
            <h2>{{ __('documents.receipt_payment') }}</h2>
            <dl>
                <dt>{{ __('documents.receipt_contract') }}</dt>
                <dd>{{ $contract['season'] }}</dd>
                <dt>{{ __('documents.installment') }}</dt>
                <dd>{{ __('documents.slot_'.$payment['slot']) }}</dd>
                @if ($payment['method'] !== null)
                    <dt>{{ __('documents.receipt_method') }}</dt>
                    <dd>{{ __('documents.method_'.$payment['method']) }}</dd>
                @endif
                <dt>{{ __('documents.receipt_paid_on') }}</dt>
                <dd>{{ $formatDate($payment['paidAt']) }}</dd>
            </dl>
        </div>
    </section>

    <section>
        <table class="summary">
            <tbody>
                <tr>
                    <td>{{ __('documents.installment') }}</td>
                    <td class="num">{{ __('documents.slot_'.$payment['slot']) }}</td>
                </tr>
                @if ($payment['method'] !== null)
                    <tr>
                        <td>{{ __('documents.receipt_method') }}</td>
                        <td class="num">{{ __('documents.method_'.$payment['method']) }}</td>
                    </tr>
                @endif
                <tr class="balance">
                    <td>{{ __('documents.amount_paid') }}</td>
                    <td class="num">{{ $formatMoney($payment['amount']) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <footer class="doc-footer">
        {{ __('documents.generated_on', ['date' => $formatDate($issuedAt), 'organization' => $organization['name']]) }}
    </footer>
</article>
</body>
</html>

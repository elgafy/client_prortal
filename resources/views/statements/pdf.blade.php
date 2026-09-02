<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Statement — {{ $client->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; font-size: 12px; padding: 48px;}
        .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 12px; margin-bottom: 20px; }
        .business { font-size: 20px; font-weight: bold; letter-spacing: -0.5px; }
        .statement-title { font-size: 14px; color: #555; margin-top: 4px; }
        .meta { margin-bottom: 20px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { color: #777; width: 160px; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 24px 0 10px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; font-size: 11px; text-transform: uppercase; color: #777; border-bottom: 1px solid #ccc; padding: 6px 8px; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        table.items td.num, table.items th.num { text-align: right; }
        table.totals { width: 45%; border-collapse: collapse; margin-top: 12px; margin-left: auto; }
        table.totals td { padding: 5px 8px; }
        table.totals td.num { text-align: right; font-weight: bold; }
        table.totals tr.grand td { border-top: 2px solid #1a1a1a; font-weight: bold; }
        .currency-section { margin-bottom: 8px; }
        .currency-label { font-size: 11px; color: #777; margin-bottom: 6px; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business">{{ $businessName }}</div>
        <div class="statement-title">Account Statement</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Client</td>
            <td><strong>{{ $client->name }}</strong></td>
        </tr>
        @if ($client->company_name)
            <tr>
                <td class="label">Company</td>
                <td>{{ $client->company_name }}</td>
            </tr>
        @endif
        @if ($client->email)
            <tr>
                <td class="label">Email</td>
                <td>{{ $client->email }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Statement date</td>
            <td>{{ $generatedAt->toFormattedDateString() }}</td>
        </tr>
        @if ($from || $to)
            <tr>
                <td class="label">Payment filter</td>
                <td>
                    {{ $from ? \Illuminate\Support\Carbon::parse($from)->toFormattedDateString() : 'Beginning' }}
                    —
                    {{ $to ? \Illuminate\Support\Carbon::parse($to)->toFormattedDateString() : 'Today' }}
                    (summary always reflects complete account totals)
                </td>
            </tr>
        @endif
    </table>

    @foreach ($summary['currencies'] as $currency => $line)
        @php
            $currencyProjects = $projects->where('currency', $currency);
            $currencyPayments = $payments->where('currency', $currency);
        @endphp

        <div class="currency-section">
            <div class="currency-label">Currency: {{ $currency }}</div>

            <h2>Projects</h2>
            @if ($currencyProjects->isEmpty())
                <p style="color: #999;">No projects.</p>
            @else
                <table class="items">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Date</th>
                            <th class="num">Subtotal</th>
                            <th class="num">Discount</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($currencyProjects as $project)
                            <tr>
                                <td>{{ $project->name }}</td>
                                <td>{{ $project->project_date ? \Illuminate\Support\Carbon::parse($project->project_date)->toFormattedDateString() : '—' }}</td>
                                <td class="num">{{ number_format((float) $project->subtotal) }}</td>
                                <td class="num">{{ $project->discount_total > 0 ? '-'.number_format((float) $project->discount_total) : '—' }}</td>
                                <td class="num">{{ number_format((float) $project->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <h2>Payments</h2>
            @if ($currencyPayments->isEmpty())
                <p style="color: #999;">No payments.</p>
            @else
                <table class="items">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Project</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($currencyPayments as $payment)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($payment->payment_date)->toFormattedDateString() }}</td>
                                <td>{{ $payment->method }}</td>
                                <td>{{ $payment->project?->name ?? '—' }}</td>
                                <td class="num">{{ number_format((float) $payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <table class="totals">
                <tr>
                    <td>Total Projects</td>
                    <td class="num">{{ number_format($line['projects_total']) }}</td>
                </tr>
                <tr>
                    <td>Total Paid</td>
                    <td class="num">{{ number_format($line['payments_total']) }}</td>
                </tr>
                <tr class="grand">
                    <td>{{ $line['credit'] !== 0 ? 'Credit' : 'Outstanding' }} ({{ $currency }})</td>
                    <td class="num">{{ number_format($line['credit'] !== 0 ? $line['credit'] : $line['outstanding']) }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Statement generated on {{ $generatedAt->toDayDateTimeString() }} by {{ $businessName }}.
        Totals reflect the complete account state at generation time.
    </div>
</body>
</html>

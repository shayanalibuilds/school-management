<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee Receipt — {{ $payment->reference ?? $payment->getKey() }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; background: #f3f4f6; padding: 24px; color: #111827; }
        .receipt { max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #d1d5db; }
        .header { background: #1e3a8a; color: #ffffff; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; letter-spacing: 0.5px; }
        .header .tag { font-size: 12px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.85; }
        .meta { display: flex; justify-content: space-between; padding: 20px 32px; border-bottom: 1px dashed #d1d5db; font-size: 14px; }
        .meta div { line-height: 1.7; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 12px 32px; background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        td { padding: 14px 32px; border-bottom: 1px solid #f3f4f6; }
        .totals td { font-weight: bold; font-size: 16px; background: #f9fafb; }
        .stamp { margin: 20px 32px 8px; padding: 10px 16px; display: inline-block; border: 2px solid #166534; color: #166534; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; transform: rotate(-3deg); }
        .stamp.pending { border-color: #92400e; color: #92400e; }
        .footer { padding: 20px 32px 28px; font-size: 12px; color: #6b7280; line-height: 1.6; }
        .print { max-width: 640px; margin: 16px auto; text-align: center; }
        .print button { font-family: system-ui; padding: 10px 24px; background: #1e3a8a; color: #fff; border: none; cursor: pointer; }
        @media print {
            body { background: #fff; padding: 0; }
            .print { display: none; }
            .receipt { border: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div>
                <h1>{{ config('app.name') }}</h1>
                <div class="tag">Official Fee Receipt</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 12px; opacity: 0.85;">Receipt No.</div>
                <div style="font-size: 15px; font-family: monospace;">{{ $payment->reference ?? 'PENDING' }}</div>
            </div>
        </div>

        <div class="meta">
            <div>
                <div class="label">Student</div>
                <div>{{ $payment->fee->student->name }} (GR #{{ $payment->fee->student->gr_no }})</div>
                <div class="label" style="margin-top: 6px;">Class</div>
                <div>{{ $payment->fee->student->studentClass?->name ?? '—' }}</div>
            </div>
            <div style="text-align: right;">
                <div class="label">Payment date</div>
                <div>{{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}</div>
                <div class="label" style="margin-top: 6px;">Fee period</div>
                <div>{{ $payment->fee->feeStructure->name }} — {{ $payment->fee->year }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Channel</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->fee->feeStructure->name }} ({{ $payment->fee->feeStructure->type->label() }})</td>
                    <td>{{ $payment->provider->label() }}</td>
                    <td style="text-align: right;">PKR {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
                <tr class="totals">
                    <td colspan="2">Total paid</td>
                    <td style="text-align: right;">PKR {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="meta">
            <div>
                <div class="label">Payer</div>
                <div>{{ $payment->payer_name ?? '—' }}</div>
                @if ($payment->payer_cnic)
                    <div style="font-size: 12px; color: #6b7280;">CNIC: {{ $payment->payer_cnic }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <span class="stamp {{ $payment->status->value === 'completed' ? '' : 'pending' }}">
                    {{ $payment->status->label() }}
                </span>
            </div>
        </div>

        <div class="footer">
            This is a system-generated receipt for payment received via {{ $payment->provider->label() }}.
            Transaction reference: <strong>{{ $payment->reference ?? '—' }}</strong>.
            Please retain this receipt for your records. For queries, contact the school office.
        </div>
    </div>

    <div class="print">
        <button onclick="window.print()">Print receipt</button>
    </div>
</body>
</html>

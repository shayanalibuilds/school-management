<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee Receipt — {{ $payment->reference ?? $payment->getKey() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #faf8ff;
            --card: #ffffff;
            --mist: #f2f3ff;
            --tint: #eaedff;
            --haze: #e2e7ff;
            --haze-deep: #dae2fd;
            --ink: #131b2e;
            --ink-soft: #45474d;
            --line: #c6c6cd;
            --navy: #121b2f;
            --steel: #7a849c;
            --green: #006c48;
            --mint: #95f3c2;
            --mint-ink: #00714c;
            --clay: #ffdbca;
            --clay-ink: #763300;
            --alert: #ba1a1a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            padding: 24px 16px 48px;
            -webkit-font-smoothing: antialiased;
        }
        .tabular { font-variant-numeric: tabular-nums; }
        .mono { font-family: ui-monospace, 'Cascadia Mono', 'Courier New', monospace; }

        /* Non-printable verification banner + actions */
        .action-bar {
            max-width: 800px;
            margin: 0 auto 20px;
            background: var(--mist);
            border: 1px solid color-mix(in srgb, var(--green) 20%, transparent);
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .action-bar .badge-icon {
            width: 38px; height: 38px; border-radius: 999px;
            background: var(--mint); color: var(--mint-ink);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .action-bar h2 { font-size: 15px; font-weight: 600; color: var(--ink); }
        .action-bar p { font-size: 12px; color: var(--ink-soft); margin-top: 2px; }
        .action-bar p strong { color: var(--green); font-weight: 700; }
        .verified-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--mint); color: var(--mint-ink);
            border-radius: 999px; padding: 5px 11px;
            font-size: 11px; font-weight: 700; letter-spacing: .04em;
            white-space: nowrap;
        }
        .verified-pill .dot { width: 6px; height: 6px; border-radius: 999px; background: var(--green); }

        /* A4 paper canvas */
        .receipt-canvas {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid color-mix(in srgb, var(--line) 40%, transparent);
            border-radius: 8px;
            padding: 36px 40px 32px;
            box-shadow: 0 1px 2px rgb(19 27 46 / 6%), 0 8px 24px rgb(19 27 46 / 6%);
        }
        .tri-strip {
            height: 6px;
            border-radius: 999px;
            overflow: hidden;
            display: flex;
            margin-bottom: 28px;
        }
        .tri-strip span { flex: 1; }
        .tri-strip .s1 { background: var(--green); }
        .tri-strip .s2 { background: var(--clay); }
        .tri-strip .s3 { background: var(--navy); }

        /* Letterhead */
        .letterhead {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 20px;
        }
        .crest-row { display: flex; gap: 14px; align-items: flex-start; }
        .crest {
            width: 56px; height: 56px; border-radius: 8px;
            background: var(--navy); color: #98f5c5;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .letterhead h1 {
            font-family: 'Newsreader', Georgia, 'Times New Roman', serif;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: var(--ink);
        }
        .letterhead .sub {
            margin-top: 3px;
            font-size: 12.5px;
            line-height: 1.55;
            color: var(--ink-soft);
        }
        .letterhead .division {
            display: inline-block;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--green);
        }
        .receipt-box {
            background: var(--mist);
            border: 1px solid color-mix(in srgb, var(--line) 30%, transparent);
            border-radius: 8px;
            padding: 14px 16px;
            min-width: 230px;
        }
        .receipt-box .label {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em;
            color: var(--ink-soft);
        }
        .receipt-box .number { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 999px; padding: 4px 11px;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            margin-top: 6px;
        }
        .status-pill.paid { background: var(--mint); color: var(--mint-ink); }
        .status-pill.pending { background: var(--clay); color: var(--clay-ink); }

        .rule {
            position: relative;
            height: 1px;
            background: var(--haze);
            margin: 4px 0 22px;
        }
        .rule span {
            position: absolute; right: 0; top: -8px;
            background: var(--card);
            padding-left: 10px;
            font-size: 11px; color: var(--steel);
            letter-spacing: .06em;
        }

        /* Party metadata grid */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 32px;
            background: color-mix(in srgb, var(--mist) 55%, transparent);
            border: 1px solid color-mix(in srgb, var(--line) 20%, transparent);
            border-radius: 8px;
            padding: 20px 22px;
            margin-bottom: 26px;
        }
        @media (min-width: 640px) {
            .meta-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 560px) {
            .meta-grid { grid-template-columns: 1fr; }
        }
        .meta-grid .label {
            display: block;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em;
            color: var(--ink-soft);
            margin-bottom: 3px;
        }
        .meta-grid .value { font-size: 15px; font-weight: 600; color: var(--ink); }
        .meta-grid .value.small { font-size: 13.5px; font-weight: 500; }
        .gr-chip {
            display: inline-block;
            font-family: ui-monospace, 'Cascadia Mono', 'Courier New', monospace;
            font-weight: 700; font-size: 13px;
            background: var(--haze-deep);
            border-radius: 4px;
            padding: 3px 9px;
            margin-top: 3px;
        }

        /* Items table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            margin-bottom: 26px;
        }
        table.items thead tr {
            background: var(--navy);
            color: var(--card);
        }
        table.items th {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .1em;
            padding: 10px 14px;
            text-align: left;
        }
        table.items th:last-child { text-align: right; }
        table.items td {
            padding: 14px;
            border-bottom: 1px solid color-mix(in srgb, var(--haze) 60%, transparent);
            vertical-align: top;
        }
        table.items td .desc-sub {
            display: block;
            font-size: 11.5px;
            color: var(--ink-soft);
            margin-top: 2px;
        }
        table.items td.amount {
            text-align: right;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        table.items tr.totals td {
            background: var(--mist);
            border-bottom: none;
            font-weight: 700;
            font-size: 15px;
            padding: 14px;
        }
        table.items tr.totals td:last-child { text-align: right; }

        /* Footer note + stamp */
        .receipt-foot {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }
        .foot-note {
            font-size: 12px;
            line-height: 1.7;
            color: var(--ink-soft);
            max-width: 460px;
        }
        .stamp {
            display: inline-block;
            border: 2.5px solid var(--green);
            color: var(--green);
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .18em;
            padding: 10px 18px;
            transform: rotate(-3deg);
        }
        .stamp.pending { border-color: var(--clay-ink); color: var(--clay-ink); }

        .print-row { max-width: 800px; margin: 18px auto 0; text-align: center; }
        .print-row button {
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 26px;
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 150ms ease;
        }
        .print-row button:hover { background: #283044; }

        @media print {
            @page { size: A4 portrait; margin: 1.2cm; }
            body { background: #ffffff; padding: 0; }
            .action-bar, .print-row { display: none; }
            .receipt-canvas {
                border: none;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    {{-- Non-printable verification banner --}}
    <div class="action-bar">
        <div style="display:flex; align-items:center; gap:12px;">
            <div class="badge-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
            </div>
            <div>
                <h2>Official Electronic Settlement Voucher</h2>
                <p>
                    System-verifiable record ·
                    Student GR: <strong class="mono tabular">{{ \App\Support\GrNumber::bare($payment->fee->student->gr_no) }}</strong>
                </p>
            </div>
        </div>
        <span class="verified-pill"><span class="dot"></span>Verified document</span>
    </div>

    {{-- A4 paper --}}
    <div class="receipt-canvas">
        <div class="tri-strip" aria-hidden="true"><span class="s1"></span><span class="s2"></span><span class="s3"></span></div>

        <div class="letterhead">
            <div class="crest-row">
                <div class="crest" aria-hidden="true">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                </div>
                <div>
                    <h1>{{ config('app.name') }}</h1>
                    <p class="sub">Bursar &amp; Accounts Office · System-generated official record</p>
                    <span class="division">Public Verification Portal</span>
                </div>
            </div>
            <div class="receipt-box">
                <div class="label">Receipt No.</div>
                <div class="number mono tabular">{{ $payment->reference ?? 'PENDING' }}</div>
                <div class="label">Payment date</div>
                <div class="small tabular" style="font-size:13px; font-weight:600; margin-bottom:2px;">
                    {{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
                @if ($payment->status->value === 'completed')
                    <span class="status-pill paid">Paid in full</span>
                @else
                    <span class="status-pill pending">{{ $payment->status->label() }}</span>
                @endif
            </div>
        </div>

        <div class="rule"><span class="mono tabular">OFFICIAL RECORD · {{ $payment->provider->label() }}</span></div>

        <div class="meta-grid">
            <div>
                <span class="label">Student</span>
                <span class="value">{{ $payment->fee->student->name }}</span>
                <span class="gr-chip tabular">GR {{ \App\Support\GrNumber::bare($payment->fee->student->gr_no) }}</span>
            </div>
            <div>
                <span class="label">Received from</span>
                <span class="value">{{ $payment->payer_name ?? $payment->fee->student->name }}</span>
                @if ($payment->payer_cnic)
                    <span class="value small" style="display:block; color:var(--ink-soft); font-size:12px; margin-top:2px;">
                        CNIC: {{ $payment->payer_cnic }}
                    </span>
                @endif
            </div>
            <div>
                <span class="label">Class / Division</span>
                <span class="value small">{{ $payment->fee->student->studentClass?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="label">Payment method</span>
                <span class="value small">{{ $payment->provider->label() }}</span>
            </div>
            <div>
                <span class="label">Fee period</span>
                <span class="value small">{{ $payment->fee->feeStructure->name }} — <span class="tabular">{{ $payment->fee->year }}</span></span>
            </div>
            <div>
                <span class="label">Transaction reference</span>
                <span class="value small mono tabular">{{ $payment->reference ?? '—' }}</span>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th scope="col">Description</th>
                    <th scope="col">Academic cycle</th>
                    <th scope="col">Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {{ $payment->fee->feeStructure->name }} ({{ $payment->fee->feeStructure->type->label() }})
                        <span class="desc-sub">Settled via {{ $payment->provider->label() }}</span>
                    </td>
                    <td class="tabular" style="color:var(--ink-soft);">{{ $payment->fee->year }}</td>
                    <td class="amount tabular">{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
                <tr class="totals">
                    <td colspan="2">Total paid</td>
                    <td class="tabular">PKR {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-foot">
            <p class="foot-note">
                This is a system-generated receipt for payment received via {{ $payment->provider->label() }}.
                Transaction reference: <strong class="mono tabular">{{ $payment->reference ?? '—' }}</strong>.
                Please retain this receipt for your records. For queries and corrections, contact
                the school office with the receipt number ready.
            </p>
            <span class="stamp {{ $payment->status->value === 'completed' ? '' : 'pending' }}">
                {{ $payment->status->label() }}
            </span>
        </div>
    </div>

    <div class="print-row">
        <button type="button" onclick="window.print()">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
            Print receipt
        </button>
    </div>
</body>
</html>

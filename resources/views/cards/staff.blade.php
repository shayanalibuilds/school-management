<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Card — {{ $staff->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f3f4f6; padding: 32px; display: flex; flex-direction: column; align-items: center; gap: 20px; }
        .card { width: 340px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 24px rgba(0,0,0,.14); background: #fff; }
        .band { background: linear-gradient(135deg, #0a2f26, #0f7861); color: #fff; padding: 16px 20px; }
        .band .school { font-size: 14px; font-weight: 700; letter-spacing: .3px; }
        .band .tag { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; opacity: .85; margin-top: 2px; }
        .body { padding: 18px 20px 20px; display: flex; gap: 14px; }
        .photo { width: 84px; height: 100px; border-radius: 10px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; }
        .fields { flex: 1; font-size: 13px; line-height: 1.9; }
        .label { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .footer { padding: 0 20px 16px; font-size: 10px; color: #9ca3af; }
        .print button { font-family: system-ui; padding: 10px 24px; background: #064e3b; color: #fff; border: none; cursor: pointer; border-radius: 8px; }
        @media print { body { background: #fff; padding: 0; } .print { display: none; } .card { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="band">
            <div class="school">{{ config('app.name') }}</div>
            <div class="tag">Staff Card</div>
        </div>
        <div class="body">
            <div class="photo">PHOTO</div>
            <div class="fields">
                <div><span class="label">Name</span><br>{{ $staff->name }}</div>
                <div><span class="label">CNIC</span><br>{{ $staff->cnic }}</div>
                <div><span class="label">Joined</span><br>{{ $staff->joining_date?->format('d M Y') }}</div>
                <div><span class="label">Status</span><br>{{ $staff->status->label() }}</div>
            </div>
        </div>
        <div class="footer">This card is the property of the school. If found, please return to the school office.</div>
    </div>
    <div class="print"><button onclick="window.print()">Print card</button></div>
</body>
</html>

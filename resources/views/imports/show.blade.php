<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import / Export</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f3f4f6; padding: 32px; color: #111827; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        p.sub { color: #6b7280; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 960px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
        .card h2 { font-size: 16px; margin-bottom: 4px; }
        .card p { font-size: 13px; color: #6b7280; margin-bottom: 14px; }
        label { display: block; font-size: 13px; margin-bottom: 8px; }
        input[type=file] { display: block; margin-bottom: 12px; font-size: 13px; }
        button { padding: 8px 18px; background: #1e3a8a; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; }
        .links { max-width: 960px; margin-top: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
        .links a { display: block; padding: 6px 0; color: #1e3a8a; font-size: 14px; }
        .status { max-width: 960px; margin-bottom: 16px; padding: 12px 16px; background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Import / Export</h1>
    <p class="sub">Bulk import students and staff from CSV, or download current data.</p>

    @if (session('import_status'))
        <div class="status">{{ session('import_status') }}</div>
    @endif

    <div class="grid">
        <div class="card">
            <h2>Import students</h2>
            <p>Columns: sr_no, name, class, joining_date, status</p>
            <form method="POST" action="{{ route('imports.students') }}" enctype="multipart/form-data">
                @csrf
                <label>CSV file</label>
                <input type="file" name="csv" accept=".csv" required>
                <button type="submit">Import students</button>
            </form>
        </div>

        <div class="card">
            <h2>Import staff</h2>
            <p>Columns: name, cnic, email, phone, joining_date, status</p>
            <form method="POST" action="{{ route('imports.staff') }}" enctype="multipart/form-data">
                @csrf
                <label>CSV file</label>
                <input type="file" name="csv" accept=".csv" required>
                <button type="submit" style="background:#064e3b;">Import staff</button>
            </form>
        </div>
    </div>

    <div class="links">
        <h2 style="font-size:16px; margin-bottom:8px;">Exports</h2>
        <a href="{{ route('exports', ['type' => 'students']) }}">Download students CSV</a>
        <a href="{{ route('exports', ['type' => 'staff']) }}">Download staff CSV</a>
        <a href="{{ route('exports', ['type' => 'attendance']) }}">Download attendance CSV</a>
        <a href="{{ route('exports', ['type' => 'exam-results']) }}">Download exam results CSV</a>
    </div>
</body>
</html>

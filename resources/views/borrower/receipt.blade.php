<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borrow Slip #{{ $transaction->id }} - CICT Equipment Borrower System</title>

    {{-- Self-contained on purpose: this page must print cleanly without pulling in
         the app shell's navbar/sidebar styles. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" type="image/x-icon">

    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --line: #e2e8f0;
            --accent: #2563eb;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px 16px;
            background: #f8fafc;
            color: var(--ink);
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        .slip {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 32px;
        }
        .head { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid var(--line); padding-bottom: 20px; }
        .head img { width: 56px; height: 56px; object-fit: contain; }
        .org { font-size: 20px; font-weight: 700; margin: 0; }
        .sub { font-size: 14px; color: var(--muted); margin: 2px 0 0; }
        .title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-top: 24px; flex-wrap: wrap; }
        h1 { font-size: 22px; margin: 0; }
        .ref { font-size: 14px; color: var(--muted); }
        dl { display: grid; grid-template-columns: 200px 1fr; gap: 0; margin: 24px 0 0; }
        dt, dd { padding: 12px 0; border-bottom: 1px solid var(--line); margin: 0; }
        dt { font-size: 14px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        dd { font-weight: 500; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 14px; font-weight: 600; border: 1px solid; }
        .badge-borrowed { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .badge-returned { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .badge-overdue  { background: #fee2e2; color: var(--danger); border-color: #fecaca; }
        .sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 48px; }
        .sig-line { border-top: 1px solid var(--ink); padding-top: 8px; font-size: 14px; color: var(--muted); }
        .foot { margin-top: 32px; font-size: 13px; color: var(--muted); border-top: 1px solid var(--line); padding-top: 16px; }
        .actions { max-width: 720px; margin: 0 auto 16px; display: flex; gap: 8px; justify-content: flex-end; }
        .btn {
            font: inherit; font-size: 15px; font-weight: 600; cursor: pointer;
            padding: 10px 18px; min-height: 44px; border-radius: 8px;
            border: 1px solid var(--line); background: #fff; color: var(--ink); text-decoration: none;
            display: inline-flex; align-items: center;
        }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }

        @media print {
            body { background: #fff; padding: 0; }
            .slip { border: 0; border-radius: 0; padding: 0; max-width: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print this slip</button>
        <a class="btn" href="{{ route('borrower.dashboard') }}">Back to dashboard</a>
    </div>

    <div class="slip">
        <div class="head">
            <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
            <div>
                <p class="org">CICT Equipment Borrower System</p>
                <p class="sub">College of Information &amp; Communications Technology</p>
            </div>
        </div>

        <div class="title-row">
            <h1>Borrow Slip</h1>
            <span class="ref">Reference #{{ $transaction->id }}</span>
        </div>

        <dl>
            <dt>Borrower</dt>
            <dd>{{ $transaction->user->name ?? '—' }}</dd>

            <dt>Equipment</dt>
            <dd>{{ $transaction->equipment->equipment_name ?? '—' }}</dd>

            <dt>Quantity</dt>
            <dd>{{ $transaction->quantity }}</dd>

            <dt>Borrow date</dt>
            <dd>{{ $transaction->borrow_date ? \Carbon\Carbon::parse($transaction->borrow_date)->format('F j, Y') : '—' }}</dd>

            <dt>Return date</dt>
            <dd>{{ $transaction->return_date ? \Carbon\Carbon::parse($transaction->return_date)->format('F j, Y') : '—' }}</dd>

            <dt>Purpose</dt>
            <dd>{{ $transaction->purpose }}</dd>

            <dt>Status</dt>
            <dd>
                @php
                    $badgeClass = ['Borrowed' => 'badge-borrowed', 'Returned' => 'badge-returned', 'Overdue' => 'badge-overdue'][$transaction->status] ?? '';
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $transaction->status }}</span>
            </dd>

            @if($transaction->remarks)
                <dt>Remarks</dt>
                <dd>{{ $transaction->remarks }}</dd>
            @endif
        </dl>

        <div class="sigs">
            <div class="sig-line">Borrower's signature</div>
            <div class="sig-line">Received / released by</div>
        </div>

        <p class="foot">
            Issued {{ now()->format('F j, Y \a\t g:i A') }}. Please present this slip when returning the equipment.
        </p>
    </div>
</body>
</html>

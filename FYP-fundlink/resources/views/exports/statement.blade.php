<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fundlink Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 32px;
            color: #1A2350;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 2px solid #3A478C;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo {
            width: 50px;
        }

        .app-name {
            font-size: 22px;
            font-weight: bold;
            color: #3A478C;
        }

        .statement-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 18px;
        }

        .subtext {
            font-size: 13px;
            margin-top: 3px;
            color: #4b4b4b;
        }

        .summary-boxes {
            display: flex;
            gap: 18px;
            margin-top: 22px;
        }

        .summary-card {
            background: #EEF1FF;
            padding: 12px 16px;
            border-radius: 10px;
            flex: 1;
        }

        .summary-label {
            font-size: 12px;
            color: #555;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #3A478C;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
            font-size: 13px;
        }

        th {
            background: #EEF1FF;
            color: #1A2350;
            padding: 10px;
            border-bottom: 2px solid #3A478C;
            text-align: left;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        .right {
            text-align: right;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 45px;
            color: #777;
        }

        .pagenum:before {
            content: "Page " counter(page);
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="logo-box">
            <img src="{{ public_path('logo.png') }}" class="logo" alt="Fundlink Logo">
            <span class="app-name">Fundlink</span>
        </div>
    </div>

    <!-- STATEMENT TITLE -->
    <div class="statement-title">
        Statement of Accounts - {{ $statementMonth }}
    </div>

    <div class="subtext">
        User: {{ $user->name }} &nbsp;|&nbsp; Account Scope: {{ $accountScope ?? 'All Accounts' }}
    </div>

    <!-- SUMMARY -->
    <div class="summary-boxes">
        <div class="summary-card">
            <div class="summary-label">Total Transactions</div>
            <div class="summary-value">{{ $transactions->count() }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Total Amount Spent</div>
            <div class="summary-value">RM{{ number_format($transactions->sum('amount'), 2) }}</div>
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Account</th>
                <th class="right">Amount (RM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                    <td>{{ $t->description }}</td>
                    <td>{{ $t->category->name ?? 'Uncategorized' }}</td>
                    <td>
                        @if($t->sharedAccount)
                            Shared: {{ $t->sharedAccount->name }}
                        @else
                            Personal
                        @endif
                    </td>
                    <td class="right">RM{{ number_format($t->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Fundlink - Smart Financial Tracking for Students & Young Adults
        <br> (c) {{ date('Y') }} Fundlink. All Rights Reserved.
    </div>
    <span class="pagenum"></span>

</body>
</html>

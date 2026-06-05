<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $sharedAccount->name }} - Fundlink</title>

    <link rel="stylesheet" href="{{ asset('profile.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #3A478C;
            --card: #ffffff;
            --border: #e5e7eb;
            --text: #1f2937;
            --muted: #6b7280;
            --bg: #f7f8fb;
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .page-shell {
            max-width: 1200px;
            margin: 24px auto 48px;
            padding: 0 16px;
        }

        .header-container {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
        }
        .menu-toggle {
            display: none;
            border: none;
            background: transparent;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--primary);
        }
        .menu-toggle i { font-size: 20px; }

        .main-nav {
            display: flex;
            gap: 14px;
            flex: 1;
            justify-content: center;
        }
        .main-nav .nav-item { padding: 10px 12px; }

        .header-actions {
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .summary-wrapper {
            padding: 24px;
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 6px 30px rgba(0,0,0,0.06);
        }

        .summary-header h2 {
            margin: 0;
            color: var(--text);
        }
        .summary-desc {
            margin: 4px 0 0;
            color: var(--muted);
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 18px 0 10px;
        }
        .stat-card {
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }
        .stat-card label {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .stat-value {
            font-weight: 700;
            color: var(--text);
            font-size: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 18px;
            margin-top: 18px;
        }

        .chart-card, .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .table-members, .table-transactions { width:100%; border-collapse: collapse; font-size:13px; }
        .table-members th, .table-members td,
        .table-transactions th, .table-transactions td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-members th, .table-transactions th { background:#f9fafb; color:#4b5563; font-weight:600; }
        .amount-col { text-align:right; font-weight:600; }

        /* Avatar circle */
        .avatar-circle {
            width: 34px; height: 34px;
            border-radius: 50%;
            display:flex; justify-content:center; align-items:center;
            font-weight:600; color:#fff;
            margin-right:10px;
            font-size: 13px;
        }

        /* Tabs */
        .member-tabs {
            display: flex;
            gap: 8px;
            margin-top: 18px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 8px 12px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .tab-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* Charts */
        .charts-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
            margin-top:18px;
        }
        @media(max-width: 900px){
            .grid-2 { grid-template-columns: 1fr; }
            .charts-grid{ grid-template-columns:1fr; }
            .summary-wrapper { padding: 18px; }
            .menu-toggle { display: inline-flex; }
            .main-nav {
                display: none;
                flex-direction: column;
                width: 100%;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 10px;
                overflow: hidden;
                margin-top: 10px;
            }
            .main-nav.open { display: flex; }
            .main-nav .nav-item {
                padding: 12px 14px;
                border-bottom: 1px solid var(--border);
            }
            .main-nav .nav-item:last-child { border-bottom: none; }
        }
        @media(max-width: 640px){
            body { background: #fff; }
            .page-shell { padding: 0 12px; margin-top: 12px; }
            .summary-wrapper { padding: 16px; border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,0.05); }
            .summary-header h2 { font-size: 20px; }
            .summary-desc { font-size: 13px; }
            .stat-card { padding: 12px; }
            .summary-stats { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .chart-card, .table-card { padding: 14px; }
            .member-tabs { gap: 6px; }
            .tab-btn { padding: 8px 10px; font-size: 12px; }
            .table-transactions, .table-transactions thead { display: none; }
            .table-transactions tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding: 10px 0;
                border-bottom: 1px solid var(--border);
            }
            .table-transactions tbody tr td {
                padding: 0;
                font-size: 13px;
            }
            .table-transactions .amount-col { text-align: left; font-weight: 700; }
            .main-header .header-container { flex-wrap: wrap; gap: 10px; padding: 10px 12px; }
            .main-nav .nav-item { padding: 12px 14px; font-size: 14px; }
        }
        .back-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            background: var(--card);
            border-radius: 10px;
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .back-btn:hover { border-color: var(--primary); }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-outline {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-danger {
            background: #fff5f5;
            color: #b91c1c;
            border: 1px solid #f3c1c1;
        }
        .member-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .member-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 10px;
        }
        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
        }
        .member-item-name {
            font-weight: 600;
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        .modal.open { display: flex; }
        .modal-content {
            width: 92%;
            max-width: 420px;
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 14px 32px rgba(0,0,0,0.14);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text);
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }
        .form-group input {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
        }
    </style>

</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-container">
        <div class="logo" style="display:flex;align-items:center;gap:12px;">
            <img src="{{ asset('logo.png') }}" class="header-logo"> Fundlink
        </div>
        <nav class="main-nav" id="mainNav">
            <a href="{{ route('dashboard') }}" class="nav-item">Dashboard</a>
            <a href="{{ route('transaction') }}" class="nav-item">Transaction</a>
            <a href="{{ route('categories') }}" class="nav-item">Categories</a>
            <a href="{{ route('sharedaccounts.index') }}" class="nav-item active">Account</a>
            <a href="{{ route('profile') }}" class="nav-item">Profile</a>
        </nav>
        <div class="header-actions">
            <button class="menu-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<div class="page-shell">
    <div class="summary-wrapper card">
        <div class="back-row">
            <a href="{{ url()->previous() }}" class="back-btn" onclick="if (history.length > 1) { history.back(); return false; }">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        <div class="summary-header">
            <h2>{{ $sharedAccount->name }}</h2>
            <p class="summary-desc">Overview of member contributions & activity.</p>
            @if($isCreator)
                <div class="member-actions">
                    <button type="button" class="btn-primary" id="openAddMemberModal">
                        <i class="fas fa-user-plus"></i> Add Member
                    </button>
                </div>
            @else
                <div class="member-actions">
                    <button type="button" class="btn-outline btn-danger" id="leaveAccountBtn">
                        <i class="fas fa-sign-out-alt"></i> Leave Account
                    </button>
                </div>
            @endif
        </div>

        <div class="summary-stats">
            <div class="stat-card">
                <label>Total Spend</label>
                <div class="stat-value">RM {{ number_format($totalSpend, 2) }}</div>
            </div>
            <div class="stat-card">
                <label>Your Spend</label>
                <div class="stat-value">RM {{ number_format($yourSpend, 2) }}</div>
            </div>
            <div class="stat-card">
                <label>Total Members</label>
                <div class="stat-value">{{ $membersCount }}</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-card">
                <h4 style="margin:0 0 10px;">Member Contributions</h4>
                @if($isCreator)
                    <div class="member-list">
                        @foreach($sharedAccount->members as $member)
                            <div class="member-item">
                                <div class="member-item-name">{{ $member->name }}</div>
                                @if($member->id !== $sharedAccount->creator_user_id)
                                    <button type="button"
                                            class="btn-outline btn-danger remove-member-btn"
                                            data-user-id="{{ $member->id }}">
                                        Remove
                                    </button>
                                @else
                                    <span style="font-size:12px;color:var(--muted);">Creator</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if($memberStats->isEmpty())
                    <p class="no-data">No transactions yet.</p>
                @else
                <table class="table-members">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Total Spend</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($memberStats as $index => $m)
                        @php
                            $color = '#'.substr(md5($m['user']->id), 0, 6);
                            $initial = strtoupper(substr($m['user']->name, 0, 1));
                            $rank = $index + 1;
                        @endphp
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar-circle" style="background: {{ $color }}">{{ $initial }}</div>
                                    <div>
                                        <div style="font-weight:600;">{{ $m['user']->name }}</div>
                                        <div style="font-size:12px;color:var(--muted);">
                                            @if($rank == 1)
                                                Top contributor
                                            @elseif($rank == 2)
                                                Runner up
                                            @elseif($rank == 3)
                                                Third place
                                            @else
                                                Member
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>RM {{ number_format($m['total'], 2) }}</td>
                            <td>{{ $m['percentage'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            <div class="chart-card">
                <h4 style="margin:0 0 10px;">Contribution Breakdown</h4>
                <canvas id="memberPieChart"></canvas>
            </div>
        </div>

        <div style="margin-top:24px;">
            <div class="member-tabs">
                <div class="tab-btn active" data-member="all">All</div>
                @foreach($memberStats as $m)
                <div class="tab-btn" data-member="{{ $m['user']->id }}">
                    {{ $m['user']->name }}
                </div>
                @endforeach
            </div>

            <div class="table-card">
                <h4 style="margin:0 0 10px;">Transactions</h4>
                @if($transactions->isEmpty())
                    <p class="no-data">No transactions recorded.</p>
                @else
                <table class="table-transactions" id="transactionTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th>Category</th>
                            <th>Note</th>
                            <th class="amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                        <tr data-user="{{ $tx->user->id }}">
                            <td>{{ \Carbon\Carbon::parse($tx->date)->format('d M Y') }}</td>
                            <td>{{ $tx->user->name }}</td>
                            <td>{{ $tx->category->name ?? 'Uncategorized' }}</td>
                            <td>{{ $tx->description ?? '-' }}</td>
                            <td class="amount-col">RM {{ number_format($tx->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@if($isCreator)
<div id="addMemberModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;">Add Member</h3>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form id="addMemberForm">
            <div class="form-group">
                <label for="invite-contact">Member Email</label>
                <input type="email" id="invite-contact" name="contact" placeholder="email@example.com" required>
            </div>
            <div class="member-actions">
                <button type="button" class="btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn-primary">Send Invite</button>
            </div>
            <div id="invite-status" style="margin-top:8px;font-size:13px;color:var(--muted);"></div>
        </form>
    </div>
</div>
@endif

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    /* ---------------- PIE CHART ---------------- */
    const memberLabels = {!! json_encode($memberStats->pluck('user.name')) !!};
    const memberTotals = {!! json_encode($memberStats->pluck('total')) !!};

    const pieCanvas = document.getElementById('memberPieChart');
    if (pieCanvas) {
        new Chart(pieCanvas, {
            type: 'doughnut',
            data: {
                labels: memberLabels,
                datasets: [{
                    data: memberTotals,
                    backgroundColor: ["#3A478C","#6C5CE7","#00B894","#FDCD6E","#E17055","#0984E3"]
                }]
            },
            options: {
                plugins: { legend: { position: "bottom" } }
            }
        });
    }

    /* ---------------- TABS FILTER ---------------- */
    const tabs = document.querySelectorAll('.tab-btn');
    const rows = document.querySelectorAll('#transactionTable tbody tr');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const memberId = tab.dataset.member;

            rows.forEach(row => {
                if (memberId === 'all' || row.dataset.user === memberId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Mobile nav toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.getElementById('mainNav');
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('open');
        });
        mainNav.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', () => mainNav.classList.remove('open'));
        });
        document.addEventListener('click', (e) => {
            if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                mainNav.classList.remove('open');
            }
        });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const openAddMemberBtn = document.getElementById('openAddMemberModal');
    const addMemberModal = document.getElementById('addMemberModal');
    const addMemberForm = document.getElementById('addMemberForm');
    const inviteStatus = document.getElementById('invite-status');

    if (openAddMemberBtn && addMemberModal) {
        openAddMemberBtn.addEventListener('click', () => {
            addMemberModal.classList.add('open');
            if (inviteStatus) inviteStatus.textContent = '';
        });
    }

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (addMemberModal) addMemberModal.classList.remove('open');
        });
    });

    if (addMemberForm) {
        addMemberForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const contact = document.getElementById('invite-contact')?.value.trim();
            if (!contact) return;

            try {
                const res = await fetch(`/sharedaccount/{{ $sharedAccount->id }}/invite/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ contact, type: 'email' })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    if (inviteStatus) inviteStatus.textContent = 'Invite sent.';
                    addMemberForm.reset();
                } else {
                    if (inviteStatus) inviteStatus.textContent = data.message || 'Unable to send invite.';
                }
            } catch (err) {
                if (inviteStatus) inviteStatus.textContent = 'Server error sending invite.';
            }
        });
    }

    document.querySelectorAll('.remove-member-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.userId;
            if (!userId) return;
            if (!confirm('Remove this member from the shared account?')) return;

            try {
                const res = await fetch(`/sharedaccount/{{ $sharedAccount->id }}/members/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    const row = btn.closest('.member-item');
                    if (row) row.remove();
                } else {
                    alert(data.message || 'Unable to remove member.');
                }
            } catch (err) {
                alert('Server error while removing member.');
            }
        });
    });

    const leaveBtn = document.getElementById('leaveAccountBtn');
    if (leaveBtn) {
        leaveBtn.addEventListener('click', async () => {
            if (!confirm('Leave this shared account? You will lose access to it.')) return;
            const keepTransactions = confirm(
                'Keep your transactions from this shared account? Click OK to keep, or Cancel to delete them.'
            );
            try {
                let res = await fetch(`/sharedaccount/{{ $sharedAccount->id }}/leave`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ keep_transactions: keepTransactions ? 1 : 0 })
                });

                if (res.status === 405 || res.status === 404) {
                    res = await fetch(`/sharedaccount/{{ $sharedAccount->id }}/leave`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'DELETE',
                            keep_transactions: keepTransactions ? 1 : 0
                        })
                    });
                }

                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    window.location.href = '{{ route('sharedaccounts.index') }}';
                } else {
                    alert(data.message || 'Unable to leave account.');
                }
            } catch (err) {
                alert('Server error while leaving account.');
            }
        });
    }
</script>
<script src="{{ asset('auth-guard.js') }}"></script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile - Fundlink</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Profile CSS -->
    <link rel="stylesheet" href="{{ asset('profile.css') }}">
</head>
<body>

    {{-- ================= HEADER ================= --}}
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Fundlink Logo" class="header-logo">
                Fundlink
            </div>

            <nav class="main-nav" id="mainNav">
                <a href="{{ route('dashboard') }}" class="nav-item">Dashboard</a>
                <a href="{{ route('transaction') }}" class="nav-item">Transaction</a>
                <a href="{{ route('categories') }}" class="nav-item">Categories</a>
                <a href="{{ route('sharedaccounts.index') }}" class="nav-item">Account</a>
                <a href="{{ route('profile') }}" class="nav-item active">Profile</a>
            </nav>

            <div class="header-actions">
                <button class="menu-toggle" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    {{-- ================= MAIN LAYOUT ================= --}}
    <div class="profile-page">

        <div class="profile-shell">

            {{-- ========== SIDEBAR (desktop) ========== --}}
            <aside class="profile-sidebar desktop-only">
                <h2 class="sidebar-title">Settings</h2>

                <button class="sidebar-link active" data-section="section-profile-info">
                    <i class="fa-solid fa-user"></i> <span>Profile Info</span>
                </button>

                <button class="sidebar-link" data-section="section-summary">
                    <i class="fa-solid fa-chart-line"></i> <span>Summary</span>
                </button>

                <button class="sidebar-link" data-section="section-shared">
                    <i class="fa-solid fa-users"></i> <span>Shared Accounts</span>
                </button>

                <button class="sidebar-link" data-section="section-statements">
                    <i class="fa-solid fa-file-invoice-dollar"></i> <span>Statements</span>
                </button>

                <button class="sidebar-link" data-section="section-notifications">
                    <i class="fa-solid fa-bell"></i> <span>Notifications</span>
                </button>

            </aside>

            {{-- ========== MOBILE DROPDOWN ========== --}}
            <div class="mobile-dropdown mobile-only">
                <button id="mobileDropdownBtn" class="dropdown-header">
                    <i class="fa-solid fa-user"></i>
                    <span id="dropdown-active-label">Profile Info</span>
                    <i class="fa-solid fa-chevron-down caret"></i>
                </button>

                <div class="dropdown-menu hidden" id="mobileDropdownMenu">
                    <button class="sidebar-link active" data-section="section-profile-info">
                        <i class="fa-solid fa-user"></i> Profile Info
                    </button>

                    <button class="sidebar-link" data-section="section-summary">
                        <i class="fa-solid fa-chart-line"></i> Summary
                    </button>

                    <button class="sidebar-link" data-section="section-shared">
                        <i class="fa-solid fa-users"></i> Shared Accounts
                    </button>

                    <button class="sidebar-link" data-section="section-statements">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Statements
                    </button>

                    <button class="sidebar-link" data-section="section-notifications">
                        <i class="fa-solid fa-bell"></i> Notifications
                    </button>

                </div>
            </div>

            {{-- ========== MAIN CONTENT ========== --}}
            <main class="profile-main">

                <div id="general-status" class="status-box hidden"></div>

                {{-- ========== PROFILE INFO SECTION ========== --}}
                <section id="section-profile-info" class="profile-section active">

                    <div class="profile-header-row">
                        <div class="profile-user-card">

                            <div class="avatar-container">
                                <img src="{{ $user->avatar_url }}" class="profile-avatar-img" id="avatar-preview">
                                <input type="file" id="avatar-input" name="avatar" accept="image/*">
                                <label for="avatar-input" id="avatar-change-label">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                            </div>

                            <div class="profile-basic-info">
                                <h1 class="profile-name">{{ $user->name }}</h1>
                                <p class="profile-email">{{ $user->email }}</p>
                                <p class="profile-meta">Member since {{ $user->created_at->format('M Y') }}</p>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button class="btn btn-edit" id="edit-btn">Edit</button>
                            <button class="btn btn-logout" id="logout-btn">Log Out</button>
                            <button class="btn btn-save hidden" id="save-btn">Save</button>
                            <button class="btn btn-cancel hidden" id="cancel-btn">Cancel</button>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Personal Information</h2>

                        <form id="profile-form">
                            <div class="form-grid-2">

                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" id="full-name" class="profile-input" value="{{ $user->name }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" id="email" class="profile-input" value="{{ $user->email }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" id="phone" class="profile-input" value="{{ $user->phone_number }}" readonly>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Password</h2>
                        <div class="password-row" id="password-locked-row">
                            <input type="password" value="********" class="profile-input" readonly>
                            <button type="button" class="btn btn-secondary" id="change-password-btn">Change Password</button>
                        </div>

                        <div class="password-edit hidden" id="password-edit-block">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" id="current-password" class="profile-input">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" id="new-password" class="profile-input">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" id="confirm-password" class="profile-input">
                            </div>
                            <div class="password-actions">
                                <button type="button" class="btn btn-save" id="save-password-btn">Save Password</button>
                                <button type="button" class="btn btn-cancel" id="cancel-password-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </section>
{{-- ================= SECTION: SUMMARY ================= --}}
<section id="section-summary" class="profile-section">

    @php
        $categoryLabels = $categoryBreakdown->map(fn($c) => optional($c->category)->name ?? 'Uncategorized');
        $categoryTotals = $categoryBreakdown->pluck('total');
    @endphp

    <div class="summary-wrapper card">

        <div class="summary-header">
            <h2>Financial Summary</h2>
            <p class="summary-desc">A quick overview of your financial activity.</p>
        </div>

        <!-- STATS MINI CARDS -->
        <div class="summary-stats">
            <div class="stat-card">
                <label>Total Expenses</label>
                <div class="stat-value">RM {{ number_format($monthlyExpenses, 2) }}</div>
            </div>

            <div class="stat-card">
                <label>Transactions</label>
                <div class="stat-value">{{ $monthlyTransactionCount }}</div>
            </div>

            <div class="stat-card">
                <label>Top Category</label>
                <div class="stat-value">{{ $topCategoryName }}</div>
                <small>RM {{ number_format($topCategoryValue, 2) }}</small>
            </div>

            <div class="stat-card">
                <label>Avg per Transaction</label>
                <div class="stat-value">RM {{ number_format($averageTransaction, 2) }}</div>
            </div>
        </div>

        <!-- CHARTS + RECENT -->
        <div class="charts-grid">
            <div class="chart-card">
                <h4>Recent Activity</h4>
                <ul class="recent-list">
                    @forelse ($latestTransactions as $tx)
                    <li>
                        <span class="recent-category">{{ $tx->category->name ?? 'Uncategorized' }}</span>
                        <span class="recent-amount">RM {{ number_format($tx->amount, 2) }}</span>
                        <span class="recent-date">{{ \Carbon\Carbon::parse($tx->date)->format('d M Y') }}</span>
                    </li>
                    @empty
                    <li class="no-data">No recent transactions found.</li>
                    @endforelse
                </ul>
            </div>

            <div class="chart-card">
                <h4>Category Breakdown</h4>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</section>


{{-- ================= SECTION: SHARED ACCOUNTS ================= --}}
<section id="section-shared" class="profile-section">

    <div class="summary-wrapper card">

        <div class="summary-header">
            <h2>Shared Accounts</h2>
            <p class="summary-desc">A quick overview of your shared financial activity.</p>
        </div>

        <div class="summary-stats">
            <div class="stat-card">
                <label>Accounts Joined</label>
                <div class="stat-value">{{ $accountsJoined ?? 0 }}</div>
            </div>

            <div class="stat-card">
                <label>Total Members</label>
                <div class="stat-value">{{ $totalMembers ?? 0 }}</div>
            </div>

            <div class="stat-card">
                <label>Your Shared Spend</label>
                <div class="stat-value">RM {{ number_format($sharedSpend ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="charts-grid">

            <div class="chart-card">
                <h4>Your Shared Accounts</h4>

                <div class="account-list">
                    @forelse($sharedAccounts ?? [] as $acc)
                        <div class="account-item">
                            <div class="account-info">
                                <strong>{{ $acc->name }}</strong>
                                <p class="account-meta">Members: {{ $acc->members_count ?? 0 }} • Total Spend: RM {{ number_format($acc->transactions_sum_amount ?? 0, 2) }}</p>
                            </div>
                            <a href="{{ route('sharedaccounts.detail', $acc->id) }}" class="btn-secondary">View</a>
                        </div>
                    @empty
                        <div class="no-data" style="margin-top:10px;">
                            You haven't joined any shared accounts yet.
                        </div>
                    @endforelse
                </div>

                <details style="margin-top: 14px;">
                    <summary style="cursor: pointer; font-weight: 600; color: #3A478C;">Achieved Accounts</summary>
                    <div class="account-list" style="margin-top: 10px;">
                        @forelse($achievedSharedAccounts ?? [] as $acc)
                            <div class="account-item">
                                <div class="account-info">
                                    <strong>{{ $acc->name }}</strong>
                                    <p class="account-meta">
                                        Members: {{ $acc->members_count ?? 0 }}
                                        - Total Spend: RM {{ number_format($acc->transactions_sum_amount ?? 0, 2) }}
                                    </p>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <a href="{{ route('sharedaccounts.detail', $acc->id) }}" class="btn-secondary">View</a>
                                    <button type="button" class="btn-secondary delete-achieved-account" data-account-id="{{ $acc->id }}" style="color:#b91c1c; border-color:#f3c1c1; background:#fff5f5;">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="no-data" style="margin-top:10px;">
                                No achieved accounts.
                            </div>
                        @endforelse
                    </div>
                </details>
            </div>

            <div class="chart-card">
                <h4>Join a Shared Account</h4>

                <form class="join-form" method="POST" action="{{ route('sharedaccounts.join') }}">
                    @csrf
                    <label for="join-code">Enter invite code</label>
                    <input type="text" id="join-code" name="code" placeholder="e.g. HSE-4F9X" class="profile-input" required maxlength="6">

                    <button type="submit" class="btn-primary" style="margin-top: 10px;">
                        Join Account
                    </button>
                </form>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #e5e7eb;">

                <h4>Pending Invites</h4>

                <div class="invite-list">
                    @forelse($pendingInvites ?? [] as $invite)
                        <div class="invite-item" style="position:relative;">
                            <button class="delete-invite-btn" type="button" data-invite-id="{{ $invite->id }}" aria-label="Delete invite" style="position:absolute; top:8px; right:8px; background:none; border:none; color:#d9534f; font-weight:700; font-size:1rem; cursor:pointer;">×</button>
                            <div class="invite-meta">
                                <strong>{{ $invite->sharedAccount->name ?? 'Shared Account' }}</strong>
                                <p class="account-meta">Email: {{ $invite->recipient_contact ?? '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="no-data">No pending invitations.</p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</section>

{{-- ================= SECTION: STATEMENTS ================= --}}
<section id="section-statements" class="profile-section">
    <div class="card">
        <h2 class="card-title">Statements</h2>
        <p class="card-helper">Select an account and month to download a statement.</p>

        <form action="{{ route('profile.statements.export') }}" method="GET" class="statement-form">
            <div class="statement-row">
                
                <div class="statement-field">
                    <label>Account</label>
                    <select name="account_id">
                        <option value="all">All Accounts</option>
                        <option value="0">Personal Account</option>
                        @foreach(auth()->user()->sharedAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="statement-field">
                    <label>Statement Month</label>
                    <input type="month" name="month" value="{{ request('month') }}">
                </div>
            </div>

            <div class="statement-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-file-arrow-down"></i> Download
                </button>
                <button type="submit" name="preview" value="1" class="btn-secondary" formtarget="_blank">
                    <i class="fa-regular fa-eye"></i> Preview
                </button>
            </div>
        </form>
    </div>
</section>
{{-- ================= SECTION: NOTIFICATIONS ================= --}}
<section id="section-notifications" class="profile-section">
    <div class="card">

        <h2 class="card-title">
            <i class="fa-solid fa-bell"></i>
            Notification Preferences
        </h2>

        <!-- ================= Budget Alerts ================= -->
        <div style="margin-top:20px;">
            <div class="notif-header">
                <i class="fa-solid fa-wallet"></i>
                Budget Alerts
            </div>

            <div class="notif-row">
                <span>Notify me when budget is <strong>NEAR LIMIT (75%)</strong></span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_budget_near"
                        @checked($user->notify_budget_near ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>

            <div class="notif-row">
                <span>Notify me when budget is <strong>OVER LIMIT (100%)</strong></span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_budget_over"
                        @checked($user->notify_budget_over ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <!-- ================= Shared Account Alerts ================= -->
        <div style="margin-top:28px;">
            <div class="notif-header">
                <i class="fa-solid fa-users"></i>
                Shared Account Alerts
            </div>

            <div class="notif-row">
                <span>Notify me when shared spend is <strong>NEAR LIMIT (75%)</strong></span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_shared_near"
                        @checked($user->notify_shared_near ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>

            <div class="notif-row">
                <span>Notify me when shared spend is <strong>OVER LIMIT (100%)</strong></span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_shared_over"
                        @checked($user->notify_shared_over ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>

            <div class="notif-row">
                <span>Notify me when someone joins my shared account</span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_shared_join"
                        @checked($user->notify_shared_join ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <!-- ================= Category Alerts ================= -->
        <div style="margin-top:28px;">
            <div class="notif-header">
                <i class="fa-solid fa-folder-open"></i>
                Category Alerts
            </div>

            <div class="notif-row">
                <span>Alert me when <strong>ANY category</strong> is near limit</span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_category_near"
                        @checked($user->notify_category_near ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>

            <div class="notif-row">
                <span>Alert me when <strong>ANY category</strong> is over limit</span>
                <label class="switch">
                    <input
                        type="checkbox"
                        class="notif-toggle"
                        data-field="notify_category_over"
                        @checked($user->notify_category_over ?? false)
                    >
                    <span class="slider"></span>
                </label>
            </div>
        </div>

    </div>
</section>



</main>
</div>
</div>

{{-- JS --}}
<script src="{{ asset('profile.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const categoryLabels = {!! json_encode($categoryLabels) !!};
    const categoryTotals = {!! json_encode($categoryTotals) !!};

    // ---------------- PIE CHART ----------------
    const categoryChart = new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryTotals,
                backgroundColor: ["#3A478C","#6C5CE7","#00B894","#FDCD6E","#E17055","#0984E3"]
            }]
        },
        options: {
            plugins: { legend: { position: "bottom" }}
        }
    });

});
</script>
<script src="{{ asset('auth-guard.js') }}"></script>

</body>
</html>








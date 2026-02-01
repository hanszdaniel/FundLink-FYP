<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fundlink - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <img src="logo.PNG" alt="Fundlink Logo" class="header-logo" >
                Fundlink
            </div>
            
            <nav class="main-nav" id="mainNav">
                <a href="{{ url('/dashboard') }}" class="nav-item active">Dashboard</a>
                <a href="{{ url('/transaction') }}" class="nav-item">Transaction</a>
                <a href="{{ url('/categories') }}" class="nav-item">Categories</a>
                <a href="{{ url('/sharedaccount') }}" class="nav-item">Account</a>
                <a href="{{ url('/profile') }}" class="nav-item">Profile</a>
            </nav>

            <!-- 🔔 Notification Bell -->
<div class="notification-wrapper" style="position:relative; margin-left:12px;">
    <i class="fas fa-bell" id="notifBell" style="font-size:18px; cursor:pointer;"></i>

    @php
        $unreadCount = auth()->user()->unreadNotifications->count();
    @endphp

    @if($unreadCount > 0)
        <span class="notif-badge" style="
    position:absolute;
    top:-6px;
    right:-6px;
    background:#d9534f;
    color:white;
    font-size:10px;
    padding:2px 6px;
    border-radius:50%;
">
    {{ $unreadCount }}
</span>

    @endif
</div>


            <button class="menu-toggle" type="button" aria-label="Toggle navigation" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="welcome-bar">
            <h1>Welcome back, <span>{{ $userName }}</span></h1>
            <button class="btn-primary" id="openDashboardTx"><i class="fas fa-plus"></i> Add Transaction</button>
        </div>

        <div class="dashboard-grid">

            <div class="quick-stats">
                @php $totalAccounts = $sharedAccounts->count() + 1; @endphp
                <div class="stat-pill">
                    <span class="label">This Month Spend</span>
                    <span class="value">RM{{ number_format($expenseChartData['total'], 2) }}</span>
                    <span class="sub">Across all accounts</span>
                </div>
                <div class="stat-pill">
                    <span class="label">Accounts</span>
                    <span class="value">{{ $totalAccounts }}</span>
                    <span class="sub">1 personal + {{ $sharedAccounts->count() }} shared</span>
                </div>
                <div class="stat-pill">
                    <span class="label">Personal Progress</span>
                    <span class="value">{{ number_format($personalStats['percent'], 1) }}%</span>
                    <span class="sub">Used of personal budget</span>
                </div>
            </div>

            <div class="widget-row two-col">
                    <div class="widget shared-account-widget">
                        <div class="widget-header">
                            <h2><i class="fas fa-user-friends"></i> Accounts &amp; Members :</h2>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button class="btn-secondary" id="openDashboardAccount"><i class="fas fa-plus"></i> Add Account</button>
                                <button class="btn-secondary" id="openDashboardJoin"><i class="fas fa-users"></i> Join Shared Account</button>
                            </div>
                        </div>
                    <p class="info-text">Personal + shared accounts with quick invite actions.</p>

                    {{-- Personal account --}}
                    @php
                        $pStatusClass = $personalStats['status'] === 'over' ? 'status-over-limit' : ($personalStats['status'] === 'near' ? 'status-near-limit' : 'status-in-limit');
                        $pStatusLabel = $personalStats['status'] === 'over' ? 'Over Limit' : ($personalStats['status'] === 'near' ? 'Near Limit' : 'In Limit');
                    @endphp
                    <div class="account-list-row" style="margin-bottom:10px;">
                        <div class="account-card personal-card"
                             data-personal="1"
                             data-name="Personal Account"
                             data-type="Personal"
                             data-target="{{ $personalStats['budget'] }}"
                             data-current="{{ $personalStats['spent'] }}"
                             data-status="{{ $pStatusLabel }}"
                             data-members="1">
                            <div class="card-details">
                                <div class="account-header">
                                    <span class="account-name">Personal Account</span>
                                    <div class="account-meta">
                                        <div class="status {{ $pStatusClass }}">{{ $pStatusLabel }}</div>
                                        <div class="dropdown-container">
                                            <i class="fas fa-ellipsis-h menu-icon"></i>
                                            <div class="dropdown-menu">
                                                <button class="dropdown-item view-account" type="button" data-personal="1">
                                                    <i class="fas fa-eye" style="color: var(--primary-blue); width: 1.25rem;"></i> View
                                                </button>
                                                <button class="dropdown-item edit-account" type="button" data-personal="1">
                                                    <i class="fas fa-edit" style="color: var(--status-warning); width: 1.25rem;"></i> Edit
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="budget-row">
                                    Budget: <span class="amount">RM{{ number_format($personalStats['budget'], 2) }}</span>
                                    <span>|</span>
                                    Spent: <span class="amount">RM{{ number_format($personalStats['spent'], 2) }}</span>
                                </div>
                                <div class="members">Members: 01</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($sharedAccounts->isNotEmpty())
                        <div class="account-list-row">
                            @foreach($sharedAccounts as $acc)
                                @php
                                    $statusClass = $acc['status'] === 'over' ? 'status-over-limit' : ($acc['status'] === 'near' ? 'status-near-limit' : 'status-in-limit');
                                    $statusLabel = $acc['status'] === 'over' ? 'Over Limit' : ($acc['status'] === 'near' ? 'Near Limit' : 'In Limit');
                                @endphp
                                <div class="account-card">
                                    <div class="card-details">
                                        <div class="account-header">
                                            <span class="account-name">{{ $acc['name'] }}</span>
                                            <div class="account-meta">
                                                <div class="status {{ $statusClass }}">{{ $statusLabel }}</div>
                                                <div class="dropdown-container">
                                                    <i class="fas fa-ellipsis-h menu-icon"></i>
                                                    <div class="dropdown-menu">
                                                        <button class="dropdown-item view-account" data-id="{{ $acc['id'] }}" type="button">
                                                            <i class="fas fa-eye" style="color: var(--primary-blue); width: 1.25rem;"></i> View
                                                        </button>
                                                        <button class="dropdown-item edit-account" data-id="{{ $acc['id'] }}" type="button">
                                                            <i class="fas fa-edit" style="color: var(--status-warning); width: 1.25rem;"></i> Edit
                                                        </button>
                                                        <button class="dropdown-item delete-account delete" data-id="{{ $acc['id'] }}" type="button">
                                                            <i class="fas fa-trash-alt" style="color: var(--status-danger); width: 1.25rem;"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="budget-row">
                                            Budget: <span class="amount">RM{{ number_format($acc['budget'], 2) }}</span>
                                            <span>|</span>
                                            Spent: <span class="amount">RM{{ number_format($acc['spent'], 2) }}</span>
                                        </div>
                                        <div class="members">Members: {{ str_pad($acc['members_count'], 2, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                    <div class="card-footer">
                                        <button class="btn-copy add-member-btn" type="button" data-account-id="{{ $acc['id'] }}"><i class="fas fa-user-plus"></i> Add Member</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="account-card">
                            <div class="card-details">
                                <span class="account-name">No shared accounts yet</span>
                                <div class="status status-near-limit">Get started</div>
                                <div class="budget-row">
                                    Create or join a shared account to track with others.
                                </div>
                                <div class="members">Members: 00</div>
                            </div>
                            <div class="card-actions">
                                <a class="btn-copy" href="{{ route('sharedaccounts.index') }}"><i class="fas fa-plus"></i> New Account</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="widget summary-widget">
                    <div class="widget-header">
                        <h2><i class="fas fa-chart-pie"></i> Expenses Summary :</h2>
                    </div>
                    <p class="info-text">Spending by category this month</p>
                    <div class="summary-content">
                        
                        <div class="breakdown-list" id="breakdownList">
                            @forelse($breakdownList as $item)
                                <div class="category-item">
                                    <div class="category-details">
                                        <div class="color-dot" style="background-color: #3A478C; border-radius: 50%;"></div>
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                    <div style="font-weight: 500; color: var(--dark-text);">
                                        RM{{ number_format($item['amount'], 2) }}
                                        <span style="color: #888;">({{ number_format($item['percent'], 1) }}%)</span>
                                    </div>
                                </div>
                            @empty
                                <div class="no-data">No spending recorded this month.</div>
                            @endforelse
                        </div>

                        <div class="chart-area">
                            <div class="total-expenses">
                                <span class="label">{{ $expenseChartData['month'] }} Expenses:</span>
                                <span class="total-amount" id="totalExpenses">RM{{ number_format($expenseChartData['total'], 2) }}</span>
                            </div>
                            <canvas id="expenseChart" style="max-height: 250px;"></canvas>
                        </div>

                    </div>
                </div>
            </div>

            <div class="widget-row two-col">
                <div class="widget status-widget">
                    <h2><i class="fas fa-money-check-alt"></i> Financial Status :</h2>
                    <p class="info-text">Status for Personal and Account</p>
                    
                    @php
                        $statusClassMap = [
                            'over' => 'status-over-limit',
                            'near' => 'status-near-limit',
                            'in'   => 'status-in-limit',
                        ];
                        $statusLabelMap = [
                            'over' => 'Over Limit',
                            'near' => 'Near Limit',
                            'in'   => 'In Limit',
                        ];
                    @endphp

                    {{-- Personal --}}
                    <div class="status-card">
                        <div class="status-header">
                            <i class="fas fa-user status-icon"></i>
                            <span class="status-label">Personal</span>
                            @php
                                $pClass = $statusClassMap[$personalStats['status']] ?? 'status-in-limit';
                                $pLabel = $statusLabelMap[$personalStats['status']] ?? 'In Limit';
                            @endphp
                            <div class="status {{ $pClass }}">Status: {{ $pLabel }}</div>
                        </div>
                        <div class="budget-details">
                            Budget: <span class="amount">RM{{ number_format($personalStats['budget'], 2) }}</span> | Used: <span class="amount">RM{{ number_format($personalStats['spent'], 2) }}</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: {{ min($personalStats['percent'], 100) }}%;"></div>
                        </div>
                        <span class="usage-percent">{{ number_format($personalStats['percent'], 1) }}% (Used)</span>
                    </div>

                    {{-- Shared accounts --}}
                    @foreach($sharedAccounts as $acc)
                        @php
                            $cClass = $statusClassMap[$acc['status']] ?? 'status-in-limit';
                            $cLabel = $statusLabelMap[$acc['status']] ?? 'In Limit';
                        @endphp
                        <div class="status-card">
                            <div class="status-header">
                                <i class="fas fa-link status-icon"></i>
                                <span class="status-label">{{ $acc['name'] }}</span>
                                <div class="status {{ $cClass }}">Status: {{ $cLabel }}</div>
                            </div>
                            <div class="budget-details">
                                Budget: <span class="amount">RM{{ number_format($acc['budget'], 2) }}</span> | Used: <span class="amount">RM{{ number_format($acc['spent'], 2) }}</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: {{ min($acc['percent'], 100) }}%;"></div>
                            </div>
                            <span class="usage-percent">{{ number_format($acc['percent'], 1) }}% (Used)</span>
                        </div>
                    @endforeach
                </div>

                <div class="widget report-widget">
                    <h2><i class="fas fa-file-invoice"></i> Generate Report :</h2>
                    <p class="info-text">Generate financial report for any account</p>
                    <form id="reportForm" method="GET" action="{{ route('profile.statements.export') }}" target="_blank">
                        <div class="report-controls">
                            <label for="report-account">Account:</label>
                            <select id="report-account" name="account_id" class="select-control" required>
                                <option value="all">All Accounts</option>
                                <option value="0">Personal Account</option>
                                @foreach($sharedAccounts as $acc)
                                    <option value="{{ $acc['id'] }}">{{ $acc['name'] }}</option>
                                @endforeach
                            </select>
                            
                            <label for="report-month">Month:</label>
                            <input type="month" id="report-month" name="month" class="select-control">
                        </div>
                        <div class="report-actions" style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                            <button type="submit" class="btn-generate">Generate Report (Preview)</button>
                            <button type="submit" name="download" value="1" class="btn-secondary" style="background:#3A478C; color:#fff;">Download PDF</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
    
    <div id="statusMessage" style="position: fixed; bottom: 20px; right: 20px; background: var(--primary-blue); color: var(--white); padding: 10px 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); z-index: 100; opacity: 0; transition: opacity 0.3s;"></div>

    <!-- 🔔 Notification Dropdown -->
<div id="notifDropdown" style="
    display:none;
    position:fixed;
    top:60px;
    right:20px;
    width:340px;
    background:#fff;
    border:1px solid #ddd;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    z-index:200;
    border-radius:10px;
    max-height:420px;
    overflow-y:auto;
">
    <div style="padding:12px; font-weight:600; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <span>Notifications</span>
        <button id="notifClearAll" type="button" style="border:none; background:#f8f8f8; padding:6px 10px; border-radius:8px; font-size:0.8rem; cursor:pointer;">Clear all</button>
    </div>

    @php
        $allNotifications = auth()->user()->notifications()->latest()->get();
    @endphp

    @forelse($allNotifications as $notif)
    <div
        class="notif-item {{ $notif->read_at ? '' : 'unread' }}"
        data-id="{{ $notif->id }}"
        style="
            padding:10px 12px;
            font-size:0.85rem;
            border-bottom:1px solid #f1f1f1;
            background: {{ $notif->read_at ? '#fff' : '#f4f6ff' }};
            cursor:pointer;
            display:flex;
            justify-content:space-between;
            gap:10px;
        "
    >
        <div>
            {{ $notif->data['message'] ?? 'Budget alert' }}
            <div style="font-size:0.7rem; color:#888;">
                {{ $notif->created_at->diffForHumans() }}
            </div>
        </div>
        <button class="notif-delete" data-id="{{ $notif->id }}" style="border:none; background:transparent; color:#d9534f; cursor:pointer;">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    @empty
        <div class="notif-empty" style="padding:12px; font-size:0.85rem; color:#888;">
            No notifications
        </div>
    @endforelse
</div>

    <!-- ADD ACCOUNT MODAL (matches Account page) -->
    <div id="dashboardAddAccountModal" class="modal" style="display:none;">
        <div class="modal-content account-modal">
            <div class="modal-header">
                <h2>Create New Account</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <form action="{{ route('sharedaccounts.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="dash-account-name">Account Name</label>
                    <input type="text" id="dash-account-name" name="name" placeholder="e.g., Vacation Fund 2024" required>
                </div>
                <div class="form-group">
                    <label for="dash-account-limit">Budget Limit / Goal Amount (RM)</label>
                    <input type="number" id="dash-account-limit" name="limit_or_goal" placeholder="0.00" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="dash-account-type">Account Type</label>
                    <select id="dash-account-type" name="type" required>
                        <option value="Expense">Expense Account (Budget Limit)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dash-account-desc">Description (Optional)</label>
                    <textarea id="dash-account-desc" name="description" placeholder="Brief description of shared fund purpose"></textarea>
                </div>
                <div class="modal-footer account-modal-footer">
                    <button type="button" class="btn-secondary modal-close btn-danger">Cancel</button>
                    <button type="submit" class="btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JOIN SHARED ACCOUNT MODAL -->
    <div id="dashboardJoinModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header">
                <h2>Join Shared Account</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <form id="dashboardJoinForm" class="form-grid">
                <div class="form-group">
                    <label for="dashboard-join-code">6-Digit Authentication Code</label>
                    <input type="text" id="dashboard-join-code" maxlength="6" pattern="\d{6}" placeholder="e.g., 123456" inputmode="numeric" autocomplete="one-time-code" required>
                    <p class="info-text" style="margin-top:6px;">Enter the code you received to join an existing shared account.</p>
                </div>
                <div class="modal-footer" style="justify-content: flex-end;">
                    <button type="button" class="btn-secondary modal-close" style="background:#d9534f;">Cancel</button>
                    <button type="submit" class="btn-primary" id="dashboard-join-submit">Join</button>
                </div>
                <div id="dashboard-join-status" class="info-text" style="margin-top:6px;"></div>
            </form>
        </div>
    </div>

    <!-- VIEW ACCOUNT MODAL -->
    <div id="dashboardViewAccountModal" class="modal" style="display:none;">
        <div class="modal-content account-modal" style="max-width: 640px;">
            <div class="modal-header">
                <h2 id="viewAccountTitle">Account Details</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div id="viewAccountBody" class="account-details-view">
                <p class="info-text">Loading...</p>
            </div>
            <div class="modal-footer" style="justify-content: flex-end;">
                <button type="button" class="btn-primary modal-close">Close</button>
            </div>
        </div>
    </div>

    <!-- ADD MEMBER MODAL (Matches Account page) -->
    <div id="dashboardAddMemberModal" class="modal" style="display:none;">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="dash-member-modal-title">
            <div class="modal-header">
                <h2 id="dash-member-modal-title">Secure Member Invitation</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>

            <div id="dash-step-one">
                <p class="step-indicator"><strong>Send an invite code</strong></p>
                <form id="dash-invite-form">
                    @csrf
                    <div class="form-group">
                        <label for="dash-invitee-contact">Member's Contact (Email):</label>
                        <input type="email" id="dash-invitee-contact" placeholder="email@example.com" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label>Preferred Authentication Platform:</label>
                        <div class="platform-selection">
                            <button type="button" class="platform-button selected" id="dash-platform-email" data-platform="email">
                                <i class="fa-solid fa-envelope"></i> Email
                            </button>
                        </div>
                    </div>

                    <p class="info-text" style="margin-top:8px;">
                        We’ll send a 6-digit code to your member. They will enter it on their side to join; no need to input it here.
                    </p>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel modal-close" style="background:#d9534f;">Cancel</button>
                        <button type="button" class="btn-primary" id="dash-send-auth">Send Auth Code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT ACCOUNT MODAL -->
    <div id="dashboardEditAccountModal" class="modal" style="display:none;">
        <div class="modal-content account-modal" style="max-width: 620px;">
            <div class="modal-header">
                <h2>Edit Account Details</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <form id="dashboardEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-account-id">

                <div class="form-group">
                    <label for="edit-account-name">Account Name</label>
                    <input type="text" id="edit-account-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-account-target">Budget Limit / Goal Amount (RM)</label>
                    <input type="number" id="edit-account-target" name="target_amount" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="edit-account-desc">Description</label>
                    <textarea id="edit-account-desc" name="description" rows="3"></textarea>
                </div>
                <div class="modal-footer account-modal-footer">
                    <button type="button" class="btn-secondary btn-danger modal-close">Cancel</button>
                    <button type="submit" class="btn-primary" id="edit-save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ADD TRANSACTION MODAL -->
    <div id="addTransactionModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header">
                <h2>Add New Transaction</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <form id="dashboardTxForm" class="form-grid" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="tx-description" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Amount (RM)</label>
                        <input type="number" id="tx-amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="tx-date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="tx-category" required>
                        <option value="" disabled selected>Select category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Receipt Image (Optional)</label>
                    <input type="file" id="tx-receipt" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Transaction Account</label>
                    <select id="tx-account" required>
                        @foreach($accountOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer" style="justify-content: flex-end;">
                    <button type="button" class="btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn-primary" id="tx-submit-btn">Save Transaction</button>
                </div>
                <div id="tx-status" class="info-text" style="margin-top:6px;"></div>
            </form>
        </div>
    </div>

    <!-- ADD MEMBER MODAL (shared account) -->
    <div id="addMemberModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header">
                <h2>Secure Member Invitation</h2>
                <button class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:#3A478C; font-weight:600; margin-bottom:6px;">Step 1 of 2: <span style="color:#1A2350;">Input Contact Details</span></p>
                <div class="form-group">
                    <label>Member's Contact (Phone or Email):</label>
                    <input type="text" id="invitee-contact" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Preferred Authentication Platform:</label>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="btn-secondary platform-button selected" id="platform-whatsapp"> <i class="fab fa-whatsapp"></i> WhatsApp</button>
                        <button type="button" class="btn-secondary platform-button" id="platform-email"> <i class="fas fa-envelope"></i> Email</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close" style="background:#d9534f;">Cancel</button>
                <button type="button" class="btn-primary" id="send-auth-btn">Send Auth Code</button>
            </div>
            <div id="invite-status" class="info-text" style="margin-top:6px;"></div>
        </div>
    </div>

    <script>
    window.expenseData = @json($expenseChartData);
    </script>
    <form id="dashboard-delete-account-form" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
    <script src="{{ asset('auth-guard.js') }}"></script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fundlink - Transactions</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('transaction.css') }}">
</head>

<body>

<!-- HEADER (Unified) -->
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <img src="{{ asset('logo.PNG') }}" class="header-logo"> Fundlink
        </div>

        <nav class="main-nav" id="mainNav">
            <a href="{{ url('/dashboard') }}" class="nav-item">Dashboard</a>
            <a href="{{ url('/transaction') }}" class="nav-item active">Transaction</a>
            <a href="{{ url('/categories') }}" class="nav-item">Categories</a>
            <a href="{{ url('/sharedaccount') }}" class="nav-item">Account</a>
            <a href="{{ url('/profile') }}" class="nav-item">Profile</a>
        </nav>

        <div class="header-actions">
            <button class="menu-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>


<!-- MAIN PAGE CONTENT -->
<main class="dashboard-main">

    <!-- TITLE + ADD TRANSACTION BUTTON -->
    <div class="welcome-bar">
        <h1>Transaction History</h1>

        <button class="btn-primary fab-button" id="openTransactionModalButton">
            <i class="fas fa-plus"></i> Add Transaction
        </button>
    </div>

    <!-- FILTERS -->
    @php
        $dateRange = request('date_range', 'All Time');
        $hasFilters = request()->hasAny(['date_range', 'category_id', 'type', 'search']) &&
            ($dateRange !== 'All Time' || request('category_id') || request('type') || request('search'));
    @endphp
    <div class="widget filter-widget-full">
        <div style="display:flex; align-items:center; gap:10px; justify-content:space-between; flex-wrap:wrap;">
            <div>
                <h2>Transaction Filters</h2>
                <p class="info-text" style="margin-bottom:0;">Filter your transactions by date, category, or amount.</p>
            </div>
            @if($hasFilters)
                <div style="background:#eef1ff; color:#1a2350; padding:6px 10px; border-radius:999px; font-size:0.9rem; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-filter"></i> Filters active
                </div>
            @endif
        </div>

        <form id="transactionFilterForm" method="GET" action="{{ route('transaction') }}">
            <div class="filter-row">

                <div class="filter-item">
                    <label>Date Range</label>
                    <select id="dateRange" name="date_range">
                        <option value="All Time" {{ $dateRange === 'All Time' ? 'selected' : '' }}>All Time</option>
                        <option value="This Month" {{ $dateRange === 'This Month' ? 'selected' : '' }}>This Month</option>
                        <option value="Last Month" {{ $dateRange === 'Last Month' ? 'selected' : '' }}>Last Month</option>
                        <option value="This Year" {{ $dateRange === 'This Year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Category</label>
                    <select id="category" name="category_id">
                        <option value="all">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Type</label>
                    <select id="type" name="type">
                        <option {{ request('type', 'All Types') === 'All Types' ? 'selected' : '' }}>All Types</option>
                        <option {{ request('type') === 'Personal' ? 'selected' : '' }}>Personal</option>
                        <option {{ request('type') === 'Shared' ? 'selected' : '' }}>Shared (All)</option>
                        @foreach($shared_accounts as $shared)
                            @php $sharedValue = 'shared:' . $shared->id; @endphp
                            <option value="{{ $sharedValue }}" {{ request('type') === $sharedValue ? 'selected' : '' }}>
                                Shared: {{ $shared->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Search</label>
                    <input type="text" id="searchDesc" name="search" placeholder="Search..." value="{{ request('search') }}">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Search</button>
                    <button type="button" class="btn-secondary" id="resetFilters">Reset</button>
                </div>

            </div>
        </form>
    </div>

    <!-- TRANSACTION TABLE -->
    <div class="widget">

        <div class="widget-header">

            <div class="widget-title-group">
                <h2>All Transactions</h2>
                <p class="info-text">Detailed breakdown of all financial movements.</p>
            </div>

            <div class="table-actions">
                <a href="{{ route('profile.statements.export') }}"
                   class="btn-secondary"
                   title="Download full statement (all accounts)">
                    <i class="fa-solid fa-file-pdf"></i> Export Statement
                </a>
            </div>

        </div>

        <div class="transactions-table-wrapper">
            <div class="table-container">
                <div class="mobile-table-head mobile-only">
                    <span>Description</span>
                    <span>Date</span>
                    <span>Amount</span>
                    <span>Actions</span>
                </div>

                <table class="transaction-table">

                    <!-- DESKTOP HEADERS -->
                    <thead>
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Account</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach($transactions as $transaction)
                    <tr class="transaction-row">

                        <td class="td-description desktop-only">
                            <div class="desc-main">{{ $transaction->description }}</div>
                        </td>

                        <td class="desktop-only category-cell">
                            <span class="category-tag">{{ $transaction->category->name }}</span>
                        </td>

                        <td class="desktop-only account-cell">
                            @if($transaction->sharedAccount)
                                <span class="shared-tag">Shared: {{ $transaction->sharedAccount->name }}</span>
                            @else
                                <span class="personal-tag">Personal</span>
                            @endif
                        </td>

                        <td class="desktop-only date-cell">
                            {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}
                        </td>

                        <td class="desktop-only amount-cell">
                            RM{{ number_format($transaction->amount, 2) }}
                        </td>

                        <td class="desktop-only action-column">
                            <div class="dropdown-container">
                                <button class="menu-dots" type="button" aria-label="Transaction actions">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </button>

                                <div class="dropdown-content">
                                    <a href="#"
                                       class="view-transaction"
                                       data-id="{{ $transaction->id }}"
                                       data-description="{{ $transaction->description }}"
                                       data-category="{{ $transaction->category->name }}"
                                       data-account="{{ $transaction->sharedAccount ? 'Account: ' . $transaction->sharedAccount->name : 'Personal Account' }}"
                                       data-account-type="{{ $transaction->sharedAccount ? 'Shared' : 'Personal' }}"
                                       data-account-name="{{ $transaction->sharedAccount->name ?? '' }}"
                                       data-date="{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}"
                                       data-date-iso="{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}"
                                       data-amount="RM{{ number_format($transaction->amount, 2) }}"
                                       data-amount-raw="{{ $transaction->amount }}"
                                       data-receipt="{{ $transaction->receipt_image_path ? asset('storage/' . $transaction->receipt_image_path) : '' }}">
                                       <span class="dropdown-icon" style="color: #223f7f;"><i class="fas fa-eye"></i></span> View
                                    </a>

                                    <a href="#"
                                       class="edit-transaction"
                                       data-id="{{ $transaction->id }}"
                                       data-description="{{ $transaction->description }}"
                                       data-category="{{ $transaction->category->name }}"
                                       data-account="{{ $transaction->sharedAccount ? 'Account: ' . $transaction->sharedAccount->name : 'Personal Account' }}"
                                       data-account-type="{{ $transaction->sharedAccount ? 'Shared' : 'Personal' }}"
                                       data-account-name="{{ $transaction->sharedAccount->name ?? '' }}"
                                       data-date="{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}"
                                       data-date-iso="{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}"
                                       data-amount="RM{{ number_format($transaction->amount, 2) }}"
                                       data-amount-raw="{{ $transaction->amount }}"
                                       data-shared-id="{{ $transaction->shared_account_id ?? 0 }}"
                                       data-category-id="{{ $transaction->category->id }}">
                                       <span class="dropdown-icon" style="color: #d38b1f;"><i class="fas fa-pen-to-square"></i></span> Edit
                                    </a>

                                    <a href="#"
                                       class="delete-transaction"
                                       data-id="{{ $transaction->id }}"
                                       data-description="{{ $transaction->description }}">
                                       <span class="dropdown-icon" style="color: #d9534f;"><i class="fas fa-trash-alt"></i></span> Delete
                                    </a>
                                </div>
                            </div>
                        </td>

                        <!-- MOBILE -->
                        <td class="mobile-only mobile-row">
                            <div class="mobile-grid">
                                <div class="mobile-desc">{{ $transaction->description }}</div>
                                <div class="mobile-date">
                                    {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}
                                </div>
                                <div class="mobile-amount">RM{{ number_format($transaction->amount, 2) }}</div>
                                <div class="mobile-actions">
                                    <div class="dropdown-container">
                                        <button class="menu-dots" type="button" aria-label="Transaction actions">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </button>
                                        <div class="dropdown-content">
                                            <a href="#"
                                               class="view-transaction"
                                               data-id="{{ $transaction->id }}"
                                               data-description="{{ $transaction->description }}"
                                               data-category="{{ $transaction->category->name }}"
                                               data-account="{{ $transaction->sharedAccount ? 'Account: ' . $transaction->sharedAccount->name : 'Personal Account' }}"
                                               data-account-type="{{ $transaction->sharedAccount ? 'Shared' : 'Personal' }}"
                                               data-account-name="{{ $transaction->sharedAccount->name ?? '' }}"
                                               data-date="{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}"
                                               data-date-iso="{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}"
                                               data-amount="RM{{ number_format($transaction->amount, 2) }}"
                                               data-amount-raw="{{ $transaction->amount }}"
                                               data-receipt="{{ $transaction->receipt_image_path ? asset('storage/' . $transaction->receipt_image_path) : '' }}">
                                                View
                                            </a>
                                            <a href="#"
                                               class="edit-transaction"
                                               data-id="{{ $transaction->id }}"
                                               data-description="{{ $transaction->description }}"
                                               data-category="{{ $transaction->category->name }}"
                                               data-account="{{ $transaction->sharedAccount ? 'Account: ' . $transaction->sharedAccount->name : 'Personal Account' }}"
                                               data-account-type="{{ $transaction->sharedAccount ? 'Shared' : 'Personal' }}"
                                               data-account-name="{{ $transaction->sharedAccount->name ?? '' }}"
                                               data-date="{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}"
                                               data-date-iso="{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}"
                                               data-amount="RM{{ number_format($transaction->amount, 2) }}"
                                               data-amount-raw="{{ $transaction->amount }}"
                                               data-shared-id="{{ $transaction->shared_account_id ?? 0 }}"
                                               data-category-id="{{ $transaction->category->id }}">
                                                Edit
                                            </a>
                                            <a href="#"
                                               class="delete-transaction"
                                               data-id="{{ $transaction->id }}"
                                               data-description="{{ $transaction->description }}">
                                                Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>

                    </tr>
                    @endforeach

                    </tbody>

                </table>

            </div>
        </div>
    </div>

</main>

<!-- EXPORT STATEMENT MODAL -->
<div id="exportModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Export Account Statement</h2>
            <button class="modal-close">&times;</button>
        </div>

        <form method="GET" action="{{ route('profile.statements.export') }}">

            <div class="form-group">
                <label>Select Account</label>
                <select name="account_id" class="form-select" required>
                    <option value="all">All Accounts</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control">
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-download"></i> Generate PDF
                </button>
            </div>

        </form>
    </div>
</div>

<!-- VIEW MODAL -->
<div id="viewTransactionModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Transaction Details</h2>
            <button class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <p><strong>Description:</strong> <span id="v-desc"></span></p>
            <p><strong>Category:</strong> <span id="v-category"></span></p>
            <p><strong>Account:</strong> <span id="v-account"></span></p>
            <p><strong>Date:</strong> <span id="v-date"></span></p>
            <p><strong>Amount:</strong> <span id="v-amount"></span></p>
            <p><strong>Image:</strong> <span id="v-receipt-text">No image</span></p>
            <div id="v-receipt-wrap" style="display:none; margin-top:8px;">
                <a id="v-receipt-link" href="#" target="_blank" rel="noopener">
                    <img id="v-receipt-img" alt="Receipt image" style="max-width:100%; border-radius:8px; cursor: zoom-in;">
                </a>
                <div style="margin-top:8px;">
                    <a id="v-receipt-download" href="#" download class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                        <i class="fas fa-download"></i> Download Image
                    </a>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary modal-close">Close</button>
        </div>

    </div>
</div>


<!-- ADD TRANSACTION MODAL -->
<div id="addTransactionModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Add New Transaction</h2>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="addTransactionForm" enctype="multipart/form-data">

            <div class="form-group">
                <label>Description</label>
                <input type="text" id="new_description" name="description" required>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label>Amount (RM)</label>
                    <input type="number" id="new_amount" name="amount" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="new_date" name="date" required>
                </div>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select id="new_category" name="category_id" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Receipt Image (Optional)</label>
                <input type="file" id="new_receipt_image" name="receipt_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Transaction Account</label>
                <select id="new_type" name="shared_account_id" required>
                    <option value="0">Personal Account</option>
                    @foreach($shared_accounts as $shared)
                        <option value="{{ $shared->id }}">{{ $shared->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-status" id="addTransactionStatus" aria-live="polite"></div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">Cancel</button>
                <button type="submit" class="btn-primary">Save Transaction</button>
            </div>

        </form>
    </div>
</div>

<script src="{{ asset('transaction.js') }}?v={{ filemtime(public_path('transaction.js')) }}"></script>
<script src="{{ asset('auth-guard.js') }}"></script>

</body>
</html>

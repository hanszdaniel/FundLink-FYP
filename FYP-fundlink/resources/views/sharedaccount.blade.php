<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundlink - Account Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- CRUCIAL FOR LARAVEL AJAX --}}
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    {{-- LINK TO EXTERNAL CSS FILE --}}
    <link rel="stylesheet" href="{{ asset('sharedaccount.css') }}">
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <img src="{{ asset('logo.PNG') }}" alt="Fundlink Logo" class="header-logo" >
                Fundlink
            </div>

            <nav class="main-nav" id="mainNav">
                {{-- NOTE: Ensure these routes are defined in routes/web.php --}}
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


    <div class="dashboard-main">
        <div class="welcome-bar">
            <div>
                <h1 class="page-title">Account Overview</h1>
                <p class="info-text">Manage and track expenses together with others.</p>
            </div>
            <div class="welcome-actions">
                <button class="btn-primary full-width-mobile" onclick="openModal('addAccountModal')">
                    <i class="fas fa-plus" style="margin-right: 5px;"></i> Add Account
                </button>
                <button class="btn-secondary full-width-mobile" onclick="openJoinModal()">
                    <i class="fas fa-users" style="margin-right: 5px;"></i> Join Shared Account
                </button>
            </div>
        </div>

        {{-- PERSONAL ACCOUNT (Non-deletable) --}}
        @isset($personalAccount)
            <div class="widget" 
                 data-account-id="{{ $personalAccount['id'] }}"
                 data-account-type="{{ $personalAccount['type'] }}"
                 data-name="{{ $personalAccount['name'] }}"
                 data-description="{{ $personalAccount['description'] }}"
                 data-target="{{ $personalAccount['limit_or_goal'] }}"
                 data-current="{{ $personalAccount['current_amount'] }}"
                 data-status-text="{{ $personalAccount['status_text'] }}"
                 data-status-color="{{ $personalAccount['status_color'] }}"
                 data-status-bg="{{ $personalAccount['status_bg'] }}"
                 data-members='@json($personalAccount['members'] ?? [["name" => "You"]])'
                 data-can-delete="0"
                 data-is-personal="1">
                
                <div class="action-menu">
                    <button class="kebab-menu-button" onclick="toggleDropdown(event, 'dropdown-personal')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div id="dropdown-personal" class="dropdown-menu">
                        <a href="#" class="dropdown-btn" onclick="openViewModal('{{ $personalAccount['id'] }}'); event.preventDefault();">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="#" class="dropdown-btn" onclick="openEditModal('{{ $personalAccount['id'] }}'); event.preventDefault();">
                            <i class="fas fa-edit"></i> Edit Account
                        </a>
                    </div>
                </div>

                <div class="account-info">
                    <h2>{{ $personalAccount['name'] }}</h2>
                    <p class="info-text">{{ $personalAccount['description'] }}</p>
                </div>
                
                <div class="account-details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Budget</span>
                        <span class="detail-value">RM{{ number_format($personalAccount['limit_or_goal'], 2) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Amount Used</span>
                        <span class="detail-value" style="color: {{ $personalAccount['status_color'] }};">RM{{ number_format($personalAccount['current_amount'], 2) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status" style="background-color: {{ $personalAccount['status_bg'] }}; color: {{ $personalAccount['status_color'] }}; border: 1px solid {{ $personalAccount['status_color'] }};">
                                {{ $personalAccount['status_text'] }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="progress-bar-wrap">
                    <p class="info-text" style="margin-bottom: 5px; text-align: right; font-size: 0.9rem;">
                        {{ $personalAccount['percentage'] }}% Used (RM{{ number_format($personalAccount['remaining'], 2) }} Remaining)
                    </p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: {{ $personalAccount['percentage'] }}%; background-color: {{ $personalAccount['status_color'] }};"></div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="members">
                        Members (1):
                        <span class="member-icon" style="background: #3A478C;" title="You">Y</span>
                    </div>
                </div>
            </div>
        @endisset

        {{-- DYNAMIC ACCOUNT CARDS (LARAVEL BLADE LOOP) --}}
        @if ($accounts->isEmpty())
            <div class="widget" style="text-align: center; padding: 50px;">
                <p class="info-text">You are not currently part of any accounts. Click "Add Account" to begin!</p>
            </div>
        @else
            @foreach ($accounts as $account)
                <div class="widget" data-account-id="{{ $account['id'] }}" data-account-type="{{ $account['type'] }}"
                     data-name="{{ $account['name'] }}"
                     data-description="{{ $account['description'] }}"
                     data-target="{{ $account['limit_or_goal'] }}"
                     data-current="{{ $account['current_amount'] }}"
                     data-status-text="{{ $account['status_text'] }}"
                     data-status-color="{{ $account['status_color'] }}"
                     data-status-bg="{{ $account['status_bg'] }}"
                     data-members='@json($account['members'])'
                     data-can-delete="{{ $account['can_delete'] ? 1 : 0 }}"
                     data-is-personal="0">
                    
                    <div class="action-menu">
                        <button class="kebab-menu-button" onclick="toggleDropdown(event, 'dropdown-{{ $account['id'] }}')">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div id="dropdown-{{ $account['id'] }}" class="dropdown-menu">
                            <button type="button" onclick="openViewModal({{ $account['id'] }}); event.stopPropagation();" class="dropdown-btn">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button type="button" onclick="openEditModal({{ $account['id'] }}); event.stopPropagation();" class="dropdown-btn">
                                <i class="fas fa-edit"></i> Edit Account
                            </button>
                            <button type="button" class="dropdown-btn add-member-link" data-account-id="{{ $account['id'] }}" onclick="openAddMemberModal({{ $account['id'] }}); event.stopPropagation();">
                                <i class="fas fa-user-plus"></i> Add Member
                            </button>
                            @if($account['can_delete'])
                            <button type="button" class="dropdown-btn delete-option delete-account-link" data-account-id="{{ $account['id'] }}" onclick="deleteAccount(event, {{ $account['id'] }}); event.stopPropagation();">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                            @elseif($account['can_leave'])
                            <button type="button" class="dropdown-btn leave-option leave-account-link" data-account-id="{{ $account['id'] }}" onclick="leaveSharedAccount(event, {{ $account['id'] }}); event.stopPropagation();">
                                <i class="fas fa-sign-out-alt"></i> Leave Account
                            </button>
                            @endif
                        </div>
                    </div>

                    <div class="account-info">
                        <h2>{{ $account['name'] }}</h2>
                        <p class="info-text">{{ $account['description'] }}</p>
                    </div>
                    
                    <div class="account-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">{{ $account['type'] === 'Expense' ? 'Budget Limit' : 'Goal Amount' }}</span>
                            <span class="detail-value">RM{{ number_format($account['limit_or_goal'], 2) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">{{ $account['type'] === 'Expense' ? 'Amount Used' : 'Amount Saved' }}</span>
                            <span class="detail-value" style="color: {{ $account['status_color'] }};">RM{{ number_format($account['current_amount'], 2) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="status" style="background-color: {{ $account['status_bg'] }}; color: {{ $account['status_color'] }}; border: 1px solid {{ $account['status_color'] }};">
                                    {{ $account['status_text'] }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="progress-bar-wrap">
                        <p class="info-text" style="margin-bottom: 5px; text-align: right; font-size: 0.9rem;">
                            {{ $account['percentage'] }}% {{ $account['type'] === 'Expense' ? 'Used' : 'Saved' }} (RM{{ number_format($account['remaining'], 2) }} Remaining)
                        </p>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: {{ $account['percentage'] }}%; background-color: {{ $account['status_color'] }};"></div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="members">
                            Members ({{ count($account['members']) }}):
                            @foreach ($account['members'] as $member)
                                <span class="member-icon" style="background: {{ $member['color'] }};" title="{{ $member['name'] }}">
                                    {{ $member['initial'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

    </div>

    {{-- MODALS (Add, View, Edit, Add Member) --}}
    {{-- Keeping modals in the view file is common to prevent FOUC (Flash of unstyled content) --}}

    {{-- --- MODAL: Add New Account --- --}}
    <div id="addAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Account</h2>
                <span class="modal-close" onclick="closeModal('addAccountModal')">&times;</span>
            </div>
            <form action="{{ route('sharedaccounts.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="new-name">Account Name</label>
                    <input type="text" id="new-name" name="name" placeholder="e.g., Vacation Fund 2024" required>
                </div>
                <div class="form-group">
                    <label for="new-budget">Budget Limit / Goal Amount (RM)</label>
                    <input type="number" id="new-budget" name="limit_or_goal" placeholder="0.00" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="new-type">Account Type</label>
                    <select id="new-type" name="type" required>
                        <option value="Expense">Expense Account (Budget Limit)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new-desc">Description (Optional)</label>
                    <textarea id="new-desc" name="description" placeholder="Brief description of shared fund purpose"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addAccountModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- --- MODAL: View Account Details --- --}}
    <div id="viewAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="view-modal-title">Account Details: Loading...</h2>
                <span class="modal-close" onclick="closeModal('viewAccountModal')">&times;</span>
            </div>
            <div class="account-details-view" id="view-modal-content">
                <p>Please wait while details are loaded...</p>
                {{-- Dynamic content loaded here via JS/AJAX (openViewModal function) --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeModal('viewAccountModal')">Close</button>
            </div>
        </div>
    </div>

    {{-- --- MODAL: Edit Account Details --- --}}
    <div id="editAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Account Details</h2>
                <span class="modal-close" onclick="closeModal('editAccountModal')">&times;</span>
            </div>
            <form id="edit-form" method="POST">
    @csrf
    @method('PUT')

    <input type="hidden" name="account_id" id="edit-account-id">

    <div class="form-group">
        <label for="edit-name">Account Name</label>
        <input type="text" id="edit-name" name="name" required>
    </div>

    <div class="form-group">
        <label for="edit-target">Budget Limit / Goal Amount (RM)</label>
        <input type="number" id="edit-target" name="target_amount" min="0" step="100" required>
    </div>

    <input type="hidden" name="type" value="Expense">

    <div class="form-group">
        <label for="edit-desc">Description</label>
        <textarea id="edit-desc" name="description"></textarea>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('editAccountModal')">Cancel</button>
        <button type="submit" class="btn-save">Save Changes</button>
    </div>
</form>
        </div>
    </div>
    
    {{-- --- MODAL: Add Member (Invitation with 2 Steps) --- --}}
    <div id="addMemberModal" class="modal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="member-modal-title">
            <div class="modal-header">
                <h2 id="member-modal-title">Secure Member Invitation</h2>
                <span class="modal-close" onclick="closeModal('addMemberModal')">&times;</span>
            </div>
            
            <div id="step-one">
                <p class="step-indicator"><strong>Send an invite code</strong></p>
                <form id="invite-form">
                    <div class="form-group">
                        <label for="invitee-contact">Member's Contact (Email):</label>
                        <input type="text" id="invitee-contact" placeholder="email@example.com" required autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred Authentication Platform:</label>
                        <div class="platform-selection">
                            <button type="button" class="platform-button selected" id="platform-email" onclick="selectPlatform('email')">
                                <i class="fa-solid fa-envelope"></i> Email</button>
                        </div>
                    </div>

                    <p class="info-text" style="margin-top:8px;">
                        We’ll send a 6-digit code to your member. They will enter it on their side to join; no need to input it here.
                    </p>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeModal('addMemberModal')">Cancel</button>
                        <button type="button" class="btn-primary" onclick="sendAuthCode()">Send Auth Code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- --- MODAL: Join Shared Account (Enter Invite Code) --- --}}
    <div id="joinAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Join Shared Account</h2>
                <span class="modal-close" onclick="closeModal('joinAccountModal')">&times;</span>
            </div>
            <div class="form-group">
                <label for="join-code">6-Digit Authentication Code</label>
                <input type="text" id="join-code" maxlength="6" pattern="\d{6}" placeholder="e.g., 123456" inputmode="numeric">
                <p class="info-text" style="margin-top: 6px;">Enter the code you received to join an existing shared account.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('joinAccountModal')">Cancel</button>
                <button type="button" class="btn-primary" id="join-submit-btn" onclick="submitJoinCode()">Join</button>
            </div>
        </div>
    </div>
    
    {{-- LINK TO EXTERNAL JAVASCRIPT FILE --}}
    <script src="{{ asset('sharedaccount.js') }}?v=6"></script>
    <script src="{{ asset('auth-guard.js') }}"></script>
</body>
</html>

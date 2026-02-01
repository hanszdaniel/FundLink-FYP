let currentPlatform = 'email'; // default to email so invites actually send mail
let currentAccountId = null;

// CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// --- Mobile Nav (unified) ---
function setupMobileNav() {
    const menuToggle = document.querySelector(".menu-toggle");
    const mainNav    = document.getElementById("mainNav");
    if (!menuToggle || !mainNav) return;

    menuToggle.addEventListener("click", () => {
        mainNav.classList.toggle("nav-visible");
    });

    mainNav.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => mainNav.classList.remove("nav-visible"));
    });

    document.addEventListener("click", (e) => {
        if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
            mainNav.classList.remove("nav-visible");
        }
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            mainNav.classList.remove("nav-visible");
        }
    });
}

// Close the mobile nav (used by modals)
function closeMobileMenu() {
    const mainNav = document.getElementById("mainNav");
    if (mainNav) {
        mainNav.classList.remove("nav-visible");
    }
}

// --- Dropdown ---
function toggleDropdown(event, dropdownId) {
    event.stopPropagation();
    const dropdown = document.getElementById(dropdownId);

    document.querySelectorAll('.dropdown-menu').forEach(d => {
        if (d.id !== dropdownId) d.classList.remove('show');
    });

    dropdown.classList.toggle('show');
    console.log('[sharedaccount] dropdown', dropdownId, dropdown.classList.contains('show') ? 'opened' : 'closed');
}

// --- Modals ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    if(modalId === 'addMemberModal') {
        resetToStepOne(false);
    }

    modal.classList.add('open');
    closeMobileMenu();

    document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.remove('show'));
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
}

// Join modal helper
function openJoinModal() {
    const input = document.getElementById('join-code');
    if (input) {
        input.value = '';
        input.focus();
    }
    openModal('joinAccountModal');
}

// Helper to get the widget element
function getAccountCard(accountId) {
    return document.querySelector(`.widget[data-account-id="${accountId}"]`);
}

// --- Open specific modals ---
function openAddMemberModal(accountId) {
    currentAccountId = accountId;
    console.log('[sharedaccount] Add member clicked for account', accountId);
    // Default to email channel when opening the modal
    selectPlatform('email');
    const modal = document.getElementById('addMemberModal');
    if (!modal) {
        alert('Unable to open Add Member modal (missing element).');
        return;
    }
    openModal('addMemberModal');
    const input = document.getElementById('invitee-contact');
    if (input) input.focus();
}

function openViewModal(accountId) {
    currentAccountId = accountId;
    const titleEl = document.getElementById('view-modal-title');
    const contentEl = document.getElementById('view-modal-content');
    const card = getAccountCard(accountId);

    const renderTxListHtml = (txs) => {
        if (!Array.isArray(txs) || txs.length === 0) {
            return '<p class="info-text" style="margin-top:10px;">No transactions for this account yet.</p>';
        }

        return txs.map(tx => `
            <div class="tx-row">
                <div class="tx-main">
                    <div class="tx-title">${tx.description || 'No description'}</div>
                    <div class="tx-meta">${tx.category || 'Uncategorized'} - ${tx.date || ''} ${tx.time ? tx.time : ''}</div>
                </div>
                <div class="tx-amount">RM${Number(tx.amount || 0).toFixed(2)}</div>
            </div>
        `).join('');
    };

    // Renders modal content from the card dataset (fallback when API fails)
    const renderFromCard = (cardEl, message) => {
        if (!cardEl) {
            titleEl.innerText = 'Account Details';
            contentEl.innerHTML = `<p class="info-text">${message || 'Could not load account details.'}</p>`;
            openModal('viewAccountModal');
            return;
        }

        const name = cardEl.dataset.name || 'Account';
        const description = cardEl.dataset.description || 'No description provided.';
        const type = cardEl.dataset.accountType || cardEl.dataset.type || 'Account';
        const target = Number(cardEl.dataset.target || 0);
        const current = Number(cardEl.dataset.current || 0);
        const remaining = Math.max(target - current, 0);
        const percentage = cardEl.dataset.percentage
            ? Number(cardEl.dataset.percentage)
            : (target > 0 ? Math.min(Math.round((current / target) * 100), 999) : 0);
        const typeLabel = type === 'Savings' ? 'Saved' : 'Used';

        let members = [];
        if (cardEl.dataset.members) {
            try {
                members = JSON.parse(cardEl.dataset.members);
            } catch (e) {
                members = [];
            }
        }

        const memberChips = members.length
            ? members.map(m => {
                const name = (typeof m === 'string') ? m : (m && (m.name || m.full_name || m.user_name));
                return `<span class="member-chip">${name || 'Member'}</span>`;
            }).join('')
            : '<span class="info-text">No members added yet.</span>';

        const detailRows = `
            <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${type}</span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${description}</span></div>
            <div class="detail-row"><span class="detail-label">${type === 'Savings' ? 'Goal' : 'Budget'}</span><span class="detail-value">RM${target.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">${typeLabel}</span><span class="detail-value">RM${current.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${remaining.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${percentage}%</span></div>
        `;

        const membersSection = `
            <div class="members-joined-view">
                <div class="members-joined-header">
                    <h4>Members Joined</h4>
                    <p class="info-text">People currently in this account.</p>
                </div>
                <div class="member-chip-group">${memberChips}</div>
            </div>
        `;

        titleEl.innerText = `${name} Details`;
        contentEl.innerHTML = `
            ${detailRows}
            ${membersSection}
            <hr style="margin: 12px 0;">
            <p class="info-text">${message || 'Transactions could not be loaded right now.'}</p>
        `;
        openModal('viewAccountModal');
    };

    if (!titleEl || !contentEl) return;

    titleEl.innerText = 'Loading account...';
    contentEl.innerHTML = `<p class="info-text">Fetching account and transactions...</p>`;

    const isPersonal = accountId === 'personal' || (card && card.dataset.isPersonal === '1');

    // Personal account lives outside the shared-account API; render directly from card data + personal transactions
    if (isPersonal) {
        const name = card?.dataset.name || 'Personal Account';
        const description = card?.dataset.description || 'Your individual transactions and balances.';
        const target = Number(card?.dataset.target || 0);
        const current = Number(card?.dataset.current || 0);
        const remaining = Math.max(target - current, 0);
        const percentage = card?.dataset?.percentage
            ? Number(card.dataset.percentage)
            : (target > 0 ? Math.min(Math.round((current / target) * 100), 999) : 0);

        let members = [];
        if (card && card.dataset.members) {
            try {
                members = JSON.parse(card.dataset.members);
            } catch (e) {
                members = [];
            }
        }

        const memberChips = members.length
            ? members.map(m => {
                const name = (typeof m === 'string') ? m : (m && (m.name || m.full_name || m.user_name));
                return `<span class="member-chip">${name || 'Member'}</span>`;
            }).join('')
            : '<span class="info-text">No members added yet.</span>';

        const detailRows = `
            <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">Personal</span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${description}</span></div>
            <div class="detail-row"><span class="detail-label">Budget</span><span class="detail-value">RM${target.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Used</span><span class="detail-value">RM${current.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${remaining.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${percentage}%</span></div>
        `;

        const membersSection = `
            <div class="members-joined-view">
                <div class="members-joined-header">
                    <h4>Members Joined</h4>
                    <p class="info-text">People currently in this account.</p>
                </div>
                <div class="member-chip-group">${memberChips}</div>
            </div>
        `;

        fetch('/personal/transactions')
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load personal transactions.');
                }

                const txList = renderTxListHtml(data.transactions || []);

                titleEl.innerText = `${name} Details`;
                contentEl.innerHTML = `
                    ${detailRows}
                    ${membersSection}
                    <hr style="margin: 12px 0;">
                    <div class="tx-list">${txList}</div>
                `;
            })
            .catch((err) => {
                console.error(err);
                renderFromCard(card, err?.message || 'Could not load account details.');
            })
            .finally(() => openModal('viewAccountModal'));
        return;
    }

    fetch(`/sharedaccount/${accountId}/transactions`)
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to load account details.');
            }

            const acc = data.account || {};
            const txs = data.transactions || [];
            const card = getAccountCard(accountId);

            const typeLabel = acc.type === 'Savings' ? 'Saved' : 'Used';
            const target = Number(acc.target_amount || 0);
            const current = Number(acc.current_amount || 0);
            const remaining = Number(acc.remaining || 0);
            const percentage = Number(acc.percentage || 0);
            let members = [];
            // Prefer API response
            if (Array.isArray(acc.members)) {
                members = acc.members;
            } else if (Array.isArray(acc.account_members)) {
                members = acc.account_members;
            } else if (Array.isArray(acc.members_list)) {
                members = acc.members_list;
            }
            // Fallback to data attributes from the card when API omits members
            if ((!members || members.length === 0) && card && card.dataset.members) {
                try {
                    members = JSON.parse(card.dataset.members);
                } catch (e) {
                    members = [];
                }
            }

            const memberChips = members.length
                ? members.map(m => {
                    const name = (typeof m === 'string') ? m :
                        (m && (m.name || m.full_name || m.user_name || m.user?.name));
                    return `<span class="member-chip">${name || 'Member'}</span>`;
                }).join('')
                : '<span class="info-text">No members added yet.</span>';

            const detailRows = `
                <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${acc.type || 'Account'}</span></div>
                <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${acc.description || 'No description provided.'}</span></div>
                <div class="detail-row"><span class="detail-label">Budget / Goal</span><span class="detail-value">RM${target.toFixed(2)}</span></div>
                <div class="detail-row"><span class="detail-label">${typeLabel}</span><span class="detail-value">RM${current.toFixed(2)}</span></div>
                <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${remaining.toFixed(2)}</span></div>
                <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${percentage}%</span></div>
            `;

            const membersSection = `
                <div class="members-joined-view">
                    <div class="members-joined-header">
                        <h4>Members Joined</h4>
                        <p class="info-text">People currently in this account.</p>
                    </div>
                    <div class="member-chip-group">${memberChips}</div>
                </div>
            `;

            const txList = renderTxListHtml(txs);

            titleEl.innerText = `${acc.name || 'Account'} Details`;
            contentEl.innerHTML = `
                ${detailRows}
                ${membersSection}
                <hr style="margin: 12px 0;">
                <div class="tx-list">${txList}</div>
            `;
        })
        .catch((err) => {
            console.error(err);
            // Gracefully fall back to card data when API fails
            renderFromCard(card, err?.message || 'Could not load account details.');
        })
        .finally(() => openModal('viewAccountModal'));
}
async function openEditModal(accountId) {
    const card = getAccountCard(accountId);
    const saveBtn = document.querySelector('#editAccountModal .btn-save');

    if (card && card.dataset.isPersonal === '1') {
        document.getElementById('edit-account-id').value = accountId;
        document.getElementById('edit-name').value = card.dataset.name || '';
        document.getElementById('edit-target').value = card.dataset.target || 0;
        document.getElementById('edit-desc').value = card.dataset.description || '';

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }

        openModal('editAccountModal');
        return;
    }

    if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    }

    try {
        const response = await fetch(`/sharedaccount/view/${accountId}`);
        const data = await response.json();

        if (!response.ok || !data.success) {
            alert("Failed to fetch account details.");
            return;
        }

        document.getElementById('edit-account-id').value = data.id;
        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-target').value = data.target_amount || 0;
        document.getElementById('edit-desc').value = data.description || '';

        openModal('editAccountModal');

    } catch (err) {
        console.error("Error loading account:", err);
        alert("Could not load account details.");
    }
}

// --- Select Platform ---
function selectPlatform(platform) {
    // Force lowercase
    currentPlatform = platform.toLowerCase();

    // Remove highlight
    document.querySelectorAll('.platform-button').forEach(btn => btn.classList.remove('selected'));

    // Highlight the selected button
    const btn = document.getElementById(`platform-${currentPlatform}`);
    if (btn) btn.classList.add('selected');

    // Select input field
    const input = document.getElementById('invitee-contact');

    // Update placeholder based on platform
    if (currentPlatform === 'email') {
        input.placeholder = "email@example.com";
        input.type = "email";   // improve UI validation
    } 
    else if (currentPlatform === 'whatsapp') {
        input.placeholder = "+60123456789";
        input.type = "text";
    }
}


// --- Send Auth Code ---
async function sendAuthCode() {
    const contact = document.getElementById('invitee-contact').value.trim();

    if (!contact || !currentAccountId) {
        alert("Please ensure contact and account are valid.");
        return;
    }

    try {
        const response = await fetch(`/sharedaccount/${currentAccountId}/invite/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken 
            },
            body: JSON.stringify({
                contact: contact,
                type: currentPlatform // lowercase email / whatsapp
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeModal('addMemberModal');
            alert("Auth code sent. Ask your member to enter it on their end to join.");
        } else {
            alert("Error sending code: " + (data.message || ""));
        }

    } catch (error) {
        console.error('sendAuthCode error:', error);
        alert('API error while sending code.');
    }
}

// --- Join Shared Account ---
async function submitJoinCode() {
    const codeInput = document.getElementById('join-code');
    const joinBtn = document.getElementById('join-submit-btn');
    const code = codeInput ? codeInput.value.trim() : '';

    if (!code || code.length !== 6 || !/^\d+$/.test(code)) {
        alert("Please enter a valid 6-digit code.");
        return;
    }

    if (joinBtn) {
        joinBtn.disabled = true;
        joinBtn.textContent = 'Joining...';
    }

    try {
        const response = await fetch('/sharedaccount/join', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ code })
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok && data.success) {
            alert(`Joined ${data.account?.name || 'shared account'} successfully!`);
            closeModal('joinAccountModal');
            location.reload();
        } else {
            alert(data.message || 'Unable to join with this code. Please try again or request a new invite.');
        }
    } catch (error) {
        console.error('joinSharedAccount error:', error);
        alert('Server error while joining the account.');
    } finally {
        if (joinBtn) {
            joinBtn.disabled = false;
            joinBtn.textContent = 'Join';
        }
    }
}

// --- Reset Steps ---
function resetToStepOne(showMessage = true) {
    const stepOne = document.getElementById('step-one');
    const stepTwo = document.getElementById('step-two');
    if (stepOne) stepOne.style.display = 'block';
    if (stepTwo) stepTwo.style.display = 'none';

    const title = document.getElementById('member-modal-title');
    if (title) title.innerText = 'Secure Member Invitation';

    const authCode = document.getElementById('auth-code');
    if (authCode) authCode.value = '';

    if (showMessage) alert("Code resend started (placeholder).");
}

// --- Delete Account ---
async function deleteAccount(event, accountId) {
    event.preventDefault();
    const widget = getAccountCard(accountId);
    if (widget && widget.dataset.canDelete === '0') {
        alert("This account cannot be deleted.");
        return;
    }

    if (!confirm("Delete this shared account?")) return;

    const keepTransactions = confirm(
        "Keep all transactions? Click OK to keep, or Cancel to delete all transactions permanently."
    );

    try {
        let response = await fetch(`/sharedaccount/${accountId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ keep_transactions: keepTransactions ? 1 : 0 })
        });

        // Fallback: some setups only accept POST + _method
        if (response.status === 405 || response.status === 404) {
            response = await fetch(`/sharedaccount/${accountId}`, {
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

        const result = await response.json().catch(() => ({}));

        if (response.ok && result.success) {
            alert(result.message || "Account deleted.");
            const w = event.target.closest('.widget');
            if (w) {
                w.style.opacity = '0';
                setTimeout(() => w.remove(), 300);
            }
        } else {
            alert("Delete failed: " + (result.message || "Unable to delete account."));
        }

    } catch (error) {
        console.error('Delete error:', error);
        alert('Server error.');
    }
}

// --- Leave Shared Account ---
async function leaveSharedAccount(event, accountId) {
    event.preventDefault();

    if (!confirm("Leave this shared account? You will lose access to it.")) return;

    const keepTransactions = confirm(
        "Keep your transactions from this shared account? Click OK to keep, or Cancel to delete them."
    );

    try {
        let response = await fetch(`/sharedaccount/${accountId}/leave`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ keep_transactions: keepTransactions ? 1 : 0 })
        });

        if (response.status === 405 || response.status === 404) {
            response = await fetch(`/sharedaccount/${accountId}/leave`, {
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

        const result = await response.json().catch(() => ({}));

        if (response.ok && result.success) {
            alert(result.message || "You left the account.");
            const w = event.target.closest('.widget');
            if (w) {
                w.style.opacity = '0';
                setTimeout(() => w.remove(), 300);
            }
        } else {
            alert("Leave failed: " + (result.message || "Unable to leave account."));
        }

    } catch (error) {
        console.error('Leave error:', error);
        alert('Server error.');
    }
}

// --- Global click listeners ---
window.onload = function() {
    console.log('[sharedaccount] script loaded');
    setupMobileNav();

    // Direct bindings for dropdown buttons
    document.querySelectorAll('.add-member-link').forEach(btn => {
        btn.addEventListener('click', (e) => {
            console.log('[sharedaccount] Direct handler: add-member clicked', btn.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            openAddMemberModal(btn.dataset.accountId);
        });
    });
    document.querySelectorAll('.delete-account-link').forEach(btn => {
        btn.addEventListener('click', (e) => {
            console.log('[sharedaccount] Direct handler: delete clicked', btn.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            deleteAccount(e, btn.dataset.accountId);
        });
    });
    document.querySelectorAll('.leave-account-link').forEach(btn => {
        btn.addEventListener('click', (e) => {
            console.log('[sharedaccount] Direct handler: leave clicked', btn.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            leaveSharedAccount(e, btn.dataset.accountId);
        });
    });

    // Delegated handlers in case elements are added dynamically
    document.addEventListener('click', (e) => {
        const addLink = e.target.closest('.add-member-link');
        if (addLink) {
            console.log('[sharedaccount] Delegated handler: add-member clicked', addLink.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            openAddMemberModal(addLink.dataset.accountId);
            return;
        }
        const delLink = e.target.closest('.delete-account-link');
        if (delLink) {
            console.log('[sharedaccount] Delegated handler: delete clicked', delLink.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            deleteAccount(e, delLink.dataset.accountId);
            return;
        }
        const leaveLink = e.target.closest('.leave-account-link');
        if (leaveLink) {
            console.log('[sharedaccount] Delegated handler: leave clicked', leaveLink.dataset.accountId);
            e.preventDefault();
            e.stopPropagation();
            leaveSharedAccount(e, leaveLink.dataset.accountId);
            return;
        }
    }, true); // capture to win against bubbling handlers

    // Minimal click logger to confirm events are firing (first 10 only)
    let clickLogCount = 0;
    document.addEventListener('click', (e) => {
        if (clickLogCount < 10) {
            console.log('[sharedaccount] click on', e.target.tagName, e.target.className);
            clickLogCount++;
        }
    }, true);

    // existing window click handler for dropdowns/modals
    window.onclick = function(event) {
        // Keep dropdown open if click happens inside dropdown; close otherwise
        if (!event.target.closest('.kebab-menu-button') && !event.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(d => d.classList.remove('show'));
        }

        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('open');
            event.target.style.display = 'none';
        }
    };
};

// --- Edit Form ---
document.getElementById('edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const accountId = document.getElementById('edit-account-id').value;
    const card = getAccountCard(accountId);

    const formData = new FormData(e.target);

    try {
        let response;
        if (card && card.dataset.isPersonal === '1') {
            // Personal account uses a dedicated POST endpoint; remove spoofed PUT
            formData.delete('_method');
            response = await fetch(`/sharedaccount/personal`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
        } else {
            formData.append('_method', 'PUT');
            response = await fetch(`/sharedaccount/${accountId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
        }

        const result = await response.json();

        if (response.ok && result.success) {
            alert("Account updated!");
            closeModal('editAccountModal');
            location.reload();
        } else {
            alert("Update failed.");
        }

    } catch (err) {
        console.error('Update error:', err);
        alert('Server error.');
    }
});

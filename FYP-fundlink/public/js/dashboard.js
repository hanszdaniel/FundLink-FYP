// ===================================================
// GLOBAL DATA
// ===================================================
const expenseData = window.expenseData || {
    labels: [],
    amounts: [],
    colors: [],
    currency: "RM",
};

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

// ===================================================
// STATUS MESSAGE
// ===================================================
function showStatusMessage(message, duration = 3000) {
    const el = document.getElementById("statusMessage");
    if (!el) return;
    el.textContent = message;
    el.style.opacity = "1";
    setTimeout(() => (el.style.opacity = "0"), duration);
}

// ===================================================
// EXPENSE CHART
// ===================================================
function initExpenseChart() {
    const canvas = document.getElementById("expenseChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    const total = expenseData.amounts.reduce((a, b) => a + b, 0);

    const breakdown = document.getElementById("breakdownList");
    if (!expenseData.amounts.length || total <= 0) {
        breakdown.innerHTML =
            '<div class="no-data">No spending recorded this month.</div>';
        return;
    }

    breakdown.innerHTML = "";
    expenseData.labels.forEach((label, i) => {
        const amount = expenseData.amounts[i];
        const percent = ((amount / total) * 100).toFixed(1);
        breakdown.innerHTML += `
            <div class="category-item">
                <div class="category-details">
                    <span class="color-dot" style="background:${expenseData.colors[i]}"></span>
                    <span>${label}</span>
                </div>
                <div>${expenseData.currency}${amount} (${percent}%)</div>
            </div>
        `;
    });

    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: expenseData.labels,
            datasets: [{
                data: expenseData.amounts,
                backgroundColor: expenseData.colors,
                borderWidth: 0,
            }],
        },
        options: {
            cutout: "70%",
            plugins: {
                legend: { display: false },
            },
        },
    });
}

// ===================================================
// MOBILE MENU
// ===================================================
function toggleMobileMenu() {
    const nav = document.getElementById("mainNav");
    if (nav) {
        nav.classList.toggle("nav-visible");
    }
}
function closeMobileMenu() {
    const nav = document.getElementById("mainNav");
    if (nav) {
        nav.classList.remove("nav-visible");
    }
}

// Close nav when a link is clicked (mobile)
document.querySelectorAll(".main-nav a").forEach(link => {
    link.addEventListener("click", () => closeMobileMenu());
});

// ===================================================
// ADD TRANSACTION MODAL
// ===================================================
const dashModal = document.getElementById("addTransactionModal");
const openDashBtn = document.getElementById("openDashboardTx");
const dashForm = document.getElementById("dashboardTxForm");
const dashStatus = document.getElementById("tx-status");

const addAccountModal = document.getElementById("dashboardAddAccountModal");
const openAccountBtn = document.getElementById("openDashboardAccount");
const viewAccountModal = document.getElementById("dashboardViewAccountModal");
const viewAccountBody = document.getElementById("viewAccountBody");
const viewAccountTitle = document.getElementById("viewAccountTitle");
const editAccountModal = document.getElementById("dashboardEditAccountModal");
const editForm = document.getElementById("dashboardEditForm");
const editName = document.getElementById("edit-account-name");
const editTarget = document.getElementById("edit-account-target");
const editDesc = document.getElementById("edit-account-desc");
const editId = document.getElementById("edit-account-id");
const joinAccountModal = document.getElementById("dashboardJoinModal");
const openJoinBtn = document.getElementById("openDashboardJoin");
const joinForm = document.getElementById("dashboardJoinForm");
const joinInput = document.getElementById("dashboard-join-code");
const joinStatus = document.getElementById("dashboard-join-status");
const dashAddMemberModal = document.getElementById("dashboardAddMemberModal");
const dashInviteContact = document.getElementById("dash-invitee-contact");
const dashStepOne = document.getElementById("dash-step-one");
let dashCurrentAccountId = null;
let dashCurrentPlatform = "email";

function closeModalEl(modal) {
    if (modal) modal.style.display = "none";
}

function dashSelectPlatform(type) {
    dashCurrentPlatform = type;
    document.querySelectorAll("#dashboardAddMemberModal .platform-button").forEach(btn => btn.classList.remove("selected"));
    const btn = document.querySelector(`#dashboardAddMemberModal .platform-button[data-platform="${type}"]`);
    if (btn) btn.classList.add("selected");

    if (dashInviteContact) {
        if (type === "email") {
            dashInviteContact.placeholder = "email@example.com";
            dashInviteContact.type = "email";
        } else if (type === "whatsapp") {
            dashInviteContact.placeholder = "+60123456789";
            dashInviteContact.type = "text";
        }
    }
}

function dashResetToStepOne() {
    if (dashStepOne) dashStepOne.style.display = "block";
    const title = document.getElementById("dash-member-modal-title");
    if (title) title.textContent = "Secure Member Invitation";
}

function openDashboardAddMember(accountId) {
    dashCurrentAccountId = accountId;
    dashResetToStepOne();
    dashSelectPlatform("email");
    if (dashAddMemberModal) dashAddMemberModal.style.display = "flex";
    if (dashInviteContact) dashInviteContact.focus();
}

async function dashSendAuthCode() {
    if (!dashCurrentAccountId || !dashInviteContact) return;
    const contact = dashInviteContact.value.trim();
    if (!contact) {
        alert("Please enter a contact.");
        return;
    }

    try {
        let res = await fetch(`/sharedaccount/${dashCurrentAccountId}/invite/send`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ contact, type: dashCurrentPlatform }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            closeModalEl(dashAddMemberModal);
            alert("Auth code sent. Ask your member to enter it on their end to join.");
        } else {
            alert(data.message || "Unable to send authentication code.");
        }
    } catch (err) {
        alert("Server error while sending code.");
    }
}

function buildPersonalAccountData(cardEl) {
    if (!cardEl?.dataset) return null;
    const target = parseFloat(cardEl.dataset.target || "0");
    const current = parseFloat(cardEl.dataset.current || "0");
    const remaining = Math.max(target - current, 0);
    const percentage = target > 0 ? Math.round((current / target) * 100) : 0;
    return {
        name: cardEl.dataset.name || "Personal Account",
        description: "Your individual transactions and balances.",
        type: cardEl.dataset.type || "Personal",
        target_amount: target,
        current_amount: current,
        remaining,
        percentage,
        status: cardEl.dataset.status || "On Track",
        members: cardEl.dataset.members || "1",
    };
}

openDashBtn?.addEventListener("click", () => {
    dashModal.style.display = "flex";
    dashForm?.reset();
    if (dashStatus) dashStatus.textContent = "";
});

openAccountBtn?.addEventListener("click", () => {
    addAccountModal.style.display = "flex";
    const form = addAccountModal.querySelector("form");
    form?.reset();
    const firstInput = addAccountModal.querySelector("input, select, textarea");
    firstInput?.focus();
});

document.querySelectorAll(".modal-close").forEach(btn => {
    btn.addEventListener("click", () => closeModalEl(btn.closest(".modal")));
});

window.addEventListener("click", e => {
    if (e.target?.classList?.contains("modal")) {
        closeModalEl(e.target);
    }
});

// JOIN SHARED ACCOUNT MODAL
openJoinBtn?.addEventListener("click", () => {
    if (!joinAccountModal) return;
    joinAccountModal.style.display = "flex";
    if (joinForm) joinForm.reset();
    if (joinStatus) joinStatus.textContent = "";
    joinInput?.focus();
});

joinForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const code = joinInput?.value.trim() || "";
    if (!/^\d{6}$/.test(code)) {
        if (joinStatus) joinStatus.textContent = "Please enter a valid 6-digit code.";
        return;
    }
    if (joinStatus) joinStatus.textContent = "Joining...";
    try {
        const res = await fetch("/sharedaccount/join", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
            body: JSON.stringify({ code })
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            if (joinStatus) joinStatus.textContent = "Joined successfully.";
            setTimeout(() => location.reload(), 600);
        } else {
            if (joinStatus) joinStatus.textContent = data.message || "Unable to join. Please try again.";
        }
    } catch (err) {
        if (joinStatus) joinStatus.textContent = "Server error while joining.";
    }
});

dashForm?.addEventListener("submit", async e => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("description", document.getElementById("tx-description").value);
    formData.append("amount", document.getElementById("tx-amount").value);
    formData.append("date", document.getElementById("tx-date").value);
    formData.append("category_id", document.getElementById("tx-category").value);
    formData.append(
        "shared_account_id",
        document.getElementById("tx-account").value || ""
    );

    const receiptInput = document.getElementById("tx-receipt");
    if (receiptInput?.files?.[0]) {
        formData.append("receipt_image", receiptInput.files[0]);
    }

    dashStatus.textContent = "Saving...";

    const res = await fetch("/transaction", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
        },
        body: formData,
    });

    if (res.ok) {
        showStatusMessage("Transaction added");
        setTimeout(() => location.reload(), 500);
    } else {
        dashStatus.textContent = "Failed to save transaction";
    }
});

// ===================================================
// SHARED ACCOUNT DROPDOWNS
// ===================================================
document.querySelectorAll(".dropdown-container").forEach(container => {
    const icon = container.querySelector(".menu-icon");
    const menu = container.querySelector(".dropdown-menu");

    icon?.addEventListener("click", e => {
        e.stopPropagation();
        document.querySelectorAll(".dropdown-menu")
            .forEach(d => d.classList.remove("active"));
        menu.classList.toggle("active");
        const rect = menu.getBoundingClientRect();
        const overflows = rect.right > window.innerWidth;
        if (overflows) {
            menu.style.left = "auto";
            menu.style.right = "0";
        }
    });
});

document.addEventListener("click", () =>
    document.querySelectorAll(".dropdown-menu")
        .forEach(d => d.classList.remove("active"))
);

// Platform selection buttons in dashboard add member modal
document.querySelectorAll("#dashboardAddMemberModal .platform-button").forEach(btn => {
    btn.addEventListener("click", () => dashSelectPlatform(btn.dataset.platform || "email"));
});

document.getElementById("dash-send-auth")?.addEventListener("click", dashSendAuthCode);

// ===================================================
// SHARED ACCOUNT ACTIONS (Dashboard cards)
// ===================================================
async function openAccountView(id) {
    if (!viewAccountModal || !viewAccountBody || !viewAccountTitle) return;
    viewAccountTitle.textContent = "Account Details";
    viewAccountBody.innerHTML = '<p class="info-text">Loading...</p>';
    viewAccountModal.style.display = "flex";

    try {
        const res = await fetch(`/sharedaccount/${id}/transactions`);
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || "Unable to load account.");

        const account = data.account || {};
        const txs = data.transactions || [];
        const members = account.members || [];
        viewAccountTitle.textContent = `${account.name || "Account"} Details`;

        const memberChips = members.length
            ? members.map(m => `<span class="member-chip">${m}</span>`).join(" ")
            : '<span class="info-text">No members added yet.</span>';

        const txList = txs.length
            ? txs.map(tx => `
                <div class="tx-row">
                    <div class="tx-main">
                        <div class="tx-title">${tx.description || "No description"}</div>
                        <div class="tx-meta">${tx.category || "Uncategorized"} - ${tx.date || ""}</div>
                    </div>
                    <div class="tx-amount">RM${Number(tx.amount || 0).toFixed(2)}</div>
                </div>
            `).join("")
            : '<p class="info-text" style="margin-top:10px;">No transactions for this account yet.</p>';

        viewAccountBody.innerHTML = `
            <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${account.type || "-"}</span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${account.description || "No description provided."}</span></div>
            <div class="detail-row"><span class="detail-label">Budget / Goal</span><span class="detail-value">RM${Number(account.target_amount || 0).toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Used</span><span class="detail-value">RM${Number(account.current_amount || 0).toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${Number(account.remaining || 0).toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${account.percentage ?? 0}%</span></div>
            <hr style="margin:12px 0;">
            <div class="members-joined-view">
                <div class="members-joined-header">
                    <h4>Members Joined</h4>
                    <p class="info-text">People currently in this account.</p>
                </div>
                <div class="member-chip-group">${memberChips}</div>
            </div>
            <hr style="margin:12px 0;">
            <div class="tx-list">${txList}</div>
        `;
    } catch (err) {
        viewAccountBody.innerHTML = `<p class="info-text">Could not load account details.</p>`;
    }
}

document.querySelectorAll(".view-account").forEach(btn => {
    btn.addEventListener("click", async () => {
        const card = btn.closest(".account-card");
        const isPersonal = btn.dataset.personal === "1" || card?.dataset.personal === "1";
        if (isPersonal) {
            const data = buildPersonalAccountData(card);
            if (!data || !viewAccountModal || !viewAccountBody || !viewAccountTitle) return;

            viewAccountTitle.textContent = data.name;
            viewAccountBody.innerHTML = '<p class="info-text">Loading...</p>';
            viewAccountModal.style.display = "flex";

            try {
                const res = await fetch("/personal/transactions");
                const personalData = await res.json().catch(() => ({}));
                const txs = Array.isArray(personalData.transactions) ? personalData.transactions : [];

                const txList = txs.length
                    ? txs.map(tx => `
                        <div class="tx-row">
                            <div class="tx-main">
                                <div class="tx-title">${tx.description || "No description"}</div>
                                <div class="tx-meta">${tx.category || "Uncategorized"} - ${tx.date || ""}</div>
                            </div>
                            <div class="tx-amount">RM${Number(tx.amount || 0).toFixed(2)}</div>
                        </div>
                    `).join("")
                    : '<p class="info-text" style="margin-top:10px;">No transactions for this account yet.</p>';

                viewAccountBody.innerHTML = `
                    <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${data.type}</span></div>
                    <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${data.description}</span></div>
                    <div class="detail-row"><span class="detail-label">Budget / Goal</span><span class="detail-value">RM${Number(data.target_amount || 0).toFixed(2)}</span></div>
                    <div class="detail-row"><span class="detail-label">Used</span><span class="detail-value">RM${Number(data.current_amount || 0).toFixed(2)}</span></div>
                    <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${Number(data.remaining || 0).toFixed(2)}</span></div>
                    <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${data.percentage}%</span></div>
                    <hr style="margin:12px 0;">
                    <div class="members-joined-view">
                        <div class="members-joined-header">
                            <h4>Members Joined</h4>
                            <p class="info-text">People currently in this account.</p>
                        </div>
                        <div class="member-chip-group">
                            <span class="member-chip">You</span>
                        </div>
                    </div>
                    <hr style="margin:12px 0;">
                    <div class="tx-list">${txList}</div>
                    <p class="info-text" style="margin-top:10px;">Personal account transactions appear in your dashboard totals.</p>
                `;
            } catch (err) {
                viewAccountBody.innerHTML = `<p class="info-text">Could not load personal transactions.</p>`;
            }
            return;
        }

        const id = btn.dataset.id;
        if (id) openAccountView(id);
    });
});

async function openAccountEdit(id) {
    if (!editAccountModal || !editForm || !editName || !editTarget || !editDesc || !editId) return;
    editForm.dataset.id = id;
    editAccountModal.style.display = "flex";
    editName.value = "";
    editTarget.value = "";
    editDesc.value = "";
    editId.value = id;

    try {
        const res = await fetch(`/sharedaccount/view/${id}`);
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || "Unable to load account.");

        editName.value = data.name || "";
        editTarget.value = data.target_amount || 0;
        editDesc.value = data.description || "";
    } catch (err) {
        editName.value = "";
        editTarget.value = "";
        editDesc.value = "";
    }
}

document.querySelectorAll(".edit-account").forEach(btn => {
    btn.addEventListener("click", () => {
        const card = btn.closest(".account-card");
        const isPersonal = btn.dataset.personal === "1" || card?.dataset.personal === "1";
        if (isPersonal) {
            if (!editAccountModal || !editForm || !editName || !editTarget || !editDesc || !editId) return;
            const target = card?.dataset?.target || "0";
            editForm.dataset.id = "personal";
            editAccountModal.style.display = "flex";
            editName.value = "Personal Account";
            editTarget.value = target;
            editDesc.value = "Your individual transactions and balances.";
            editId.value = "personal";
            editName.disabled = true;
            editDesc.disabled = true;
            return;
        }

        const id = btn.dataset.id;
        if (id) openAccountEdit(id);
    });
});

editForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const id = editForm.dataset.id || editId?.value;
    if (!id) return;

    if (id === "personal") {
        const payload = new FormData();
        payload.append("target_amount", editTarget?.value || 0);

        try {
            const res = await fetch("/sharedaccount/personal", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
                body: payload,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success !== true) throw new Error(data.message || "Could not save changes.");
            closeModalEl(editAccountModal);
            showStatusMessage("Personal budget updated");
            setTimeout(() => location.reload(), 400);
        } catch (err) {
            showStatusMessage(err.message || "Failed to update personal budget", 3000);
        } finally {
            editName.disabled = false;
            editDesc.disabled = false;
        }
        return;
    }

    const payload = {
        name: editName?.value || "",
        target_amount: editTarget?.value || 0,
        description: editDesc?.value || "",
        type: "Expense",
    };

    try {
        const res = await fetch(`/sharedaccount/${id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success !== true) throw new Error(data.message || "Could not save changes.");
        closeModalEl(editAccountModal);
        showStatusMessage("Account updated");
        setTimeout(() => location.reload(), 400);
    } catch (err) {
        showStatusMessage(err.message || "Failed to save account", 3000);
    } finally {
        editName.disabled = false;
        editDesc.disabled = false;
    }
});

document.querySelectorAll(".delete-account").forEach(btn => {
    btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        if (!id) return;
        if (!confirm("Delete this account? This action cannot be undone.")) return;

        try {
            let res = await fetch(`/sharedaccount/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
            });

            // Fallback for servers that need POST + _method
            if (res.status === 405 || res.status === 404) {
                res = await fetch(`/sharedaccount/${id}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json",
                    },
                    body: JSON.stringify({ _method: "DELETE" }),
                });
            }

            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                alert(data.message || "Account deleted.");
                setTimeout(() => location.reload(), 200);
            } else {
                alert(data.message || "Unable to delete account.");
            }
        } catch (err) {
            alert("Server error while deleting account.");
        }
    });
});

document.querySelectorAll(".add-member-btn[data-account-id]").forEach(btn => {
    btn.addEventListener("click", e => {
        e.preventDefault();
        const id = btn.dataset.accountId;
        if (id) openDashboardAddMember(id);
    });
});

// ===================================================
// 🔔 NOTIFICATION SYSTEM (FINAL & FIXED)
// ===================================================
document.addEventListener("DOMContentLoaded", function () {
    const bell = document.getElementById("notifBell");
    const dropdown = document.getElementById("notifDropdown");

    if (!bell || !dropdown) return;

    function refreshEmptyState() {
        const items = dropdown.querySelectorAll(".notif-item");
        const empty = dropdown.querySelector(".notif-empty");
        if (!items.length) {
            if (!empty) {
                const div = document.createElement("div");
                div.className = "notif-empty";
                div.style.padding = "12px";
                div.style.fontSize = "0.85rem";
                div.style.color = "#888";
                div.textContent = "No notifications";
                dropdown.appendChild(div);
            }
        } else if (empty) {
            empty.remove();
        }
    }

    async function deleteNotification(id, element) {
        try {
            const res = await fetch(`/notifications/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success !== true) throw new Error("Delete failed");
            if (element) element.remove();
            refreshEmptyState();

            // Update badge
            const badge = bell.parentElement.querySelector(".notif-badge");
            if (badge) {
                const newCount = Math.max(
                    0,
                    parseInt(badge.textContent || "0", 10) - 1
                );
                if (newCount <= 0) {
                    badge.remove();
                } else {
                    badge.textContent = newCount;
                }
            }
        } catch (err) {
            showStatusMessage("Could not delete notification");
        }
    }

    async function clearAllNotifications() {
        try {
            const res = await fetch("/notifications", {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success !== true) throw new Error("Clear failed");
            dropdown.querySelectorAll(".notif-item").forEach(n => n.remove());
            refreshEmptyState();
            const badge = bell.parentElement.querySelector(".notif-badge");
            if (badge) badge.remove();
        } catch (err) {
            showStatusMessage("Could not clear notifications");
        }
    }

    dropdown.addEventListener("click", (e) => {
        const deleteBtn = e.target.closest(".notif-delete");
        if (deleteBtn) {
            e.stopPropagation();
            const id = deleteBtn.dataset.id;
            const item = deleteBtn.closest(".notif-item");
            if (id) deleteNotification(id, item);
        }
    });

    const clearBtn = document.getElementById("notifClearAll");
    if (clearBtn) {
        clearBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            if (confirm("Clear all notifications?")) clearAllNotifications();
        });
    }

    bell.addEventListener("click", function (e) {
        e.stopPropagation();

        const isOpen = dropdown.style.display === "block";
        dropdown.style.display = isOpen ? "none" : "block";

        // ✅ Mark all as read ONLY when opened
        if (!isOpen) {
            fetch("/notifications/read-all", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            }).then(() => {
                document
                    .querySelectorAll(".notif-item.unread")
                    .forEach(item => item.classList.remove("unread"));

                const badge =
                    bell.parentElement.querySelector(".notif-badge");
                if (badge) badge.remove();
            });
        }
    });

    dropdown.addEventListener("click", e => e.stopPropagation());

    document.addEventListener("click", () => {
        dropdown.style.display = "none";
    });
});

// ===================================================
// INIT
// ===================================================
window.onload = initExpenseChart;

// Simple status / toast box
function showStatus(message, type = "success") {
    const box = document.getElementById("general-status");
    if (!box) return;

    box.textContent = message;
    box.style.backgroundColor = type === "success" ? "#d9f99d" : "#fee2e2";
    box.style.color = type === "success" ? "#365314" : "#991b1b";
    box.classList.remove("hidden");

    setTimeout(() => box.classList.add("hidden"), 2500);
}

// Logout handler
async function handleLogout(csrfToken) {
    try {
        await fetch("/logout", {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": csrfToken || ""
            }
        });
    } catch (_) {
        // Ignore network errors; still force a client-side logout.
    } finally {
        localStorage.clear();
        sessionStorage.clear();
        window.location.replace("/login");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    // Avatar input/preview (defined early so other functions can access)
    const avatarInput = document.getElementById("avatar-input");
    const avatarPreview = document.getElementById("avatar-preview");

    /* -------------------------------
       MOBILE NAV (HEADER)
    --------------------------------*/
    const menuToggle = document.querySelector(".menu-toggle");
    const mainNav = document.getElementById("mainNav");

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", () => {
            mainNav.classList.toggle("nav-visible");
        });

        // Close nav when a link is clicked (mobile)
        mainNav.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", () => {
                mainNav.classList.remove("nav-visible");
            });
        });

        // Close nav when clicking outside
        document.addEventListener("click", (e) => {
            if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                mainNav.classList.remove("nav-visible");
            }
        });

        // Close nav on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                mainNav.classList.remove("nav-visible");
            }
        });
    }

    /* -------------------------------
       DESKTOP SIDEBAR + MOBILE DROPDOWN
       (single active section at a time)
    --------------------------------*/
    const sidebarLinks = document.querySelectorAll(".sidebar-link");
    const sections = document.querySelectorAll(".profile-section");
    const mobileDropdownBtn = document.getElementById("mobileDropdownBtn");
    const dropdownMenu = document.getElementById("mobileDropdownMenu");
    const dropdownActiveLabel = document.getElementById("dropdown-active-label");

    function setActiveSection(targetId, labelText) {
        if (!targetId) return;

        sections.forEach(sec => {
            sec.classList.toggle("active", sec.id === targetId);
        });

        sidebarLinks.forEach(link => {
            link.classList.toggle("active", link.dataset.section === targetId);
        });

        if (dropdownActiveLabel && labelText) {
            dropdownActiveLabel.textContent = labelText;
        }

        if (dropdownMenu) dropdownMenu.classList.add("hidden");
        if (mobileDropdownBtn) mobileDropdownBtn.classList.remove("open");
    }

    sidebarLinks.forEach(link => {
        link.addEventListener("click", () => {
            const targetId = link.dataset.section;
            const labelText = link.textContent.trim();
            setActiveSection(targetId, labelText);
        });
    });

    if (mobileDropdownBtn && dropdownMenu) {
        mobileDropdownBtn.addEventListener("click", () => {
            dropdownMenu.classList.toggle("hidden");
            mobileDropdownBtn.classList.toggle("open");
        });
    }

    /* -------------------------------
       JOIN SHARED ACCOUNT (Profile card)
    --------------------------------*/
    const joinFormProfile = document.querySelector(".join-form");
    const joinCodeInput = document.getElementById("join-code");
    const joinSubmitBtn = joinFormProfile?.querySelector("button[type='submit']");
    const deleteInviteButtons = document.querySelectorAll(".delete-invite-btn[data-invite-id]");
    const deleteAchievedButtons = document.querySelectorAll(".delete-achieved-account[data-account-id]");

    joinFormProfile?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const code = (joinCodeInput?.value || "").trim();
        if (code.length !== 6) {
            showStatus("Please enter a valid 6-digit code.", "error");
            return;
        }

        if (joinSubmitBtn) {
            joinSubmitBtn.disabled = true;
            joinSubmitBtn.textContent = "Joining...";
        }

        try {
            const res = await fetch("/sharedaccount/join", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ code }),
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.success) {
                showStatus(data.message || "Joined shared account.", "success");
                setTimeout(() => window.location.reload(), 600);
            } else {
                showStatus(data.message || "Unable to join. Please check the code.", "error");
            }
        } catch (err) {
            showStatus("Server error while joining. Try again.", "error");
        } finally {
            if (joinSubmitBtn) {
                joinSubmitBtn.disabled = false;
                joinSubmitBtn.textContent = "Join Account";
            }
        }
    });

    deleteInviteButtons.forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.inviteId;
            if (!id) return;
            if (!confirm("Delete this invitation?")) return;

            try {
                const res = await fetch(`/sharedaccount/invite/${id}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json",
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    showStatus(data.message || "Invite deleted.", "success");
                    const item = btn.closest(".invite-item");
                    if (item) item.remove();
                } else {
                    showStatus(data.message || "Unable to delete invite.", "error");
                }
            } catch (err) {
                showStatus("Server error while deleting invite.", "error");
            }
        });
    });

    deleteAchievedButtons.forEach(btn => {
        btn.addEventListener("click", async () => {
            const accountId = btn.dataset.accountId;
            if (!accountId) return;
            if (!confirm("Permanently delete this shared account and all its transactions?")) return;

            try {
                let res = await fetch(`/sharedaccount/${accountId}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({ keep_transactions: 0 }),
                });

                if (res.status === 405 || res.status === 404) {
                    res = await fetch(`/sharedaccount/${accountId}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: JSON.stringify({
                            _method: "DELETE",
                            keep_transactions: 0,
                        }),
                    });
                }

                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    showStatus(data.message || "Shared account deleted.", "success");
                    const item = btn.closest(".account-item");
                    if (item) item.remove();
                } else {
                    showStatus(data.message || "Unable to delete shared account.", "error");
                }
            } catch (err) {
                showStatus("Server error while deleting shared account.", "error");
            }
        });
    });

    /* -------------------------------
       EDIT / SAVE / CANCEL LOGIC
    --------------------------------*/
    const form = document.getElementById("profile-form");
    const inputs = form ? form.querySelectorAll(".profile-input") : [];
    const editBtn = document.getElementById("edit-btn");
    const saveBtn = document.getElementById("save-btn");
    const cancelBtn = document.getElementById("cancel-btn");
    const logoutBtn = document.getElementById("logout-btn");
    const emailInput = document.getElementById("email");

    // Password controls
    const changePasswordBtn = document.getElementById("change-password-btn");
    const savePasswordBtn = document.getElementById("save-password-btn");
    const cancelPasswordBtn = document.getElementById("cancel-password-btn");
    const passwordEditBlock = document.getElementById("password-edit-block");
    const passwordLockedRow = document.getElementById("password-locked-row");
    const currentPassword = document.getElementById("current-password");
    const newPassword = document.getElementById("new-password");
    const confirmPassword = document.getElementById("confirm-password");

    let initialValues = {};

    function storeInitialValues() {
        inputs.forEach(input => {
            initialValues[input.id] = input.value;
        });
    }

    function toggleEditMode(isEditing) {
        inputs.forEach(input => {
            if (input.id === "full-name" || input.id === "phone") {
                input.readOnly = !isEditing;
                input.classList.toggle("editable", isEditing);
            }
        });

        if (editBtn) editBtn.classList.toggle("hidden", isEditing);
        if (logoutBtn) logoutBtn.classList.toggle("hidden", isEditing);
        if (saveBtn) saveBtn.classList.toggle("hidden", !isEditing);
        if (cancelBtn) cancelBtn.classList.toggle("hidden", !isEditing);
    }

    function resetPasswordEditor() {
        [currentPassword, newPassword, confirmPassword].forEach(el => {
            if (el) el.value = "";
        });
        if (passwordEditBlock) passwordEditBlock.classList.add("hidden");
        if (passwordLockedRow) passwordLockedRow.classList.remove("hidden");
    }

    async function submitProfileUpdate(extraPayload = {}) {
        const fd = new FormData();
        fd.append("name", document.getElementById("full-name")?.value.trim() || "");
        fd.append("phone_number", document.getElementById("phone")?.value.trim() || "");
        fd.append("email", emailInput?.value.trim() || "");

        // Attach avatar if provided
        if (avatarInput && avatarInput.files && avatarInput.files[0]) {
            fd.append("avatar", avatarInput.files[0]);
        }

        Object.entries(extraPayload || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                fd.append(key, value);
            }
        });

        const response = await fetch("/profile", {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: fd
        });

        let result = {};
        try {
            result = await response.json();
        } catch (_) {
            // ignore parse errors
        }

        if (!response.ok) {
            let errorMsg = result.message || "Could not save changes.";
            if (result.errors) {
                const firstError = Object.values(result.errors).map(arr => arr[0]).join(" ");
                errorMsg += ` ${firstError}`;
            }
            showStatus(errorMsg, "error");
            return false;
        }

        const updated = result.user || {};
        if (updated.name) {
            const nameEl = document.querySelector(".profile-name");
            if (nameEl) nameEl.textContent = updated.name;
        }
        if (updated.email) {
            const emailEl = document.querySelector(".profile-email");
            if (emailEl) emailEl.textContent = updated.email;
        }
        if (updated.avatar_url && avatarPreview) {
            avatarPreview.src = updated.avatar_url;
        }

        showStatus(result.message || "Profile updated successfully.");
        return true;
    }

    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            handleLogout(csrfToken);
        });
    }

    if (editBtn) {
        editBtn.addEventListener("click", (e) => {
            e.preventDefault();
            storeInitialValues();
            toggleEditMode(true);
            const first = document.getElementById("full-name");
            if (first) first.focus();
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener("click", (e) => {
            e.preventDefault();
            submitProfileUpdate().then((ok) => {
                if (ok) {
                    toggleEditMode(false);
                    resetPasswordEditor();
                }
            });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", (e) => {
            e.preventDefault();
            Object.keys(initialValues).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = initialValues[id];
            });
            toggleEditMode(false);
            showStatus("Changes cancelled.", "error");
            resetPasswordEditor();
        });
    }

    /* -------------------------------
       AVATAR PREVIEW
    --------------------------------*/
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener("change", () => {
            const file = avatarInput.files[0];
            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
            }
        });
    }

    /* -------------------------------
       PASSWORD CHANGE
    --------------------------------*/
    if (changePasswordBtn && passwordEditBlock && passwordLockedRow) {
        changePasswordBtn.addEventListener("click", (e) => {
            e.preventDefault();
            passwordEditBlock.classList.remove("hidden");
            passwordLockedRow.classList.add("hidden");
        });
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener("click", (e) => {
            e.preventDefault();
            resetPasswordEditor();
        });
    }

    if (savePasswordBtn) {
        savePasswordBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const current = currentPassword?.value.trim() || "";
            const next = newPassword?.value.trim() || "";
            const confirm = confirmPassword?.value.trim() || "";

            if (!current || !next || !confirm) {
                showStatus("Please fill all password fields.", "error");
                return;
            }
            if (next !== confirm) {
                showStatus("New password and confirmation do not match.", "error");
                return;
            }

            submitProfileUpdate({
                current_password: current,
                password: next,
                password_confirmation: confirm
            }).then((ok) => {
                if (ok) {
                    resetPasswordEditor();
                }
            });
        });
    }

    /* -------------------------------
       NOTIFICATION TOGGLES
    --------------------------------*/
    async function updateNotificationPreference(field, value, target) {
        if (!field) return;
        try {
            const response = await fetch("/profile/notifications/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({ field, value })
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success !== true) {
                throw new Error(result.message || "Could not update notification preference.");
            }

            showStatus("Notification preference saved.");
        } catch (err) {
            if (target && Object.prototype.hasOwnProperty.call(target, "checked")) {
                target.checked = !target.checked;
            }
            if (target && Object.prototype.hasOwnProperty.call(target, "value") && target.dataset.previousValue) {
                target.value = target.dataset.previousValue;
            }
            showStatus(err.message || "Could not update notification preference.", "error");
        }
    }

    document.querySelectorAll(".notif-toggle").forEach(toggle => {
        toggle.addEventListener("change", () => {
            updateNotificationPreference(toggle.dataset.field, toggle.checked ? 1 : 0, toggle);
        });
    });

});

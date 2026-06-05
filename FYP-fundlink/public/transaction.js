// ----------------------------------------------------
// Mobile nav (unified header)
// ----------------------------------------------------
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

// ----------------------------------------------------
// Custom Alert (Console Only)
// ----------------------------------------------------
function showStatusMessage(message) {
    console.log("Action:", message);
}

document.addEventListener("DOMContentLoaded", function () {
    // ----------------------------------------------------
    // 1. HAMBURGER MENU
    // ----------------------------------------------------
    setupMobileNav();

    // ----------------------------------------------------
    // 2. ADD TRANSACTION MODAL
    // ----------------------------------------------------
    const openBtn = document.getElementById("openTransactionModalButton");
    const addModal = document.getElementById("addTransactionModal");
    const closeAddBtns = document.querySelectorAll("#addTransactionModal .modal-close");

    closeAddBtns.forEach(btn => {
        btn.addEventListener("click", () => addModal.classList.remove("open"));
    });

    window.addEventListener("click", (e) => {
        if (e.target === addModal) addModal.classList.remove("open");
    });

    // ----------------------------------------------------
    // 3. DROPDOWN MENU (...)
    // ----------------------------------------------------
    document.querySelectorAll(".menu-dots").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();

            const container = btn.closest(".dropdown-container");

            // Close other dropdowns
            document.querySelectorAll(".dropdown-container.show").forEach(dd => {
                if (dd !== container) dd.classList.remove("show");
            });

            container.classList.toggle("show");
        });
    });

    // Close dropdown on outside click
    document.addEventListener("click", () => {
        document.querySelectorAll(".dropdown-container.show")
            .forEach(dd => dd.classList.remove("show"));
    });

    // Prevent dropdown from closing when clicking inside menu
    document.querySelectorAll(".dropdown-content").forEach(menu => {
        menu.addEventListener("click", (e) => e.stopPropagation());
    });

    // ----------------------------------------------------
    // 4. EXPORT & FILTER buttons
    // ----------------------------------------------------
    const exportBtn = document.querySelector(".widget-header .btn-secondary");
    if (exportBtn) {
        exportBtn.addEventListener("click", () => {
            showStatusMessage("Export clicked");
        });
    }

    const filterBtn = document.querySelector(".filter-actions .btn-primary");
    const filterForm = document.getElementById("transactionFilterForm");
    const resetBtn = document.getElementById("resetFilters");
    if (filterBtn && filterForm) {
        filterBtn.addEventListener("click", (e) => {
            e.preventDefault();
            filterForm.submit();
        });
    }
    if (resetBtn && filterForm) {
        resetBtn.addEventListener("click", () => {
            const dateRange = filterForm.querySelector('#dateRange');
            const category = filterForm.querySelector('#category');
            const type = filterForm.querySelector('#type');
            const search = filterForm.querySelector('#searchDesc');
            if (dateRange) dateRange.value = 'This Month';
            if (category) category.value = 'all';
            if (type) type.value = 'All Types';
            if (search) search.value = '';
            filterForm.submit();
        });
    }

    // ----------------------------------------------------
    // 5. ADD / EDIT FORM SUBMIT
    // ----------------------------------------------------
    const form = document.getElementById("addTransactionForm");
    const statusBox = document.getElementById("addTransactionStatus");
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const setStatus = (msg, type = 'info') => {
        if (!statusBox) return;
        statusBox.textContent = msg;
        statusBox.className = `form-status ${type}`;
    };

    const modalTitle = document.querySelector("#addTransactionModal .modal-header h2");
    const submitBtn = form?.querySelector("button[type='submit']");

    const openCreateMode = () => {
        if (!form) return;
        form.dataset.mode = "create";
        form.dataset.id = "";
        modalTitle.textContent = "Add New Transaction";
        if (submitBtn) submitBtn.textContent = "Save Transaction";
        form.reset();
        setStatus("");
        const sharedSelect = document.getElementById("new_type");
        if (sharedSelect) sharedSelect.value = "0";
        addModal.classList.add("open");
    };

    if (openBtn && addModal) {
        openBtn.addEventListener("click", openCreateMode);
    }

    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const isEdit = form.dataset.mode === "edit" && form.dataset.id;
            const url = isEdit ? `/transaction/${form.dataset.id}` : "/transaction";

            const formData = new FormData();
            formData.append("description", document.getElementById("new_description").value.trim());
            formData.append("amount", document.getElementById("new_amount").value);
            formData.append("date", document.getElementById("new_date").value);
            formData.append("category_id", document.getElementById("new_category").value);
            formData.append("shared_account_id", document.getElementById("new_type").value);

            const receiptInput = document.getElementById("new_receipt_image");
            if (receiptInput?.files?.[0]) {
                formData.append("receipt_image", receiptInput.files[0]);
            }

            if (isEdit) {
                formData.append("_method", "PUT");
            }

            if (submitBtn) submitBtn.disabled = true;
            setStatus(isEdit ? "Updating transaction..." : "Saving transaction...", "info");

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    const message = errorData.message || errorData.error || "Unable to save transaction.";
                    setStatus(message, "error");
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }

                setStatus("Saved. Reloading...", "success");
                setTimeout(() => window.location.reload(), 350);
            } catch (err) {
                console.error(err);
                setStatus("Network error. Please try again.", "error");
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // ----------------------------------------------------
    // 6. VIEW / EDIT / DELETE
    // ----------------------------------------------------
    const viewModal = document.getElementById("viewTransactionModal");
    const closeViewBtns = document.querySelectorAll("#viewTransactionModal .modal-close");

    closeViewBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            viewModal.classList.remove("open");
        });
    });

    const handleView = (trigger) => {
        if (!trigger || !viewModal) return;
        document.getElementById("v-desc").textContent = trigger.dataset.description || "";
        document.getElementById("v-category").textContent = trigger.dataset.category || "";
        document.getElementById("v-account").textContent = trigger.dataset.account || "";
        document.getElementById("v-date").textContent = trigger.dataset.date || "";
        document.getElementById("v-amount").textContent = trigger.dataset.amount || "";
        const receiptUrl = trigger.dataset.receipt || "";
        const receiptText = document.getElementById("v-receipt-text");
        const receiptWrap = document.getElementById("v-receipt-wrap");
        const receiptImg = document.getElementById("v-receipt-img");
        const receiptLink = document.getElementById("v-receipt-link");
        const receiptDownload = document.getElementById("v-receipt-download");
        const receiptDownloadName = () => {
            const desc = (trigger.dataset.description || "image").trim();
            const date = (trigger.dataset.date || "").trim();
            const base = `${desc}${date ? "_" + date : ""}`.toLowerCase();
            return base.replace(/[^a-z0-9-_]+/g, "_").replace(/_+/g, "_").replace(/^_|_$/g, "");
        };
        if (receiptText) {
            receiptText.textContent = receiptUrl ? "Image attached" : "No image";
        }
        if (receiptWrap && receiptImg) {
            if (receiptUrl) {
                receiptImg.src = receiptUrl;
                if (receiptLink) receiptLink.href = receiptUrl;
                if (receiptDownload) {
                    receiptDownload.href = receiptUrl;
                    receiptDownload.download = receiptDownloadName() || "image";
                }
                receiptWrap.style.display = "block";
            } else {
                receiptImg.src = "";
                if (receiptLink) receiptLink.href = "#";
                if (receiptDownload) {
                    receiptDownload.href = "#";
                    receiptDownload.removeAttribute("download");
                }
                receiptWrap.style.display = "none";
            }
        }

        const tags = document.getElementById("v-tags");
        if (tags) {
            const accountType = trigger.dataset.accountType || "";
            const accountName = trigger.dataset.accountName || "";
            const label = accountType === "Shared" ? `Account: ${accountName}` : "Personal account";
            tags.textContent = label;
        }

        viewModal.classList.add("open");
    };

    const handleEdit = (trigger) => {
        if (!trigger || !form) return;
        form.dataset.mode = "edit";
        form.dataset.id = trigger.dataset.id;
        modalTitle.textContent = "Edit Transaction";
        if (submitBtn) submitBtn.textContent = "Update Transaction";
        setStatus("");

        document.getElementById("new_description").value = trigger.dataset.description || "";
        document.getElementById("new_amount").value = trigger.dataset.amountRaw || "";
        document.getElementById("new_date").value = trigger.dataset.dateIso || "";
        document.getElementById("new_category").value = trigger.dataset.categoryId || "";
        document.getElementById("new_type").value = trigger.dataset.sharedId || "0";
        const receiptInput = document.getElementById("new_receipt_image");
        if (receiptInput) receiptInput.value = "";

        addModal.classList.add("open");
    };

    const handleDelete = async (trigger) => {
        if (!trigger) return;
        const id = trigger.dataset.id;
        const desc = trigger.dataset.description || "this transaction";
        if (!confirm(`Delete "${desc}"? This cannot be undone.`)) return;

        try {
            const response = await fetch(`/transaction/${id}`, {
                method: "DELETE",
                headers: {
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                alert(err.message || err.error || "Failed to delete transaction.");
                return;
            }

            window.location.reload();
        } catch (err) {
            console.error(err);
            alert("Network error. Please try again.");
        }
    };

    document.querySelectorAll(".view-transaction").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            handleView(btn);
        });
    });

    document.querySelectorAll(".edit-transaction").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            handleEdit(btn);
        });
    });

    document.querySelectorAll(".delete-transaction").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            handleDelete(btn);
        });
    });
});

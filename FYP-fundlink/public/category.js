// --- Global Data Array (Start empty, data will be fetched from API) ---
let categoryData = []; 

// --- Global Elements and Helper Functions ---
const categoryModal = document.getElementById('addCategoryModal');
const iconModal = document.getElementById('iconPickerModal');
const categoryForm = document.getElementById('addCategoryForm');
const viewCategoryModal = document.getElementById('viewCategoryModal');
const viewCategoryTitle = document.getElementById('view-category-title');
const viewCategoryContent = document.getElementById('view-category-content');

// Helper function to show a custom status message (replaces alert())
function showStatusMessage(message, duration = 3000) {
    const statusMessage = document.getElementById('statusMessage'); 
    if (!statusMessage) {
        console.error("Element with ID 'statusMessage' not found.");
        return;
    }
    statusMessage.textContent = message;
    statusMessage.style.opacity = '1';
    statusMessage.style.pointerEvents = 'auto';
    setTimeout(() => {
        statusMessage.style.opacity = '0';
        statusMessage.style.pointerEvents = 'none';
    }, duration);
}

// Update the donut label to the current month so it auto-rotates each month
function updateMonthLabel() {
    const monthLabel = document.getElementById('monthLabel');
    if (!monthLabel) return;
    monthLabel.textContent = 'Total Expenses:';
}

// Helper function to generate a random color for the new category card
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

async function deleteCategory(id, name) {
    // ... [Omitted confirmation prompt code] ...

    try {
        const response = await fetch(`/categories/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        // Check for a successful status (204 No Content is standard for successful DELETE)
        if (response.ok || response.status === 204) {
            
            // 1. Remove the category from the local data array (categoryData)
            categoryData = categoryData.filter(cat => {
                // Use a robust check to handle both string and numeric IDs
                // Ensure the category ID in the array is not equal to the ID we just deleted
                return String(cat.id).endsWith(String(id)) === false;
            });

            // 2. Re-render the UI immediately to reflect the change
            renderCategories();
            initExpenseChart(); // Re-initialize the chart to update totals/slices
            
            showStatusMessage(`Category '${name}' successfully deleted.`, 3000);

        } else {
            // Handle non-200, non-204 error statuses
            const errorData = await response.json().catch(() => ({ message: `HTTP error! status: ${response.status}` }));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

    } catch (error) {
        console.error("Error deleting category:", error);
        showStatusMessage(`Failed to delete category: ${error.message}`, 5000);
    }
}

// --- Render Categories (Safety checks added) ---
function renderCategories() {
    const listContainer = document.getElementById('categoryList');
    listContainer.innerHTML = '';
    
    // Check if categoryData is defined and has elements before proceeding
    if (!categoryData || categoryData.length === 0) {
        // Display a message to the user when no categories are found
        listContainer.innerHTML = '<p class="text-center text-gray-500 mt-5">No categories found. Click "Add Category" to start!</p>';
        return; 
    }
    
    categoryData.forEach(cat => {
        // Ensure properties are treated as numbers (necessary after fetching from DB)
        const budget = parseFloat(cat.budget) || 0;
        const spent = parseFloat(cat.spent) || 0;

        const currentPercent = budget > 0 ? ((spent / budget) * 100).toFixed(0) : 0;
        const card = document.createElement('div');
        card.className = `category-card card-${cat.id}`;
        
        card.innerHTML = `
            <div class="card-top">
                <div class="category-icon-name">
                    <div class="category-icon" style="color: ${cat.color};"><i class="${cat.icon}"></i></div>
                    <span class="category-name">${cat.name}</span>
                </div>
                <i class="fas fa-ellipsis-h menu-icon" data-category-id="${cat.id}"></i>
                <div class="dropdown-menu" id="menu-${cat.id}">
                    <a class="dropdown-item" data-action="view" data-id="${cat.id}" data-name="${cat.name}"><i class="fas fa-eye" style="color: var(--primary-blue);"></i> View</a>
                    <a class="dropdown-item" data-action="edit" data-id="${cat.id}" data-name="${cat.name}"><i class="fas fa-edit" style="color: var(--status-warning);"></i> Edit</a>
                    <a class="dropdown-item" data-action="delete" data-id="${cat.id}" data-name="${cat.name}"><i class="fas fa-trash-alt" style="color: var(--status-danger);"></i> Delete</a>
                </div>
            </div>
            <div class="budget-info">
                <span>Budget : RM${budget.toFixed(2)}</span>
                <span>Spent : RM${spent.toFixed(2)} (${currentPercent}%)</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: ${currentPercent > 100 ? 100 : currentPercent}%; background-color: ${currentPercent >= 80 ? '#d9534f' : cat.color};"></div>
            </div>
        `;
        listContainer.appendChild(card);
    });
    
    attachMenuListeners();
}


// --- Menu Listeners (FIXED: Combined logic to ensure dropdown button and menu actions work) ---
function attachMenuListeners() {
    // 1. Listener for the ellipsis icon (to open/toggle the menu)
    document.querySelectorAll('.menu-icon').forEach(icon => {
        icon.addEventListener('click', (e) => {
            e.stopPropagation(); 
            const categoryId = icon.getAttribute('data-category-id');
            const targetMenu = document.getElementById(`menu-${categoryId}`);
            
            // Close any other open menus
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                if (menu.id !== targetMenu.id) {
                    menu.classList.remove('active');
                }
            });
            
            // Toggle the target menu visibility
            targetMenu.classList.toggle('active');
        });
    });

    // 2. Listener for clicking anywhere else (to close all menus)
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    });

    // 3. Menu Item Click Handler (View, Edit, Delete logic)
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation(); 
            
            const action = item.getAttribute('data-action');
            const name = item.getAttribute('data-name');
            
            // --- TEMPORARY FIX: SAFELY EXTRACT NUMERIC ID FROM STRING ---
            const rawId = item.getAttribute('data-id');
            let id;
            if (rawId && rawId.includes('-')) {
                // Assumes the number is the last part after the hyphen (e.g., 1761480106)
                const parts = rawId.split('-');
                id = parseInt(parts[parts.length - 1]);
            } else {
                // Normal parsing for pure numeric IDs
                id = parseInt(rawId); 
            }
            // --- END TEMPORARY FIX ---
            
            // Close the menu immediately
            item.closest('.dropdown-menu').classList.remove('active');

            // CRITICAL GUARD CLAUSE: Check for NaN
            if (isNaN(id)) {
                console.error(`Invalid Category ID received for action ${action}. Raw ID was: ${rawId}`);
                showStatusMessage(`Error: Cannot perform ${action}. ID is corrupted: ${rawId}`, 5000);
                return; 
            }

            // Route the valid action
            if (action === 'view') {
                const rawId = item.getAttribute('data-id');
                openCategoryView(rawId);
            } else if (action === 'edit') {
                openEditModal(id); 
            } else if (action === 'delete') {
                deleteCategory(id, name);
            } else {
                 showStatusMessage(`${action.charAt(0).toUpperCase() + action.slice(1)} action triggered for: ${name}`);
            }
        });
    });
}


// --- Chart Initialization (Fix 1 implemented) ---
function initExpenseChart() {
    updateMonthLabel();
    // 👈 FIX: Add Guard Clause to prevent crash on empty data
    if (categoryData.length === 0) {
        document.getElementById('totalExpenses').textContent = `RM0.00`;
        if (typeof Chart !== 'undefined' && window.expenseChartInstance) {
            window.expenseChartInstance.destroy();
        }
        return; 
    }

    // Ensure Chart variable is accessible
    if (typeof Chart === 'undefined') {
          console.error("Chart.js is not defined. Ensure chart.min.js is loaded BEFORE category.js.");
          return;
    }

    const ctx = document.getElementById('expenseChart').getContext('2d');
    
    const labels = categoryData.map(c => c.name);
    const data = categoryData.map(c => parseFloat(c.spent) || 0); 
    const colors = categoryData.map(c => c.color);
    const total = data.reduce((sum, amount) => sum + amount, 0);

    document.getElementById('totalExpenses').textContent = `RM${total.toFixed(2)}`;

    if (window.expenseChartInstance) {
        window.expenseChartInstance.destroy();
    }

    window.expenseChartInstance = new Chart(ctx, { 
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                hoverOffset: 8,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', 
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: RM${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}


// =======================================================
// === MODAL MANAGEMENT & ICON PICKER FUNCTIONS ===
// =======================================================

// --- Icon Data ---
const iconList = [
    { name: "Car", code: "fas fa-car" },
    { name: "Food", code: "fas fa-hamburger" },
    { name: "Home", code: "fas fa-home" },
    { name: "Travel", code: "fas fa-plane" },
    { name: "Phone", code: "fas fa-mobile-alt" },
    { name: "Shopping", code: "fas fa-shopping-bag" },
    { name: "Health", code: "fas fa-heartbeat" },
    { name: "Gas", code: "fas fa-gas-pump" },
    // Add more icons here if needed
];

// 1. Modal Functions
function openModal() {
    if (categoryModal) {
        categoryModal.style.display = 'flex';
        document.getElementById('addCategoryForm').reset();
        document.getElementById('selectedIconDisplay').innerHTML = '<i class="fas fa-question-circle"></i>';
        document.getElementById('categoryIconCode').value = '';

        // Reset title and button for 'Add New Category'
        document.querySelector('.modal-title-custom').textContent = 'Add New Category';
        document.querySelector('.btn-primary-custom').textContent = 'Add Category';
        categoryForm.removeAttribute('data-editing-id'); // Remove the editing ID
    }
}

function closeModal() {
    if (categoryModal) {
        categoryModal.style.display = 'none';
    }
}

// 2. Icon Picker Functions
function openIconPicker() {
    if (iconModal) {
        iconModal.style.display = 'flex';
        // Always populate the grid when opening
        populateIconGrid(); 
    }
}

function closeIconPicker() {
    if (iconModal) {
        iconModal.style.display = 'none';
    }
}

function populateIconGrid(icons = iconList) {
    const grid = document.getElementById('iconGridContainer');
    if (!grid) return;
    grid.innerHTML = '';
    
    icons.forEach(icon => {
        const div = document.createElement('div');
        div.className = 'icon-option';
        div.innerHTML = `<i class="${icon.code}"></i>`;
        div.onclick = () => selectIcon(icon.code);
        grid.appendChild(div);
    });
}

function selectIcon(iconCode) {
    document.getElementById('categoryIconCode').value = iconCode;
    document.getElementById('selectedIconDisplay').innerHTML = `<i class="${iconCode}"></i>`;
    closeIconPicker();
}

function filterIcons() {
    const searchValue = document.getElementById('iconSearchInput').value.toLowerCase();
    const filteredIcons = iconList.filter(icon => icon.name.toLowerCase().includes(searchValue));
    populateIconGrid(filteredIcons);
}

// --- Form Submission Handler (POST / PATCH for Add/Edit - FIXED: Single Definition) ---
async function submitNewCategory(event) {
    event.preventDefault(); 
    
    // 1. Determine if we are ADDING or EDITING
    const editingId = categoryForm.getAttribute('data-editing-id');
    const isEditing = !!editingId; 

    // --- Data Collection ---
    const name = document.getElementById('categoryName').value.trim();
    const budget = Math.max(0, parseFloat(document.getElementById('categoryBudget').value)) || 0; 
    const description = document.getElementById('categoryDescription').value.trim();
    const icon = document.getElementById('categoryIconCode').value.trim();
    
    if (!name || !icon) {
        showStatusMessage("Please enter a Category Name and select an Icon.", 4000);
        return; 
    }
    
    const categoryDataToSend = {
        name: name,
        icon: icon,
        budget: budget,
        description: description
    };

    // 2. Configure API Call (URL and Method)
    let url = '/categories';
    let method = 'POST';
    
    if (isEditing) {
        url = `/categories/${editingId}`;
        method = 'PATCH'; 
    }

    // --- API Call ---
    try {
        const response = await fetch(url, { 
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(categoryDataToSend),
        });

        if (!response.ok) {
            const errorData = await response.json(); 
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const savedCategory = await response.json();
        
        // 3. Update local data array (categoryData)
        if (isEditing) {
            const index = categoryData.findIndex(cat => cat.id == editingId);
            if (index !== -1) {
                // Ensure existing data (like 'spent' or 'color' if not returned) is kept
                categoryData[index] = { ...categoryData[index], ...savedCategory };
            }
        } else {
            categoryData.push(savedCategory);
        }

        // 4. Update UI
        renderCategories();
        initExpenseChart();
        
        closeModal(); 
        const statusMsg = isEditing ? `'${name}' updated successfully!` : `'${name}' added successfully!`;
        showStatusMessage(`Category ${statusMsg}`, 3000);

    } catch (error) {
        console.error(`Error ${isEditing ? 'updating' : 'saving'} category:`, error);
        showStatusMessage(`Failed to ${isEditing ? 'update' : 'add'} category: ${error.message}`, 5000); 
    }
}


// --- Initial Data Loader (GET) ---
async function fetchCategories() {
    try {
        const response = await fetch('/categories/data');
        
        if (!response.ok) {
            throw new Error(`Failed to load categories. Status: ${response.status}`);
        }
        
        categoryData = await response.json(); 
        
        renderCategories();
        initExpenseChart();

    } catch (error) {
        console.error("Error fetching initial data:", error);
        showStatusMessage("Could not connect to the backend server to load data. (Check server/DB)", 6000);
    }
}


// --- Page Initialization (UPDATED to call fetchCategories) ---
document.addEventListener("DOMContentLoaded", () => {
    setupMobileNav();

    fetchCategories(); 
    populateIconGrid(); 
    
    const addBtn = document.querySelector('.btn-add-category');
    if (addBtn) addBtn.addEventListener('click', openModal);
    
    if (categoryForm) {
        categoryForm.addEventListener('submit', submitNewCategory);
    }

    window.onclick = function(event) {
        if (event.target === categoryModal) {
            closeModal();
        } else if (event.target === iconModal) {
            closeIconPicker();
        } else if (event.target === viewCategoryModal) {
            closeViewCategoryModal();
        }
    };
});

// Unified mobile nav helper (shared header)
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

// --- Open Modal for Editing (FIXED: Robust Search) ---
function openEditModal(id) {
    // Convert the ID to a string to handle both numeric and string IDs gracefully during search.
    const searchId = String(id); 
    
    // 1. Find the existing category data using a robust method.
    // It finds the category if the ID matches exactly (for new numeric IDs) 
    // OR if the ID ends with the number part (for old string IDs).
    const categoryToEdit = categoryData.find(cat => {
        // Find if the whole cat.id (string or number) ends with the number portion we sent.
        return String(cat.id).endsWith(searchId);
    });

    if (!categoryToEdit) {
        showStatusMessage("Error: Category data not found for editing.", 4000);
        // CRITICAL: Ensure the modal doesn't open if data is missing
        return; 
    }

    // 2. Pre-fill the form fields
    document.getElementById('categoryName').value = categoryToEdit.name;
    // Use the stored budget, spent is calculated
    document.getElementById('categoryBudget').value = parseFloat(categoryToEdit.budget).toFixed(2);
    document.getElementById('categoryDescription').value = categoryToEdit.description || '';
    
    // Icon
    const iconCode = categoryToEdit.icon;
    document.getElementById('categoryIconCode').value = iconCode;
    document.getElementById('selectedIconDisplay').innerHTML = `<i class="${iconCode}"></i>`;

    // 3. Change Modal Title and Button
    document.querySelector('.modal-title-custom').textContent = `Edit Category: ${categoryToEdit.name}`;
    const submitButton = document.querySelector('.btn-primary-custom');
    submitButton.textContent = 'Save Changes';
    
    // 4. Temporarily attach the **ORIGINAL** category ID (string or number) to the form 
    // This original ID is what the backend needs for a reliable lookup.
    categoryForm.setAttribute('data-editing-id', categoryToEdit.id);
    
    // 5. Open the modal
    categoryModal.style.display = 'flex'; 
    
    showStatusMessage(`Ready to edit: ${categoryToEdit.name}`, 2000);
}

// --- View Modal: show category details + transactions ---
function closeViewCategoryModal() {
    if (viewCategoryModal) viewCategoryModal.style.display = 'none';
}

async function openCategoryView(rawId) {
    if (!viewCategoryModal || !viewCategoryTitle || !viewCategoryContent) return;

    viewCategoryTitle.textContent = 'Loading...';
    viewCategoryContent.innerHTML = '<p class="info-text">Fetching category details...</p>';
    viewCategoryModal.style.display = 'flex';

    try {
        const response = await fetch(`/categories/${encodeURIComponent(rawId)}/transactions`);
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to load category.');
        }

        const cat = data.category || {};
        const txs = data.transactions || [];

        const titleIcon = cat.icon ? `<i class="${cat.icon}"></i>` : '';
        const titleName = cat.name || 'Category';

        const budget = Number(cat.budget || 0);
        const spent = Number(cat.spent || 0);
        const remaining = Number(cat.remaining || 0);
        const percent = Number(cat.percent || 0);

        const detailRows = `
            <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${cat.name || ''}</span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${cat.description || 'No description'}</span></div>
            <div class="detail-row"><span class="detail-label">Budget</span><span class="detail-value">RM${budget.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Spent</span><span class="detail-value">RM${spent.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Remaining</span><span class="detail-value">RM${remaining.toFixed(2)}</span></div>
            <div class="detail-row"><span class="detail-label">Progress</span><span class="detail-value">${percent}%</span></div>
        `;

        const txList = txs.length === 0
            ? '<p class="info-text" style="margin-top:10px;">No transactions in this category yet.</p>'
            : txs.map(tx => `
                <div class="tx-row">
                    <div class="tx-main">
                        <div class="tx-title">${tx.description || 'No description'}</div>
                        <div class="tx-meta">${tx.category || 'Uncategorized'} • ${tx.account || 'Personal'} • ${tx.date || ''} ${tx.time || ''}</div>
                    </div>
                    <div class="tx-amount">RM${(tx.amount || 0).toFixed(2)}</div>
                </div>
            `).join('');

        viewCategoryTitle.innerHTML = `${titleIcon ? '<span class="modal-icon">' + titleIcon + '</span>' : ''} ${titleName} Details`;
        viewCategoryContent.innerHTML = `
            ${detailRows}
            <hr style="margin: 12px 0;">
            <div class="tx-list">${txList}</div>
        `;
    } catch (err) {
        viewCategoryTitle.textContent = 'Category Details';
        viewCategoryContent.innerHTML = '<p class="info-text">Could not load category details. Please try again.</p>';
        console.error(err);
    }
}

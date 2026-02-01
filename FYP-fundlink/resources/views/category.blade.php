<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fundlink - Categories</title>
    
    <link rel="stylesheet" href="{{ asset('category.css') }}"> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <img src="logo.png" alt="link Logo" class="header-logo"> 
                Fundlink
            </div>

            <nav class="main-nav" id="mainNav">
                <a href="{{ url('/dashboard') }}" class="nav-item">Dashboard</a>
                <a href="{{ url('/transaction') }}" class="nav-item">Transaction</a>
                <a href="{{ url('/categories') }}" class="nav-item active">Categories</a>
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

    <main class="categories-main">
        <div class="main-title">Categories</div>

        <div class="card-container">
            <div class="card-header">
                <div class="card-header-info">
                    <h2>Expense Categories</h2>
                    <p>Manage your expense categories and budgets</p>
                </div>
                <button class="btn-add-category"><i class="fas fa-plus"></i> Add Category</button>
            </div>

            <div class="content-grid">
                <div class="category-list" id="categoryList">
                    </div>

                <div class="chart-widget">
                    <div class="total-expenses">
                        <span class="label" id="monthLabel">Total Expenses:</span>
                        <span class="total-amount" id="totalExpenses">RM0</span>
                    </div>
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <div id="addCategoryModal" class="modal-backdrop-custom" style="display:none;">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h5 class="modal-title-custom">Add New Category</h5>
            <button type="button" class="close-button-custom" onclick="closeModal()">
                &times;
            </button>
        </div>
        
        <div class="modal-body-custom">
            <form id="addCategoryForm">
                
                <div class="form-group-custom">
                    <label for="categoryName">Category Name</label>
                    <input type="text" class="form-control-custom" id="categoryName" placeholder="e.g., Groceries, Entertainment" required>
                </div>

                <div class="form-group-custom">
                    <label for="categoryIcon">Category Icon</label>
                    <div class="icon-selector-container">
                        <span id="selectedIconDisplay" class="current-icon-preview">
                            <i class="fas fa-question-circle"></i>
                        </span>
                        <button type="button" class="btn-icon-picker" onclick="openIconPicker()">
                            Select Icon
                        </button>
                        <input type="hidden" id="categoryIconCode" name="categoryIconCode" required>
                    </div>
                </div>
                
                <div class="form-group-custom">
                    <label for="categoryBudget">Monthly Budget (RM)</label>
                    <input type="number" class="form-control-custom" id="categoryBudget" placeholder="0.00" value="0.00" step="0.01">
                </div>

                <div class="form-group-custom">
                    <label for="categoryDescription">Description (Optional)</label>
                    <textarea class="form-control-custom" id="categoryDescription" rows="2" placeholder="Brief explanation of this category"></textarea>
                </div>
            
            </form>
        </div>
        
        <div class="modal-footer-custom">
            <button type="button" class="btn-cancel-custom" onclick="closeModal()">Cancel</button>
            
            <button type="submit" class="btn-primary-custom" form="addCategoryForm">Add Category</button>
        </div>
    </div>
</div>


<div id="iconPickerModal" class="modal-backdrop-custom" style="display:none;">
    <div class="modal-content-custom icon-picker-content">
        <div class="modal-header-custom">
            <h5 class="modal-title-custom">Select an Icon</h5>
            <button type="button" class="close-button-custom" onclick="closeIconPicker()">
                &times;
            </button>
        </div>
        
        <div class="modal-search-bar">
            <input type="text" id="iconSearchInput" class="form-control-custom" 
                   placeholder="Search icons (e.g., car, food, phone)..." 
                   onkeyup="filterIcons()">
        </div>
        
        <div class="modal-body-custom icon-grid" id="iconGridContainer">
            </div>
    </div>
</div>

{{-- View Category Modal --}}
<div id="viewCategoryModal" class="modal-backdrop-custom" style="display:none;">
    <div class="modal-content-custom view-category-content">
        <div class="modal-header-custom">
            <h5 id="view-category-title" class="modal-title-custom">Category Details</h5>
            <button type="button" class="close-button-custom" onclick="closeViewCategoryModal()">
                &times;
            </button>
        </div>
        <div id="view-category-content" class="category-details-view">
            <p class="info-text">Loading...</p>
        </div>
        <div class="modal-footer-custom">
            <button type="button" class="btn-primary-custom" onclick="closeViewCategoryModal()">Close</button>
        </div>
    </div>
</div>
    
    <div id="statusMessage"></div>

    <script src="{{ asset('category.js') }}"></script>
    <script src="{{ asset('auth-guard.js') }}"></script>
</body>
</html>

// --- Mock Data ---
const expenseData = {
  labels: ['Food & Beverage', 'Transportation', 'Utility', 'Shopping', 'Mobile Prepaid'],
  amounts: [325, 75, 50, 35, 15],
  colors: ['#d9534f', '#f0ad4e', '#3B82F6', '#5cb85c', '#6366F1'],
  total: 500,
  currency: 'RM',
  month: 'March'
};

// --- Helper: Status message (instead of alert) ---
function showStatusMessage(message, duration = 3000) {
  const statusMessage = document.getElementById('statusMessage');
  statusMessage.textContent = message;
  statusMessage.style.opacity = '1';
  setTimeout(() => {
    statusMessage.style.opacity = '0';
  }, duration);
}

// --- Chart Initialization ---
function initExpenseChart() {
  const ctx = document.getElementById('expenseChart').getContext('2d');
  document.getElementById('totalExpenses').textContent =
    expenseData.currency + expenseData.total;

  const total = expenseData.amounts.reduce((sum, amount) => sum + amount, 0);

  const breakdownList = document.getElementById('breakdownList');
  if (breakdownList) {
    breakdownList.innerHTML = '';

    expenseData.labels.forEach((label, index) => {
      const amount = expenseData.amounts[index];
      const percentage = ((amount / total) * 100).toFixed(1);
      const color = expenseData.colors[index];

      const item = document.createElement('div');
      item.className = 'category-item';
      item.innerHTML = `
        <div class="category-details">
          <div class="color-dot" style="background-color: ${color};"></div>
          <span>${label}</span>
        </div>
        <div style="font-weight: 500; color: var(--dark-text);">
          ${expenseData.currency}${amount}
          <span style="color: #888;">(${percentage}%)</span>
        </div>
      `;
      breakdownList.appendChild(item);
    });
  }

  // Chart configuration
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: expenseData.labels,
      datasets: [
        {
          data: expenseData.amounts,
          backgroundColor: expenseData.colors,
          hoverOffset: 4,
          borderWidth: 0
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      aspectRatio: 1,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || '';
              const value = context.parsed;
              const totalSum = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / totalSum) * 100).toFixed(1);
              return `${label}: ${expenseData.currency}${value} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
}

// --- Dropdown & Copy Interactivity ---
document.addEventListener('DOMContentLoaded', () => {
  const copyBtn = document.getElementById('copyLinkBtn');
  const actionMenuToggle = document.getElementById('actionMenuToggle');
  const actionDropdown = document.getElementById('actionDropdown');

  if (copyBtn) {
    copyBtn.addEventListener('click', () => {
      const linkToCopy = 'http://fundlink.app/share/household123';
      const dummyInput = document.createElement('input');
      document.body.appendChild(dummyInput);
      dummyInput.value = linkToCopy;
      dummyInput.select();
      document.execCommand('copy');
      document.body.removeChild(dummyInput);
      showStatusMessage('Shared link copied to clipboard!');
    });
  }

  if (actionMenuToggle && actionDropdown) {
    actionMenuToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      actionDropdown.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
      if (!actionMenuToggle.contains(e.target) && actionDropdown.classList.contains('active')) {
        actionDropdown.classList.remove('active');
      }
    });

    const dropdownItems = actionDropdown.querySelectorAll('.dropdown-item');
    dropdownItems.forEach((item) => {
      item.addEventListener('click', () => {
        const action = item.textContent.trim();
        showStatusMessage(`Action triggered: ${action} on 'Expenses Household'`);
        actionDropdown.classList.remove('active');
      });
    });
  }

  // Initialize the chart after page loads
  initExpenseChart();
});

// --- Hamburger Menu Toggle ---
function toggleMenu() {
  const menu = document.getElementById('mobileMenu');
  const burger = document.querySelector('.hamburger');
  if (!menu) return;

  menu.classList.toggle('active');
  burger.classList.toggle('active');
}





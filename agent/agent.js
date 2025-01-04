// Check agent authentication
document.addEventListener('DOMContentLoaded', function() {
    const agent = JSON.parse(localStorage.getItem('agent'));
    if (!agent) {
        window.location.href = '../index.html';
        return;
    }

    // Set agent name and balance
    document.getElementById('agentName').textContent = agent.username;
    document.getElementById('agentBalance').textContent = agent.balance.toLocaleString();

    // Load initial data
    loadDashboardStats();
    loadRecentActivities();
});

// Navigation
document.querySelectorAll('.menu li').forEach(item => {
    item.addEventListener('click', function() {
        // Remove active class from all items
        document.querySelectorAll('.menu li').forEach(i => i.classList.remove('active'));
        // Add active class to clicked item
        this.classList.add('active');
        
        // Hide all pages
        document.querySelectorAll('.page-content').forEach(page => page.classList.add('hidden'));
        // Show selected page
        document.getElementById(`${this.dataset.page}-page`).classList.remove('hidden');
        
        // Load page specific data
        loadPageData(this.dataset.page);
    });
});

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const agent = JSON.parse(localStorage.getItem('agent'));
        const response = await fetch(`../api/agent/stats.php?agent_id=${agent.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById('totalUsers').textContent = data.stats.total_users;
            document.getElementById('totalBets').textContent = `${data.stats.total_bets.toLocaleString()} Ks`;
            document.getElementById('totalCommission').textContent = `${data.stats.total_commission.toLocaleString()} Ks`;
            document.getElementById('totalWinners').textContent = data.stats.total_winners;
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Load recent activities
async function loadRecentActivities() {
    try {
        const agent = JSON.parse(localStorage.getItem('agent'));
        const response = await fetch(`../api/agent/activities.php?agent_id=${agent.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const activitiesList = document.getElementById('recentActivities');
            activitiesList.innerHTML = data.activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-type">${activity.type}</div>
                        <div class="activity-details">${activity.details}</div>
                    </div>
                    <div class="activity-time">${formatTime(activity.timestamp)}</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading recent activities:', error);
    }
}

// Quick Actions
document.getElementById('addUserBtn').addEventListener('click', function() {
    document.getElementById('addUserModal').style.display = 'block';
});

document.getElementById('addBalanceBtn').addEventListener('click', function() {
    document.getElementById('balanceModal').style.display = 'block';
    document.querySelector('#balanceForm select').value = 'deposit';
});

document.getElementById('withdrawBtn').addEventListener('click', function() {
    document.getElementById('balanceModal').style.display = 'block';
    document.querySelector('#balanceForm select').value = 'withdraw';
});

document.getElementById('checkResultsBtn').addEventListener('click', async function() {
    try {
        const response = await fetch('../api/results/latest.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            // Create a formatted message
            let message = 'နောက်ဆုံးထီပေါက်စဉ်များ:\n\n';
            data.results.forEach(result => {
                message += `${result.lottery_type}: ${result.winning_numbers}\n`;
                message += `ရက်စွဲ: ${formatDate(result.draw_date)}\n\n`;
            });
            alert(message);
        }
    } catch (error) {
        console.error('Error checking results:', error);
        alert('ထီပေါက်စဉ်များ ကြည့်ရှုရန် မအောင်မြင်ပါ။');
    }
});

// Add new user
document.getElementById('addUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const agent = JSON.parse(localStorage.getItem('agent'));
    const formData = new FormData(this);
    formData.append('agent_id', agent.id);
    
    try {
        const response = await fetch('../api/agent/users/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('သုံးစွဲသူအသစ် ထည့်သွင်းခြင်း အောင်မြင်ပါသည်။');
            document.getElementById('addUserModal').style.display = 'none';
            this.reset();
            loadDashboardStats();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error creating user:', error);
        alert('သုံးစွဲသူအသစ် ထည့်သွင်းခြင်း မအောင်မြင်ပါ။');
    }
});

// Handle balance operations
document.getElementById('balanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const agent = JSON.parse(localStorage.getItem('agent'));
    const formData = new FormData(this);
    formData.append('agent_id', agent.id);
    
    try {
        const response = await fetch('../api/agent/transactions/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ငွေကြေးလုပ်ဆောင်ချက် အောင်မြင်ပါသည်။');
            document.getElementById('balanceModal').style.display = 'none';
            this.reset();
            // Update agent balance
            agent.balance = data.new_balance;
            localStorage.setItem('agent', JSON.stringify(agent));
            document.getElementById('agentBalance').textContent = agent.balance.toLocaleString();
            loadDashboardStats();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error processing transaction:', error);
        alert('ငွေကြေးလုပ်ဆောင်ချက် မအောင်မြင်ပါ။');
    }
});

// Load page specific data
function loadPageData(page) {
    switch(page) {
        case 'users':
            loadUsers();
            break;
        case 'transactions':
            loadTransactions();
            break;
        case 'commission':
            loadCommission();
            break;
        case 'reports':
            loadReports();
            break;
    }
}

// Load users list
async function loadUsers() {
    try {
        const agent = JSON.parse(localStorage.getItem('agent'));
        const response = await fetch(`../api/agent/users.php?agent_id=${agent.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const usersList = document.getElementById('usersList');
            usersList.innerHTML = data.users.map(user => `
                <div class="list-item">
                    <div class="user-info">
                        <div class="user-name">${user.name}</div>
                        <div class="user-phone">${user.phone_number}</div>
                    </div>
                    <div class="user-balance">${user.balance.toLocaleString()} Ks</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Load commission data
async function loadCommission() {
    try {
        const agent = JSON.parse(localStorage.getItem('agent'));
        const response = await fetch(`../api/agent/commission.php?agent_id=${agent.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById('todayCommission').textContent = `${data.commission.today.toLocaleString()} Ks`;
            document.getElementById('monthCommission').textContent = `${data.commission.month.toLocaleString()} Ks`;
            document.getElementById('totalCommissionAll').textContent = `${data.commission.total.toLocaleString()} Ks`;
            
            const historyList = document.getElementById('commissionHistory');
            historyList.innerHTML = data.history.map(item => `
                <div class="history-item">
                    <div class="history-date">${formatDate(item.date)}</div>
                    <div class="history-amount">${item.amount.toLocaleString()} Ks</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading commission data:', error);
    }
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Logout
document.querySelector('.logout-btn').addEventListener('click', function() {
    localStorage.removeItem('agent');
    window.location.href = '../index.html';
});

// Helper Functions
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('my-MM', options);
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

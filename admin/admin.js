// Check admin authentication
document.addEventListener('DOMContentLoaded', function() {
    const admin = JSON.parse(localStorage.getItem('admin'));
    if (!admin) {
        window.location.href = '../index.html';
        return;
    }

    // Set admin name
    document.querySelector('.admin-name').textContent = admin.username;

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
        const response = await fetch('../api/admin/stats.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById('totalUsers').textContent = data.stats.total_users;
            document.getElementById('totalAgents').textContent = data.stats.total_agents;
            document.getElementById('todayBets').textContent = `${data.stats.today_bets.toLocaleString()} Ks`;
            document.getElementById('todayProfit').textContent = `${data.stats.today_profit.toLocaleString()} Ks`;
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Load recent activities
async function loadRecentActivities() {
    try {
        const response = await fetch('../api/admin/activities.php');
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

// Load page specific data
function loadPageData(page) {
    switch(page) {
        case 'agents':
            loadAgents();
            break;
        case 'users':
            loadUsers();
            break;
        case 'transactions':
            loadTransactions();
            break;
        case 'lottery':
            loadLotteryRecords();
            break;
        case 'results':
            loadResults();
            break;
        case 'accounts':
            loadAccounts();
            break;
        case 'reports':
            loadReports();
            break;
    }
}

// Agents Management
async function loadAgents() {
    try {
        const response = await fetch('../api/admin/agents.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const agentsList = document.getElementById('agentsList');
            agentsList.innerHTML = data.agents.map(agent => `
                <div class="list-item">
                    <div class="agent-info">
                        <div class="agent-name">${agent.username}</div>
                        <div class="agent-balance">${agent.balance.toLocaleString()} Ks</div>
                    </div>
                    <div class="agent-actions">
                        <button onclick="editAgent(${agent.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleAgentStatus(${agent.id})">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading agents:', error);
    }
}

// Add new agent
document.getElementById('addAgentBtn').addEventListener('click', function() {
    document.getElementById('addAgentModal').style.display = 'block';
});

document.getElementById('addAgentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../api/admin/agents/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ကိုယ်စားလှယ်အသစ် ထည့်သွင်းခြင်း အောင်မြင်ပါသည်။');
            document.getElementById('addAgentModal').style.display = 'none';
            loadAgents();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error creating agent:', error);
        alert('ကိုယ်စားလှယ်အသစ် ထည့်သွင်းခြင်း မအောင်မြင်ပါ။');
    }
});

// Results Management
async function loadResults() {
    try {
        const response = await fetch('../api/admin/results.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const resultsList = document.getElementById('resultsList');
            resultsList.innerHTML = data.results.map(result => `
                <div class="list-item">
                    <div class="result-info">
                        <div class="result-type">${result.lottery_type}</div>
                        <div class="result-number">${result.winning_numbers}</div>
                        <div class="result-date">${formatDate(result.draw_date)}</div>
                    </div>
                    <div class="result-actions">
                        <button onclick="editResult(${result.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteResult(${result.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading results:', error);
    }
}

// Add new result
document.getElementById('addResultBtn').addEventListener('click', function() {
    document.getElementById('addResultModal').style.display = 'block';
});

document.getElementById('addResultForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../api/admin/results/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ထီပေါက်စဉ်အသစ် ထည့်သွင်းခြင်း အောင်မြင်ပါသည်။');
            document.getElementById('addResultModal').style.display = 'none';
            loadResults();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error creating result:', error);
        alert('ထီပေါက်စဉ်အသစ် ထည့်သွင်းခြင်း မအောင်မြင်ပါ။');
    }
});

// Bank Accounts Management
async function loadAccounts() {
    try {
        const response = await fetch('../api/admin/accounts.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const accountsList = document.getElementById('accountsList');
            accountsList.innerHTML = data.accounts.map(account => `
                <div class="list-item">
                    <div class="account-info">
                        <div class="account-bank">${account.bank_name}</div>
                        <div class="account-number">${account.account_number}</div>
                        <div class="account-holder">${account.account_holder}</div>
                    </div>
                    <div class="account-actions">
                        <button onclick="editAccount(${account.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleAccountStatus(${account.id})">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading accounts:', error);
    }
}

// Add new account
document.getElementById('addAccountBtn').addEventListener('click', function() {
    document.getElementById('addAccountModal').style.display = 'block';
});

document.getElementById('addAccountForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../api/admin/accounts/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ဘဏ်အကောင့်အသစ် ထည့်သွင်းခြင်း အောင်မြင်ပါသည်။');
            document.getElementById('addAccountModal').style.display = 'none';
            loadAccounts();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error creating account:', error);
        alert('ဘဏ်အကောင့်အသစ် ထည့်သွင်းခြင်း မအောင်မြင်ပါ။');
    }
});

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
    localStorage.removeItem('admin');
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

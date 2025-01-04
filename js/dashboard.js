// Check if user is logged in
document.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = '../index.html';
        return;
    }

    // Update user information
    document.getElementById('userName').textContent = user.name;
    document.getElementById('userPhone').textContent = user.phone_number;
    document.getElementById('userBalance').textContent = user.balance.toLocaleString();

    // Load recent results
    loadRecentResults();
    // Load play history
    loadPlayHistory();
});

// Logout functionality
document.querySelector('.logout-btn').addEventListener('click', function() {
    localStorage.removeItem('user');
    window.location.href = '../index.html';
});

// Deposit modal
const depositModal = document.getElementById('depositModal');
const depositBtn = document.querySelector('.deposit-btn');

depositBtn.addEventListener('click', function() {
    depositModal.style.display = 'block';
    loadDepositAccounts();
});

// Play modal
const playModal = document.getElementById('playModal');
const playBtns = document.querySelectorAll('.lottery-card button');

playBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const lotteryType = this.closest('.lottery-card').dataset.type;
        openPlayModal(lotteryType);
    });
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target === depositModal) {
        depositModal.style.display = 'none';
    }
    if (event.target === playModal) {
        playModal.style.display = 'none';
    }
});

// Load recent results
async function loadRecentResults() {
    try {
        const response = await fetch('../api/results/recent.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const resultsGrid = document.querySelector('.results-grid');
            resultsGrid.innerHTML = data.results.map(result => `
                <div class="result-card">
                    <h3>${result.lottery_type}</h3>
                    <p class="winning-number">${result.winning_numbers}</p>
                    <p class="date">${formatDate(result.draw_date)}</p>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading results:', error);
    }
}

// Load play history
async function loadPlayHistory() {
    const user = JSON.parse(localStorage.getItem('user'));
    try {
        const response = await fetch(`../api/playbets/history.php?user_id=${user.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const playsList = document.querySelector('.plays-list');
            playsList.innerHTML = data.plays.map(play => `
                <div class="play-card">
                    <div class="play-info">
                        <span class="lottery-type">${play.lottery_type}</span>
                        <span class="number">${play.number_selected}</span>
                        <span class="amount">${play.bet_amount.toLocaleString()} Ks</span>
                    </div>
                    <div class="play-status ${play.result}">${play.result}</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading play history:', error);
    }
}

// Load deposit accounts
async function loadDepositAccounts() {
    try {
        const response = await fetch('../api/accounts/deposit.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const accountsDiv = document.querySelector('.deposit-accounts');
            accountsDiv.innerHTML = data.accounts.map(account => `
                <div class="account-card">
                    <h3>${account.bank_name}</h3>
                    <p>${account.account_number}</p>
                    <p>${account.account_holder}</p>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading deposit accounts:', error);
    }
}

// Open play modal
function openPlayModal(lotteryType) {
    const modal = document.getElementById('playModal');
    modal.querySelector('h2').textContent = `${lotteryType} ထီထိုးရန်`;
    modal.style.display = 'block';
}

// Handle deposit form submission
document.getElementById('depositForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const amount = this.querySelector('input[type="number"]').value;
    const reference = this.querySelector('input[type="text"]').value;
    
    try {
        const response = await fetch('../api/transactions/deposit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: JSON.parse(localStorage.getItem('user')).id,
                amount: amount,
                reference_number: reference
            })
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ငွေဖြည့်ခြင်း အောင်မြင်ပါသည်။');
            depositModal.style.display = 'none';
            // Update user balance
            const user = JSON.parse(localStorage.getItem('user'));
            user.balance += parseFloat(amount);
            localStorage.setItem('user', JSON.stringify(user));
            document.getElementById('userBalance').textContent = user.balance.toLocaleString();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error processing deposit:', error);
        alert('ငွေဖြည့်ခြင်း မအောင်မြင်ပါ။');
    }
});

// Handle play form submission
document.getElementById('playForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const number = this.querySelector('input[type="text"]').value;
    const amount = this.querySelector('input[type="number"]').value;
    const lotteryType = document.querySelector('#playModal h2').textContent.split(' ')[0];
    
    try {
        const response = await fetch('../api/playbets/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: JSON.parse(localStorage.getItem('user')).id,
                lottery_type: lotteryType,
                number_selected: number,
                bet_amount: amount
            })
        });
        
        const data = await response.json();
        if (data.status === 'success') {
            alert('ထီထိုးခြင်း အောင်မြင်ပါသည်။');
            playModal.style.display = 'none';
            // Update user balance and play history
            const user = JSON.parse(localStorage.getItem('user'));
            user.balance -= parseFloat(amount);
            localStorage.setItem('user', JSON.stringify(user));
            document.getElementById('userBalance').textContent = user.balance.toLocaleString();
            loadPlayHistory();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error processing play:', error);
        alert('ထီထိုးခြင်း မအောင်မြင်ပါ။');
    }
});

// Helper function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('my-MM', options);
}

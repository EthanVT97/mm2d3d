// Initialize Supabase client
const SUPABASE_URL = 'https://jaubdheyosmukdxvctbq.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImphdWJkaGV5b3NtdWtkeHZjdGJxIiwicm9sZSI6ImFub24iLCJpYXQiOjE2NjU5ODEyNjksImV4cCI6MjA1MTU1NzI2OX0.0jN_29eZTDqWaWlkIuFnyHTiSGOfv_5Ie2jnsT9J6FA';
const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// Get base path for GitHub Pages
const BASE_PATH = '/mm2d3d';

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
    } else {
        navbar.style.backgroundColor = 'white';
        navbar.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
    }
});

// Check if user is already logged in
async function checkAuthStatus() {
    const user = localStorage.getItem('user');
    if (!user) {
        window.location.href = `${BASE_PATH}/auth/`;
        return null;
    }
    
    try {
        // Verify user still exists and is active
        const { data, error } = await supabase
            .from('users')
            .select('*')
            .eq('id', JSON.parse(user).id)
            .eq('status', 'active')
            .single();

        if (error || !data) {
            localStorage.removeItem('user');
            window.location.href = `${BASE_PATH}/auth/`;
            return null;
        }

        return data;
    } catch (error) {
        console.error('Auth error:', error);
        localStorage.removeItem('user');
        window.location.href = `${BASE_PATH}/auth/`;
        return null;
    }
}

// Logout function
async function logout() {
    try {
        localStorage.removeItem('user');
        window.location.href = `${BASE_PATH}/auth/`;
    } catch (error) {
        console.error('Error logging out:', error.message);
    }
}

// Data fetching functions
async function fetchUserData() {
    const user = localStorage.getItem('user');
    if (!user) return null;

    try {
        const { data, error } = await supabase
            .from('users')
            .select('*')
            .eq('id', JSON.parse(user).id)
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error fetching user data:', error.message);
        return null;
    }
}

// Initialize dashboard
async function initializeDashboard() {
    const userData = await checkAuthStatus();
    if (!userData) return;

    // Update UI with user data
    const nameElement = document.querySelector('.user-name');
    const balanceElement = document.querySelector('.user-balance');
    
    if (nameElement) {
        nameElement.textContent = userData.name;
    }
    if (balanceElement) {
        balanceElement.textContent = `Balance: ${userData.balance.toFixed(2)}`;
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Initialize dashboard if we're on the dashboard page
    if (window.location.pathname.includes(`${BASE_PATH}/dashboard`)) {
        initializeDashboard();
    }

    // Attach logout handler
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }

    // Check auth status
    checkAuthStatus();
});

// Navigation handlers
document.querySelector('.login-btn')?.addEventListener('click', function() {
    window.location.href = `${BASE_PATH}/auth/`;
});

document.querySelector('.register-btn')?.addEventListener('click', function() {
    window.location.href = `${BASE_PATH}/auth/`;
});

document.querySelector('.cta-button')?.addEventListener('click', function() {
    const user = localStorage.getItem('user');
    if (user) {
        window.location.href = `${BASE_PATH}/dashboard/`;
    } else {
        window.location.href = `${BASE_PATH}/auth/`;
    }
});

// Button click handlers
document.querySelector('.login-btn').addEventListener('click', function() {
    window.location.href = `${BASE_PATH}/auth/`;
});

document.querySelector('.register-btn').addEventListener('click', function() {
    window.location.href = `${BASE_PATH}/auth/`;
    // Add a small delay to ensure the page loads before switching tabs
    setTimeout(() => {
        const registerTab = document.querySelector('.tab:last-child');
        if (registerTab) {
            registerTab.click();
        }
    }, 100);
});

document.querySelector('.cta-button').addEventListener('click', function() {
    const user = checkAuthStatus();
    if (user) {
        window.location.href = `${BASE_PATH}/agent/`;
    } else {
        window.location.href = `${BASE_PATH}/auth/`;
    }
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
        }
    });
}, observerOptions);

// Observe elements for animation
document.querySelectorAll('.feature-card, .step, .testimonial-card').forEach(element => {
    observer.observe(element);
});

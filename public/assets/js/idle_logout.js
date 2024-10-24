// idle_logout.js

const IDLE_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
let idleTimer;
let isLoggingOut = false;
let logoutInProgress = false;

function resetIdleTimer() {
    if (!isLoggingOut) {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(autoLogout, IDLE_TIMEOUT);
    }
}

function autoLogout() {
    if (logoutInProgress) return;
    logoutInProgress = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch('/logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `auto_logout=true&csrf_token=${csrfToken || ''}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                isLoggingOut = true;
                window.location.href = '/login.php';
            }
        })
        .catch(error => {
            console.error('Auto-logout error:', error);
            logoutInProgress = false;
        });
}

// Only attach event listeners if we're not in a logout process
if (!isLoggingOut) {
    document.addEventListener('mousemove', resetIdleTimer);
    document.addEventListener('keypress', resetIdleTimer);
    resetIdleTimer();
}

// Handle page unload
window.addEventListener('beforeunload', (e) => {
    if (!isLoggingOut && !logoutInProgress) {
        clearTimeout(idleTimer);
    }
});
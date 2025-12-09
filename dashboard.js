// Dashboard JavaScript for dynamic updates

document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard every 5 minutes
    setInterval(function() {
        updateLastUpdateTime();
    }, 60000); // Update time every minute
    
    // Initial time update
    updateLastUpdateTime();
    
    // Add smooth animations for stat cards
    animateStatCards();
});

/**
 * Update the last update time display
 */
function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    const timeElement = document.getElementById('lastUpdateTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

/**
 * Animate stat cards on load
 */
function animateStatCards() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

/**
 * Format large numbers with K, M suffixes
 */
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

/**
 * Refresh dashboard data via AJAX (optional enhancement)
 */
function refreshDashboardData() {
    // This would make an AJAX call to fetch updated stats
    // For now, we'll just reload the page
    // In a production environment, you'd want to:
    // 1. Create an API endpoint (e.g., dashboard_api.php)
    // 2. Fetch data using fetch() or XMLHttpRequest
    // 3. Update DOM elements without page reload
    
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn) {
        refreshBtn.textContent = '🔄 Refreshing...';
        refreshBtn.disabled = true;
    }
    
    // Simulate API call (replace with actual API call)
    setTimeout(() => {
        location.reload();
    }, 500);
}

/**
 * Add loading state to elements
 */
function setLoadingState(elementId, isLoading) {
    const element = document.getElementById(elementId);
    if (element) {
        if (isLoading) {
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';
        } else {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        }
    }
}

/**
 * Update stat value with animation
 */
function updateStatValue(elementId, newValue, isCurrency = false) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseFloat(element.textContent.replace(/[^0-9.]/g, '')) || 0;
    const targetValue = parseFloat(newValue) || 0;
    
    // Animate the number change
    let startValue = currentValue;
    const duration = 1000; // 1 second
    const startTime = Date.now();
    
    function animate() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const current = startValue + (targetValue - startValue) * easeOutQuart;
        
        if (isCurrency) {
            element.textContent = '$' + current.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            element.textContent = Math.round(current).toLocaleString('en-US');
        }
        
        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            if (isCurrency) {
                element.textContent = '$' + targetValue.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else {
                element.textContent = targetValue.toLocaleString('en-US');
            }
        }
    }
    
    animate();
}

// Export functions for use in other scripts
window.dashboardUtils = {
    formatNumber,
    updateStatValue,
    refreshDashboardData,
    setLoadingState
};


/**
 * Sidebar Navigation JavaScript
 * 
 * Handles sidebar toggle, responsive overlay behavior, and keyboard navigation
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        mobileBreakpoint: 768,
        transitionDuration: 250,
        storageKey: 'sidebar-state',
        defaultState: 'expanded' // 'expanded' or 'collapsed'
    };
    
    // State
    let isCollapsed = false;
    let isMobile = window.innerWidth <= CONFIG.mobileBreakpoint;
    let isOpen = false;
    
    // DOM Elements
    let sidebar = null;
    let sidebarToggle = null;
    let mobileToggle = null;
    let overlay = null;
    let mainContent = null;
    let topBar = null;
    
    /**
     * Initialize sidebar functionality
     */
    function init() {
        // Get DOM elements
        sidebar = document.querySelector('.sidebar');
        sidebarToggle = document.querySelector('.sidebar-toggle');
        mobileToggle = document.querySelector('.mobile-menu-toggle');
        overlay = document.querySelector('.sidebar-overlay');
        mainContent = document.querySelector('.main-content');
        topBar = document.querySelector('.top-bar');
        
        // Check if sidebar exists
        if (!sidebar) {
            console.warn('Sidebar element not found');
            return;
        }
        
        // Remove no-js class (progressive enhancement)
        document.body.classList.remove('no-js');
        document.body.classList.add('js');
        
        // Check if mobile
        checkMobile();
        
        // Load saved state (desktop only)
        if (!isMobile) {
            loadState();
        }
        
        // Setup event listeners
        setupEventListeners();
        
        // Setup tooltips for collapsed state
        setupTooltips();
        
        // Handle window resize
        window.addEventListener('resize', handleResize);
        
        // Handle Escape key for mobile overlay
        document.addEventListener('keydown', handleEscape);
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Sidebar toggle button
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', handleToggle);
            sidebarToggle.addEventListener('keydown', handleToggleKeydown);
        }
        
        // Mobile menu toggle
        if (mobileToggle) {
            mobileToggle.addEventListener('click', handleMobileToggle);
            mobileToggle.addEventListener('keydown', handleToggleKeydown);
        }
        
        // Overlay click (close sidebar on mobile)
        if (overlay) {
            overlay.addEventListener('click', handleOverlayClick);
        }
        
        // Navigation links (close sidebar on mobile when clicked)
        const navLinks = sidebar.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', handleNavLinkClick);
        });
    }
    
    /**
     * Setup tooltips for collapsed state
     */
    function setupTooltips() {
        const navLinks = sidebar.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => {
            const label = link.querySelector('.sidebar-nav-label');
            if (label) {
                const tooltipText = label.textContent.trim();
                if (tooltipText) {
                    link.setAttribute('data-tooltip', tooltipText);
                }
            }
        });
    }
    
    /**
     * Handle sidebar toggle (desktop)
     */
    function handleToggle() {
        if (isMobile) {
            return; // Use mobile toggle on mobile
        }
        
        isCollapsed = !isCollapsed;
        updateSidebarState();
        saveState();
    }
    
    /**
     * Handle mobile menu toggle
     */
    function handleMobileToggle() {
        isOpen = !isOpen;
        updateMobileState();
    }
    
    /**
     * Handle overlay click (close sidebar on mobile)
     */
    function handleOverlayClick() {
        if (isMobile && isOpen) {
            isOpen = false;
            updateMobileState();
        }
    }
    
    /**
     * Handle navigation link click (close sidebar on mobile)
     */
    function handleNavLinkClick() {
        if (isMobile && isOpen) {
            // Small delay to allow navigation
            setTimeout(() => {
                isOpen = false;
                updateMobileState();
            }, 100);
        }
    }
    
    /**
     * Handle keyboard events for toggle buttons
     */
    function handleToggleKeydown(e) {
        // Enter or Space to toggle
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (isMobile) {
                handleMobileToggle();
            } else {
                handleToggle();
            }
        }
    }
    
    /**
     * Handle Escape key (close sidebar on mobile)
     */
    function handleEscape(e) {
        if (e.key === 'Escape' && isMobile && isOpen) {
            isOpen = false;
            updateMobileState();
            
            // Return focus to mobile toggle
            if (mobileToggle) {
                mobileToggle.focus();
            }
        }
    }
    
    /**
     * Handle window resize
     */
    function handleResize() {
        const wasMobile = isMobile;
        checkMobile();
        
        // If switching between mobile and desktop
        if (wasMobile !== isMobile) {
            if (isMobile) {
                // Switching to mobile: close sidebar
                isOpen = false;
                isCollapsed = false;
                updateMobileState();
            } else {
                // Switching to desktop: load saved state
                loadState();
            }
        }
    }
    
    /**
     * Check if mobile view
     */
    function checkMobile() {
        isMobile = window.innerWidth <= CONFIG.mobileBreakpoint;
    }
    
    /**
     * Update sidebar state (desktop)
     */
    function updateSidebarState() {
        if (!sidebar || !sidebarToggle) return;
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            sidebarToggle.setAttribute('aria-expanded', 'false');
        } else {
            sidebar.classList.remove('collapsed');
            sidebarToggle.setAttribute('aria-expanded', 'true');
        }
        
        // Update main content margin
        if (mainContent) {
            if (isCollapsed) {
                mainContent.classList.add('sidebar-collapsed');
            } else {
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
        
        // Update top bar position
        if (topBar) {
            if (isCollapsed) {
                topBar.classList.add('sidebar-collapsed');
            } else {
                topBar.classList.remove('sidebar-collapsed');
            }
        }
    }
    
    /**
     * Update mobile state
     */
    function updateMobileState() {
        if (!sidebar || !mobileToggle || !overlay) return;
        
        if (isOpen) {
            sidebar.classList.add('open');
            mobileToggle.setAttribute('aria-expanded', 'true');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
        } else {
            sidebar.classList.remove('open');
            mobileToggle.setAttribute('aria-expanded', 'false');
            overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore body scroll
        }
    }
    
    /**
     * Load saved state from localStorage
     */
    function loadState() {
        try {
            const savedState = localStorage.getItem(CONFIG.storageKey);
            if (savedState === 'collapsed') {
                isCollapsed = true;
            } else if (savedState === 'expanded') {
                isCollapsed = false;
            } else {
                // Use default state
                isCollapsed = CONFIG.defaultState === 'collapsed';
            }
            updateSidebarState();
        } catch (e) {
            console.warn('Failed to load sidebar state:', e);
        }
    }
    
    /**
     * Save state to localStorage
     */
    function saveState() {
        try {
            const state = isCollapsed ? 'collapsed' : 'expanded';
            localStorage.setItem(CONFIG.storageKey, state);
        } catch (e) {
            console.warn('Failed to save sidebar state:', e);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();


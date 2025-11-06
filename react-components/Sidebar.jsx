/**
 * Sidebar Component
 * 
 * A production-ready, accessible, retractable sidebar menu component
 * Built with React hooks and Tailwind CSS
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';

/**
 * Custom hook for focus trap
 * Traps focus within the sidebar when overlay is open
 */
function useFocusTrap(isActive, containerRef) {
    const previousActiveElement = useRef(null);

    useEffect(() => {
        if (!isActive || !containerRef.current) return;

        // Store the element that had focus before opening
        previousActiveElement.current = document.activeElement;

        const container = containerRef.current;
        const focusableElements = container.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        // Focus the first element
        if (firstElement) {
            firstElement.focus();
        }

        const handleTabKey = (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement?.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement?.focus();
                }
            }
        };

        container.addEventListener('keydown', handleTabKey);

        return () => {
            container.removeEventListener('keydown', handleTabKey);
            // Return focus to the element that opened the sidebar
            previousActiveElement.current?.focus();
        };
    }, [isActive, containerRef]);
}

/**
 * Sidebar Component
 * 
 * @param {Object} props
 * @param {Array<{id: string, label: string, icon: ReactNode, href: string}>} props.navItems - Navigation items
 * @param {boolean} [props.defaultExpanded=true] - Default expanded state (desktop only)
 * @param {Function} [props.onToggle] - Callback when sidebar toggles (expanded: boolean) => void
 * @param {number} [props.overlayBreakpoint=768] - Breakpoint for mobile overlay mode
 * @param {string} [props.className] - Additional CSS classes
 * @param {boolean} [props.darkMode=false] - Dark mode variant
 */
function Sidebar({
    navItems = [],
    defaultExpanded = true,
    onToggle,
    overlayBreakpoint = 768,
    className = '',
    darkMode = false
}) {
    const [isExpanded, setIsExpanded] = useState(defaultExpanded);
    const [isMobile, setIsMobile] = useState(false);
    const [isOverlayOpen, setIsOverlayOpen] = useState(false);
    const sidebarRef = useRef(null);
    const toggleButtonRef = useRef(null);

    // Check if mobile view
    useEffect(() => {
        const checkMobile = () => {
            setIsMobile(window.innerWidth <= overlayBreakpoint);
            // On mobile, sidebar starts closed
            if (window.innerWidth <= overlayBreakpoint) {
                setIsOverlayOpen(false);
            }
        };

        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, [overlayBreakpoint]);

    // Focus trap when overlay is open
    useFocusTrap(isMobile && isOverlayOpen, sidebarRef);

    // Handle Escape key to close overlay
    useEffect(() => {
        if (!isMobile || !isOverlayOpen) return;

        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                handleCloseOverlay();
            }
        };

        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [isMobile, isOverlayOpen]);

    // Prevent body scroll when overlay is open
    useEffect(() => {
        if (isMobile && isOverlayOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isMobile, isOverlayOpen]);

    // Handle toggle
    const handleToggle = useCallback(() => {
        if (isMobile) {
            setIsOverlayOpen(prev => !prev);
        } else {
            const newExpanded = !isExpanded;
            setIsExpanded(newExpanded);
            onToggle?.(newExpanded);
        }
    }, [isMobile, isExpanded, onToggle]);

    // Handle close overlay
    const handleCloseOverlay = useCallback(() => {
        setIsOverlayOpen(false);
        // Return focus to toggle button
        setTimeout(() => {
            toggleButtonRef.current?.focus();
        }, 100);
    }, []);

    // Handle backdrop click
    const handleBackdropClick = useCallback((e) => {
        if (e.target === e.currentTarget) {
            handleCloseOverlay();
        }
    }, [handleCloseOverlay]);

    // Determine sidebar state
    const isSidebarVisible = isMobile ? isOverlayOpen : true;
    const isSidebarExpanded = isMobile ? true : isExpanded;

    // Theme classes
    const themeClasses = darkMode
        ? 'bg-gray-900 text-white border-gray-800'
        : 'bg-white text-gray-900 border-gray-200';

    const hoverClasses = darkMode
        ? 'hover:bg-gray-800'
        : 'hover:bg-gray-100';

    const activeClasses = darkMode
        ? 'bg-gray-800'
        : 'bg-gray-100';

    return (
        <>
            {/* Mobile Toggle Button (only visible on mobile) */}
            {isMobile && (
                <button
                    ref={toggleButtonRef}
                    onClick={handleToggle}
                    aria-expanded={isOverlayOpen}
                    aria-controls="sidebar-navigation"
                    aria-label="Toggle navigation menu"
                    className={`
                        fixed top-4 left-4 z-[1001]
                        p-2 rounded-lg
                        ${darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'}
                        shadow-lg
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                        transition-all duration-200
                    `}
                >
                    <svg
                        className="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        {isOverlayOpen ? (
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        ) : (
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        )}
                    </svg>
                </button>
            )}

            {/* Overlay Backdrop (mobile only) */}
            {isMobile && isOverlayOpen && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 z-[999] transition-opacity duration-200"
                    onClick={handleBackdropClick}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar */}
            <aside
                ref={sidebarRef}
                id="sidebar-navigation"
                role="navigation"
                aria-label="Main navigation"
                className={`
                    fixed top-0 left-0 h-full z-[1000]
                    ${themeClasses}
                    border-r
                    flex flex-col
                    transition-all duration-250 ease-in-out
                    ${isMobile
                        ? `w-60 ${isOverlayOpen ? 'translate-x-0' : '-translate-x-full'}`
                        : `${isSidebarExpanded ? 'w-60' : 'w-16'}`
                    }
                    ${className}
                `}
            >
                {/* Sidebar Header */}
                <div className="flex items-center justify-between p-4 border-b border-inherit min-h-[64px]">
                    <a
                        href="/"
                        className="flex items-center gap-3 text-lg font-semibold no-underline transition-opacity duration-200"
                        style={{
                            opacity: isSidebarExpanded ? 1 : 0,
                            width: isSidebarExpanded ? 'auto' : 0,
                            overflow: 'hidden'
                        }}
                    >
                        <span className="flex-shrink-0 w-8 h-8 bg-blue-500 rounded flex items-center justify-center text-white font-bold">
                            A
                        </span>
                        <span className="whitespace-nowrap">App Name</span>
                    </a>

                    {/* Desktop Toggle Button */}
                    {!isMobile && (
                        <button
                            onClick={handleToggle}
                            aria-expanded={isExpanded}
                            aria-controls="sidebar-navigation"
                            aria-label={isExpanded ? 'Collapse sidebar' : 'Expand sidebar'}
                            className={`
                                flex-shrink-0 p-2 rounded-lg
                                ${hoverClasses}
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                transition-all duration-200
                            `}
                        >
                            <svg
                                className="w-5 h-5 transition-transform duration-200"
                                style={{
                                    transform: isExpanded ? 'rotate(0deg)' : 'rotate(180deg)'
                                }}
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                                />
                            </svg>
                        </button>
                    )}

                    {/* Mobile Close Button */}
                    {isMobile && (
                        <button
                            onClick={handleCloseOverlay}
                            aria-label="Close navigation menu"
                            className={`
                                flex-shrink-0 p-2 rounded-lg
                                ${hoverClasses}
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                transition-all duration-200
                            `}
                        >
                            <svg
                                className="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    )}
                </div>

                {/* Navigation Items */}
                <nav className="flex-1 overflow-y-auto overflow-x-hidden py-2">
                    <ul className="list-none m-0 p-0">
                        {navItems.map((item) => (
                            <li key={item.id} className="m-0">
                                <a
                                    href={item.href}
                                    className={`
                                        flex items-center gap-3 px-4 py-3
                                        ${hoverClasses}
                                    `}
                                    title={!isSidebarExpanded ? item.label : undefined}
                                    style={{
                                        justifyContent: isSidebarExpanded ? 'flex-start' : 'center'
                                    }}
                                    onFocus={(e) => {
                                        // Ensure sidebar is expanded on focus (desktop)
                                        if (!isMobile && !isExpanded) {
                                            setIsExpanded(true);
                                            onToggle?.(true);
                                        }
                                    }}
                                >
                                    <span
                                        className="flex-shrink-0 w-5 h-5 flex items-center justify-center"
                                        aria-hidden="true"
                                    >
                                        {item.icon}
                                    </span>
                                    <span
                                        className="transition-opacity duration-200 whitespace-nowrap"
                                        style={{
                                            opacity: isSidebarExpanded ? 1 : 0,
                                            width: isSidebarExpanded ? 'auto' : 0,
                                            overflow: 'hidden'
                                        }}
                                    >
                                        {item.label}
                                    </span>
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
            </aside>
        </>
    );
}

export default Sidebar;


/**
 * Example App Component
 * 
 * Demonstrates usage of the Sidebar component
 */

import React, { useState } from 'react';
import Sidebar from './Sidebar';

// Example icon components (you can replace these with your own icons)
const HomeIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
);

const ProductsIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
    </svg>
);

const CartIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
);

const OrdersIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
    </svg>
);

const SettingsIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
);

const LogoutIcon = () => (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
    </svg>
);

function App() {
    const [sidebarExpanded, setSidebarExpanded] = useState(true);

    // Navigation items configuration
    const navItems = [
        {
            id: 'home',
            label: 'Home',
            icon: <HomeIcon />,
            href: '/'
        },
        {
            id: 'products',
            label: 'All Products',
            icon: <ProductsIcon />,
            href: '/products'
        },
        {
            id: 'cart',
            label: 'Shopping Cart',
            icon: <CartIcon />,
            href: '/cart'
        },
        {
            id: 'orders',
            label: 'My Orders',
            icon: <OrdersIcon />,
            href: '/orders'
        },
        {
            id: 'settings',
            label: 'Settings',
            icon: <SettingsIcon />,
            href: '/settings'
        },
        {
            id: 'logout',
            label: 'Logout',
            icon: <LogoutIcon />,
            href: '/logout'
        }
    ];

    const handleSidebarToggle = (expanded) => {
        setSidebarExpanded(expanded);
        console.log('Sidebar toggled:', expanded ? 'expanded' : 'collapsed');
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Sidebar Component */}
            <Sidebar
                navItems={navItems}
                defaultExpanded={true}
                onToggle={handleSidebarToggle}
                overlayBreakpoint={768}
                darkMode={false}
            />

            {/* Main Content Area */}
            <main
                className="min-h-screen transition-all duration-250 ease-in-out"
                style={{
                    marginLeft: sidebarExpanded ? '240px' : '64px'
                }}
            >
                <div className="container mx-auto px-4 py-8">
                    <div className="max-w-4xl mx-auto">
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">
                            Example Page with Sidebar
                        </h1>
                        
                        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 className="text-xl font-semibold text-gray-800 mb-4">
                                Sidebar Integration
                            </h2>
                            <p className="text-gray-600 mb-4">
                                This page demonstrates the Sidebar component in action.
                            </p>
                            <div className="space-y-2">
                                <p className="text-sm text-gray-500">
                                    <strong>Desktop/Tablet:</strong> The sidebar is permanently visible on the left.
                                    Click the toggle button to expand/collapse it.
                                </p>
                                <p className="text-sm text-gray-500">
                                    <strong>Mobile:</strong> The sidebar becomes an overlay. Use the hamburger
                                    button in the top-left to open it.
                                </p>
                                <p className="text-sm text-gray-500">
                                    <strong>Current State:</strong> Sidebar is{' '}
                                    <span className="font-semibold">
                                        {sidebarExpanded ? 'expanded' : 'collapsed'}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 className="text-xl font-semibold text-gray-800 mb-4">
                                Features
                            </h2>
                            <ul className="list-disc list-inside space-y-2 text-gray-600">
                                <li>Responsive design (desktop sidebar, mobile overlay)</li>
                                <li>Keyboard accessible (Tab, Shift+Tab, Escape, Enter)</li>
                                <li>Focus trapping when overlay is open</li>
                                <li>Smooth animations and transitions</li>
                                <li>ARIA attributes for screen readers</li>
                                <li>Tooltips on collapsed items</li>
                                <li>Themeable with dark/light mode support</li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-lg shadow-md p-6">
                            <h2 className="text-xl font-semibold text-gray-800 mb-4">
                                Usage Example
                            </h2>
                            <pre className="bg-gray-100 rounded p-4 overflow-x-auto text-sm">
{`import Sidebar from './Sidebar';

const navItems = [
    {
        id: 'home',
        label: 'Home',
        icon: <HomeIcon />,
        href: '/'
    },
    // ... more items
];

<Sidebar
    navItems={navItems}
    defaultExpanded={true}
    onToggle={(expanded) => console.log(expanded)}
    overlayBreakpoint={768}
    darkMode={false}
/>`}
                            </pre>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}

export default App;


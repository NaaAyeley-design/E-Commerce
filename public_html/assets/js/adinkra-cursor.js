// ============================================
// ADINKRA CURSOR WITH TRAIL EFFECT
// Blue Glowing Gradient Theme
// ============================================

// Configuration
const ADINKRA_SYMBOLS = ['✦', '◈', '❖', '✤', '◆', '❋', '✶', '※'];
const TRAIL_DELAY = 30; // milliseconds between trail dots
const CURSOR_COLORS = {
  primary: '#FF9A56',    // Amber
  secondary: '#B7410E',  // Rust
  dark: '#6B4423'        // Earth Brown
};

// Initialize cursor elements
function initAdinkraCursor() {
  // Disable ONLY on actual mobile devices (not touch-capable desktops)
  // Many desktop browsers report touch capabilities, so we ONLY check user agent
  // This is more reliable than checking touch capabilities
  const mobileUserAgents = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
  const isMobileDevice = mobileUserAgents.test(navigator.userAgent);
  
  // Additional check: if screen is very small AND has touch, it's likely mobile
  const isSmallTouchScreen = window.innerWidth <= 480 && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
  
  if (isMobileDevice || isSmallTouchScreen) {
    console.log('Cursor disabled: Mobile/small touch device detected', {
      userAgent: navigator.userAgent,
      screenWidth: window.innerWidth,
      isMobileUA: isMobileDevice,
      isSmallTouch: isSmallTouchScreen,
      hasTouch: 'ontouchstart' in window,
      maxTouchPoints: navigator.maxTouchPoints
    });
    return; // Exit function - don't initialize cursor on mobile devices
  }
  
  console.log('✓ Touch detection passed - cursor will initialize', {
    userAgent: navigator.userAgent.substring(0, 50) + '...',
    screenWidth: window.innerWidth,
    hasTouch: 'ontouchstart' in window,
    maxTouchPoints: navigator.maxTouchPoints
  });

  console.log('=== INITIALIZING ADINKRA CURSOR ===');
  console.log('Document body exists:', !!document.body);
  console.log('Document ready state:', document.readyState);
  
  // Check if styles already exist
  let style = document.getElementById('adinkra-cursor-styles');
  if (!style) {
    style = document.createElement('style');
    style.id = 'adinkra-cursor-styles';
    document.head.appendChild(style);
    console.log('✓ Cursor styles element created');
  } else {
    console.log('Cursor styles already exist, reusing...');
  }
  
  style.textContent = `
    body {
      cursor: none !important;
    }
    
    * {
      cursor: none !important;
    }

    .custom-cursor {
      position: fixed !important;
      width: 20px !important;
      height: 20px !important;
      border-radius: 50% !important;
      background: ${CURSOR_COLORS.primary} !important;
      pointer-events: none !important;
      z-index: 99999 !important;
      transition: all 0.15s ease-out !important;
      transform: translate(-50%, -50%) !important;
      box-shadow: 0 0 20px ${CURSOR_COLORS.primary}, 0 0 40px rgba(255, 154, 86, 0.5) !important;
      opacity: 1 !important;
      left: 50% !important;
      top: 50% !important;
      display: block !important;
      visibility: visible !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .cursor-ring {
      position: fixed !important;
      width: 40px !important;
      height: 40px !important;
      border: 2px solid ${CURSOR_COLORS.secondary} !important;
      border-radius: 50% !important;
      pointer-events: none !important;
      z-index: 99998 !important;
      transition: all 0.2s ease-out !important;
      transform: translate(-50%, -50%) !important;
      box-shadow: 0 0 15px rgba(183, 65, 14, 0.5), 0 0 30px rgba(183, 65, 14, 0.3) !important;
      opacity: 1 !important;
      left: 50% !important;
      top: 50% !important;
      display: block !important;
      visibility: visible !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .adinkra-cursor {
      position: fixed !important;
      font-size: 32px !important;
      pointer-events: none !important;
      z-index: 100000 !important;
      transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
      transform: translate(-50%, -50%) scale(0) !important;
      opacity: 0 !important;
      color: ${CURSOR_COLORS.secondary} !important;
      filter: drop-shadow(0 2px 8px rgba(255, 154, 86, 0.5)) !important;
      text-shadow: 0 0 10px ${CURSOR_COLORS.primary} !important;
      left: 50% !important;
      top: 50% !important;
      display: block !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .adinkra-cursor.active {
      transform: translate(-50%, -50%) scale(1) !important;
      opacity: 1 !important;
      display: block !important;
      visibility: visible !important;
    }

    .adinkra-cursor.active ~ .custom-cursor,
    .adinkra-cursor.active ~ .cursor-ring {
      opacity: 0 !important;
      transform: translate(-50%, -50%) scale(0) !important;
    }
    
    /* Ensure cursor is visible when hovering interactive elements */
    .custom-cursor.hover,
    .cursor-ring.hover {
      opacity: 0 !important;
    }

    .cursor-trail {
      position: fixed;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: linear-gradient(135deg, ${CURSOR_COLORS.primary}, ${CURSOR_COLORS.secondary});
      pointer-events: none;
      z-index: 9998;
      opacity: 0;
      animation: trail-fade 0.6s ease-out forwards;
      box-shadow: 0 0 10px rgba(255, 154, 86, 0.6);
    }

    @keyframes trail-fade {
      0% {
        opacity: 0.8;
        transform: scale(1);
      }
      100% {
        opacity: 0;
        transform: scale(0.3);
      }
    }

    .custom-cursor.hover {
      width: 60px;
      height: 60px;
      background: rgba(255, 154, 86, 0.2);
      border: 2px solid ${CURSOR_COLORS.primary};
      box-shadow: 0 0 30px rgba(255, 154, 86, 0.6);
    }

    .cursor-ring.hover {
      width: 80px;
      height: 80px;
      border-color: ${CURSOR_COLORS.secondary};
      border-width: 3px;
      box-shadow: 0 0 25px rgba(183, 65, 14, 0.7);
    }

    @keyframes adinkra-rotate {
      from {
        transform: translate(-50%, -50%) scale(1) rotate(0deg);
      }
      to {
        transform: translate(-50%, -50%) scale(1) rotate(360deg);
      }
    }
  `;
  
  console.log('✓ Cursor styles added to head');
  
  // Insert cursor HTML into body - ensure body exists
  if (!document.body) {
    console.error('✗ Cannot initialize cursor: document.body does not exist');
    return;
  }
  
  // Use createElement approach (more reliable than insertAdjacentHTML)
  try {
    console.log('Creating cursor elements with createElement...');
    
    // Create adinkra cursor element
    const adinkraCursor = document.createElement('div');
    adinkraCursor.className = 'adinkra-cursor';
    adinkraCursor.textContent = '✦';
    adinkraCursor.setAttribute('data-cursor-type', 'adinkra');
    console.log('✓ Adinkra cursor element created');
    
    // Create custom cursor element
    const customCursor = document.createElement('div');
    customCursor.className = 'custom-cursor';
    customCursor.setAttribute('data-cursor-type', 'custom');
    console.log('✓ Custom cursor element created');
    
    // Create cursor ring element
    const cursorRing = document.createElement('div');
    cursorRing.className = 'cursor-ring';
    cursorRing.setAttribute('data-cursor-type', 'ring');
    console.log('✓ Cursor ring element created');
    
    // Append to body
    document.body.appendChild(adinkraCursor);
    document.body.appendChild(customCursor);
    document.body.appendChild(cursorRing);
    
    console.log('✓ All cursor elements appended to body');
    
    // IMMEDIATE VERIFICATION
    console.log('=== IMMEDIATE VERIFICATION ===');
    const immediateCheck1 = document.querySelector('.custom-cursor');
    const immediateCheck2 = document.querySelector('.cursor-ring');
    const immediateCheck3 = document.querySelector('.adinkra-cursor');
    
    console.log('Immediate querySelector results:');
    console.log('  custom-cursor found:', !!immediateCheck1, immediateCheck1);
    console.log('  cursor-ring found:', !!immediateCheck2, immediateCheck2);
    console.log('  adinkra-cursor found:', !!immediateCheck3, immediateCheck3);
    
    // Check if elements are in body HTML
    const bodyHTML = document.body.innerHTML;
    const hasCustomCursor = bodyHTML.includes('custom-cursor');
    const hasCursorRing = bodyHTML.includes('cursor-ring');
    const hasAdinkraCursor = bodyHTML.includes('adinkra-cursor');
    
    console.log('HTML contains custom-cursor:', hasCustomCursor);
    console.log('HTML contains cursor-ring:', hasCursorRing);
    console.log('HTML contains adinkra-cursor:', hasAdinkraCursor);
    
    // Verify elements are actually in DOM
    const allElements = document.body.querySelectorAll('[data-cursor-type]');
    console.log('Elements with data-cursor-type:', allElements.length);
    allElements.forEach((el, idx) => {
      console.log(`  Element ${idx + 1}:`, el.className, el);
    });
    
    // Check parent-child relationship
    if (immediateCheck1) {
      console.log('custom-cursor parent:', immediateCheck1.parentElement);
      console.log('custom-cursor in body:', document.body.contains(immediateCheck1));
    }
    if (immediateCheck2) {
      console.log('cursor-ring parent:', immediateCheck2.parentElement);
      console.log('cursor-ring in body:', document.body.contains(immediateCheck2));
    }
    if (immediateCheck3) {
      console.log('adinkra-cursor parent:', immediateCheck3.parentElement);
      console.log('adinkra-cursor in body:', document.body.contains(immediateCheck3));
    }
    
    // Set up MutationObserver to detect if elements are removed
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.removedNodes.forEach((node) => {
          if (node.nodeType === 1 && node.classList) {
            if (node.classList.contains('custom-cursor') || 
                node.classList.contains('cursor-ring') || 
                node.classList.contains('adinkra-cursor')) {
              console.error('⚠️ CURSOR ELEMENT WAS REMOVED FROM DOM!', {
                className: node.className,
                parent: node.parentElement,
                stack: new Error().stack
              });
            }
          }
        });
      });
    });
    
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
    
    console.log('✓ MutationObserver set up to detect element removal');
    
    if (immediateCheck1 && immediateCheck2 && immediateCheck3) {
      console.log('✓✓✓ ALL CURSOR ELEMENTS SUCCESSFULLY CREATED AND FOUND ✓✓✓');
    } else {
      console.error('✗✗✗ SOME CURSOR ELEMENTS NOT FOUND AFTER CREATION ✗✗✗');
    }
    
  } catch (e) {
    console.error('✗ Error creating cursor elements:', e);
    console.error('Error stack:', e.stack);
  }
}

// Main cursor logic
function setupAdinkraCursor() {
  // Disable ONLY on actual mobile devices (same logic as initAdinkraCursor)
  const mobileUserAgents = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
  const isMobileDevice = mobileUserAgents.test(navigator.userAgent);
  const isSmallTouchScreen = window.innerWidth <= 480 && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
  
  if (isMobileDevice || isSmallTouchScreen) {
    console.log('Cursor setup disabled: Mobile/small touch device detected');
    return; // Exit function - don't setup cursor on mobile devices
  }

  let currentSymbolIndex = 0;
  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let ringX = mouseX;
  let ringY = mouseY;
  let lastTrailTime = 0;

  const cursor = document.querySelector('.custom-cursor');
  const cursorRing = document.querySelector('.cursor-ring');
  const adinkraCursor = document.querySelector('.adinkra-cursor');

  // Check if cursor elements exist
  if (!cursor || !cursorRing || !adinkraCursor) {
    return; // Exit if elements don't exist (mobile device)
  }

  // Set initial position and make visible
  cursor.style.left = mouseX + 'px';
  cursor.style.top = mouseY + 'px';
  cursor.style.opacity = '1';
  cursor.style.display = 'block';
  cursor.style.visibility = 'visible';
  cursor.style.zIndex = '99999';
  cursorRing.style.left = ringX + 'px';
  cursorRing.style.top = ringY + 'px';
  cursorRing.style.opacity = '1';
  cursorRing.style.display = 'block';
  cursorRing.style.visibility = 'visible';
  cursorRing.style.zIndex = '99998';
  adinkraCursor.style.left = mouseX + 'px';
  adinkraCursor.style.top = mouseY + 'px';
  adinkraCursor.style.display = 'block';
  adinkraCursor.style.zIndex = '100000';
  
  // Debug: Log cursor initialization
  console.log('Adinkra cursor initialized', {
    cursor: !!cursor,
    cursorRing: !!cursorRing,
    adinkraCursor: !!adinkraCursor,
    mouseX: mouseX,
    mouseY: mouseY,
    cursorStyle: {
      display: cursor.style.display,
      visibility: cursor.style.visibility,
      opacity: cursor.style.opacity,
      left: cursor.style.left,
      top: cursor.style.top
    }
  });
  
  // Test: Log when hovering over any button
  console.log('Cursor setup complete. Try hovering over a button to see the Adinkra symbol appear.');

  // Update cursor position on mouse move
  document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;

    // Update main cursor immediately
    cursor.style.left = mouseX + 'px';
    cursor.style.top = mouseY + 'px';
    cursor.style.opacity = '1';
    cursor.style.display = 'block';
    cursor.style.visibility = 'visible';
    cursor.style.zIndex = '99999';
    
    cursorRing.style.left = ringX + 'px';
    cursorRing.style.top = ringY + 'px';
    cursorRing.style.opacity = '1';
    cursorRing.style.display = 'block';
    cursorRing.style.visibility = 'visible';
    cursorRing.style.zIndex = '99998';
    
    adinkraCursor.style.left = mouseX + 'px';
    adinkraCursor.style.top = mouseY + 'px';
    adinkraCursor.style.display = 'block';
    adinkraCursor.style.zIndex = '100000';

    // Create trail effect
    const currentTime = Date.now();
    if (currentTime - lastTrailTime > TRAIL_DELAY) {
      createTrail(mouseX, mouseY);
      lastTrailTime = currentTime;
    }
  });

  // Smooth ring follow with slight delay
  function animateRing() {
    ringX += (mouseX - ringX) * 0.15;
    ringY += (mouseY - ringY) * 0.15;

    cursorRing.style.left = ringX + 'px';
    cursorRing.style.top = ringY + 'px';

    requestAnimationFrame(animateRing);
  }
  animateRing();

  // Create trail dot
  function createTrail(x, y) {
    const trail = document.createElement('div');
    trail.className = 'cursor-trail';
    trail.style.left = x + 'px';
    trail.style.top = y + 'px';
    document.body.appendChild(trail);

    setTimeout(() => trail.remove(), 600);
  }

  // Use event delegation for hover effects (more reliable)
  // mouseover/mouseout bubble, so we can use event delegation
  function handleMouseOver(e) {
    const target = e.target;
    
    // Check if target or any parent is an interactive element
    const interactiveSelectors = 'a, button, input, textarea, select, [role="button"], [onclick], .btn, .card, .sidebar-link, .top-navbar-link, .footer-link, .social-link, .product-card, .warm-card, .heritage-card, .impact-card, .feature-card, .testimonial-card, .designer-card, .recommendation-card, .education-card, .action-card, .quick-stat, .brand-card';
    
    const isInteractive = target.matches(interactiveSelectors) || target.closest(interactiveSelectors);
    
    if (isInteractive && !adinkraCursor.classList.contains('active')) {
      console.log('Hover detected on interactive element:', target.tagName, target.className);
      
      adinkraCursor.classList.add('active');
      cursor.classList.add('hover');
      cursorRing.classList.add('hover');

      // Change to next Adinkra symbol
      currentSymbolIndex = (currentSymbolIndex + 1) % ADINKRA_SYMBOLS.length;
      adinkraCursor.textContent = ADINKRA_SYMBOLS[currentSymbolIndex];
      
      // Ensure cursor is visible
      adinkraCursor.style.opacity = '1';
      adinkraCursor.style.display = 'block';
      adinkraCursor.style.visibility = 'visible';
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(1)';
      adinkraCursor.style.zIndex = '100000';
      
      // Add rotation animation
      adinkraCursor.style.animation = 'adinkra-rotate 0.4s ease-out';
      
      // Hide main cursor and ring
      cursor.style.opacity = '0';
      cursorRing.style.opacity = '0';
    }
  }

  function handleMouseOut(e) {
    const target = e.target;
    const relatedTarget = e.relatedTarget;
    
    const interactiveSelectors = 'a, button, input, textarea, select, [role="button"], [onclick], .btn, .card, .sidebar-link, .top-navbar-link, .footer-link, .social-link, .product-card, .warm-card, .heritage-card, .impact-card, .feature-card, .testimonial-card, .designer-card, .recommendation-card, .education-card, .action-card, .quick-stat, .brand-card';
    
    const isInteractive = target.matches(interactiveSelectors) || target.closest(interactiveSelectors);
    const isStillOnInteractive = relatedTarget && (relatedTarget.matches(interactiveSelectors) || relatedTarget.closest(interactiveSelectors));
    
    // Only hide if we're leaving an interactive element and not entering another one
    if (isInteractive && !isStillOnInteractive && adinkraCursor.classList.contains('active')) {
      console.log('Mouse leave from interactive element:', target.tagName, target.className);
      
      adinkraCursor.classList.remove('active');
      cursor.classList.remove('hover');
      cursorRing.classList.remove('hover');
      
      // Show main cursor and ring again
      cursor.style.opacity = '1';
      cursorRing.style.opacity = '1';
      
      // Hide adinkra cursor
      adinkraCursor.style.opacity = '0';
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(0)';
    }
  }

  // Use event delegation on document body (works for all elements, including dynamically added ones)
  // mouseover/mouseout bubble, so we can capture them on document
  document.addEventListener('mouseover', handleMouseOver, true);
  document.addEventListener('mouseout', handleMouseOut, true);

  // Hide cursor when leaving window
  document.addEventListener('mouseleave', () => {
    cursor.style.opacity = '0';
    cursorRing.style.opacity = '0';
    adinkraCursor.style.opacity = '0';
  });

  document.addEventListener('mouseenter', () => {
    cursor.style.opacity = '1';
    cursorRing.style.opacity = '1';
  });

  // Click effect
  document.addEventListener('mousedown', () => {
    cursor.style.transform = 'translate(-50%, -50%) scale(0.8)';
    cursorRing.style.transform = 'translate(-50%, -50%) scale(0.9)';
    if (adinkraCursor.classList.contains('active')) {
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(0.8)';
    }
  });

  document.addEventListener('mouseup', () => {
    cursor.style.transform = 'translate(-50%, -50%) scale(1)';
    cursorRing.style.transform = 'translate(-50%, -50%) scale(1)';
    if (adinkraCursor.classList.contains('active')) {
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(1)';
    }
  });
}

// Initialize when DOM is ready
function initializeCursor() {
  // Wait for body to exist
  if (!document.body) {
    setTimeout(initializeCursor, 10);
    return;
  }
  
  // Check if already initialized
  if (document.querySelector('.custom-cursor')) {
    console.log('Cursor already initialized, skipping...');
    return;
  }
  
  console.log('Initializing Adinkra cursor...');
  
  // Ensure body exists before inserting HTML
  if (!document.body) {
    console.log('Body not ready, retrying in 50ms...');
    setTimeout(initializeCursor, 50);
    return;
  }
  
  // Initialize cursor elements
  initAdinkraCursor();
  
  // Wait a bit longer to ensure DOM is fully ready
  setTimeout(() => {
    const cursor = document.querySelector('.custom-cursor');
    const cursorRing = document.querySelector('.cursor-ring');
    const adinkraCursor = document.querySelector('.adinkra-cursor');
    
    if (!cursor || !cursorRing || !adinkraCursor) {
      console.error('Cursor elements not found after initialization!', {
        cursor: !!cursor,
        cursorRing: !!cursorRing,
        adinkraCursor: !!adinkraCursor,
        body: !!document.body,
        bodyChildren: document.body ? document.body.children.length : 0
      });
      
      // Try one more time with a fresh initialization
      setTimeout(() => {
        if (!document.querySelector('.custom-cursor') && document.body) {
          console.log('Retrying cursor initialization...');
          initAdinkraCursor();
          setTimeout(() => {
            const retryCursor = document.querySelector('.custom-cursor');
            const retryRing = document.querySelector('.cursor-ring');
            const retryAdinkra = document.querySelector('.adinkra-cursor');
            if (retryCursor && retryRing && retryAdinkra) {
              console.log('Retry successful, setting up cursor...');
              setupAdinkraCursor();
            } else {
              console.error('Retry failed - cursor elements still not found');
            }
          }, 200);
        }
      }, 500);
      return;
    }
    
    console.log('Setting up Adinkra cursor...');
    setupAdinkraCursor();
  }, 200);
}

// Try multiple initialization methods
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeCursor);
} else {
  // DOM already loaded
  initializeCursor();
}

// Also try after window load as fallback
window.addEventListener('load', () => {
  console.log('Window load event fired, checking cursor...');
  if (!document.querySelector('.custom-cursor')) {
    console.log('🔄 Retrying cursor initialization after window load...');
    initializeCursor();
  } else {
    console.log('✓ Cursor already exists, ensuring visibility...');
    // Ensure cursor is visible even if already initialized
    const cursor = document.querySelector('.custom-cursor');
    const cursorRing = document.querySelector('.cursor-ring');
    const adinkraCursor = document.querySelector('.adinkra-cursor');
    
    if (cursor) {
      cursor.style.display = 'block';
      cursor.style.visibility = 'visible';
      cursor.style.opacity = '1';
      console.log('✓ Custom cursor made visible');
    }
    if (cursorRing) {
      cursorRing.style.display = 'block';
      cursorRing.style.visibility = 'visible';
      cursorRing.style.opacity = '1';
      console.log('✓ Cursor ring made visible');
    }
    if (adinkraCursor) {
      adinkraCursor.style.display = 'block';
      console.log('✓ Adinkra cursor made visible');
    }
  }
});

// SIMPLE TEST - Run after everything to verify basic insertion works
setTimeout(() => {
  console.log('=== SIMPLE INSERTION TEST ===');
  const testDiv = document.createElement('div');
  testDiv.className = 'cursor-test-element';
  testDiv.style.cssText = 'position:fixed; top:10px; left:10px; background:red; padding:10px; z-index:999999; color:white; font-weight:bold;';
  testDiv.textContent = 'TEST CURSOR ELEMENT';
  testDiv.setAttribute('data-test', 'cursor-insertion');
  
  try {
    document.body.appendChild(testDiv);
    console.log('✓ Test element appended to body');
    
    setTimeout(() => {
      const found = document.querySelector('.cursor-test-element');
      console.log('Test element found:', !!found);
      if (found) {
        console.log('✓✓✓ BASIC INSERTION WORKS - problem is specific to cursor code ✓✓✓');
        // Remove test element after 3 seconds
        setTimeout(() => {
          if (found && found.parentElement) {
            found.remove();
            console.log('Test element removed');
          }
        }, 3000);
      } else {
        console.error('✗✗✗ BASIC INSERTION FAILS - something is blocking ALL insertions ✗✗✗');
        console.error('This may indicate a Content Security Policy (CSP) issue');
      }
    }, 100);
  } catch (e) {
    console.error('✗ Error in test insertion:', e);
  }
}, 2000);

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { initAdinkraCursor, setupAdinkraCursor };
}


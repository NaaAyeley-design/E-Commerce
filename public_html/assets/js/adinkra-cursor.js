// ============================================
// ADINKRA CURSOR WITH TRAIL EFFECT
// Blue Glowing Gradient Theme
// ============================================

// Configuration
// Get base path for assets - FIXED to ALWAYS use assets/images/ correctly
const getAssetPath = (filename) => {
  // URL encode spaces in filename
  const encodedFilename = filename.replace(/ /g, '%20');
  
  // Priority 1: Use ASSETS_URL if available (set by PHP in footer) - MOST RELIABLE
  if (typeof ASSETS_URL !== 'undefined' && ASSETS_URL && ASSETS_URL !== '') {
    // Ensure ASSETS_URL doesn't already end with /assets
    let assetsPath = ASSETS_URL;
    if (assetsPath.endsWith('/assets')) {
      assetsPath = assetsPath;
    } else if (!assetsPath.includes('/assets')) {
      assetsPath = assetsPath + '/assets';
    }
    const path = assetsPath + '/images/' + encodedFilename;
    console.log('✓ Using ASSETS_URL path:', path);
    return path;
  }
  
  // Priority 2: Use BASE_URL if available
  if (typeof BASE_URL !== 'undefined' && BASE_URL && BASE_URL !== '') {
    const path = BASE_URL + '/assets/images/' + encodedFilename;
    console.log('✓ Using BASE_URL path:', path);
    return path;
  }
  
  // Priority 3: Use direct assets/images path (works from public_html root)
  // This is the most reliable fallback - always use assets/images/
  const directPath = 'assets/images/' + encodedFilename;
  console.log('✓ Using direct assets path:', directPath);
  return directPath;
};

// Files are .svg format (confirmed from directory listing: adinkra symbol 1.svg, etc.)
// If your files are actually .png, change .svg to .png below
const ADINKRA_SYMBOLS = [
  getAssetPath('adinkra symbol 1.svg'),
  getAssetPath('adinkra symbol 2.svg'),
  getAssetPath('adinkra symbol 3.svg'),
  getAssetPath('adinkra symbol 4.svg')
];

// Debug: Log the paths being used
console.log('=== ADINKRA CURSOR CONFIGURATION ===');
console.log('ASSETS_URL available:', typeof ASSETS_URL !== 'undefined' ? ASSETS_URL : 'NOT SET');
console.log('BASE_URL available:', typeof BASE_URL !== 'undefined' ? BASE_URL : 'NOT SET');
console.log('Adinkra symbol paths:', ADINKRA_SYMBOLS);

// Test if first image can load (debugging)
const testImg = new Image();
testImg.onload = () => console.log('✓✓✓ FIRST SYMBOL TEST: Image loaded successfully! Path:', testImg.src);
testImg.onerror = () => console.error('✗✗✗ FIRST SYMBOL TEST: Failed to load image. Path:', testImg.src, 'Check that file exists and path is correct.');
testImg.src = ADINKRA_SYMBOLS[0];
const TRAIL_DELAY = 30; // milliseconds between trail dots
const CURSOR_COLORS = {
  primary: '#FF9A56',    // Amber
  secondary: '#B7410E',  // Rust
  dark: '#6B4423'        // Earth Brown
};

// Color schemes for different element types
const SYMBOL_COLORS = {
  button: {
    primary: 'rgba(255, 154, 86, 0.9)',   // Amber
    secondary: 'rgba(183, 65, 14, 0.7)',   // Rust
    hueRotate: '0deg',
    brightness: '0.8'
  },
  link: {
    primary: 'rgba(183, 65, 14, 0.9)',     // Rust/Red
    secondary: 'rgba(255, 154, 86, 0.7)',  // Amber
    hueRotate: '-20deg',
    brightness: '0.8'
  },
  card: {
    primary: 'rgba(107, 68, 35, 0.9)',     // Brown
    secondary: 'rgba(139, 90, 43, 0.7)',   // Light brown
    hueRotate: '-30deg',
    brightness: '0.7'
  },
  input: {
    primary: 'rgba(100, 150, 200, 0.9)',   // Blue
    secondary: 'rgba(70, 120, 180, 0.7)',  // Dark blue
    hueRotate: '180deg',
    brightness: '0.85'
  },
  product: {
    primary: 'rgba(76, 175, 80, 0.9)',     // Green
    secondary: 'rgba(56, 142, 60, 0.7)',   // Dark green
    hueRotate: '100deg',
    brightness: '0.8'
  },
  default: {
    primary: 'rgba(255, 154, 86, 0.9)',   // Amber (default)
    secondary: 'rgba(183, 65, 14, 0.7)',   // Rust
    hueRotate: '0deg',
    brightness: '0.8'
  }
};

// Function to get color scheme based on element type
function getElementColor(element) {
  // Determine element type
  if (element.matches('button, .btn, [type="button"], [type="submit"]')) {
    return SYMBOL_COLORS.button;
  } else if (element.matches('a, .link')) {
    return SYMBOL_COLORS.link;
  } else if (element.matches('.card, .product-card, .heritage-card, .warm-card, .feature-card, .testimonial-card, .designer-card, .recommendation-card, .education-card, .action-card, .brand-card')) {
    return SYMBOL_COLORS.card;
  } else if (element.matches('input, textarea, select, [type="text"], [type="email"], [type="password"], [type="search"]')) {
    return SYMBOL_COLORS.input;
  } else if (element.matches('.product-item, [data-product], .product, .product-card')) {
    return SYMBOL_COLORS.product;
  }
  
  // Default to button colors
  return SYMBOL_COLORS.default;
}

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
    /* Hide system cursor ONLY on interactive elements */
    a, 
    button, 
    input, 
    textarea, 
    select, 
    [role="button"],
    [onclick],
    .btn, 
    .card,
    .sidebar-link,
    .top-navbar-link,
    .footer-link,
    .social-link,
    .product-card,
    .product-item,
    .warm-card,
    .heritage-card,
    .impact-card,
    .feature-card,
    .testimonial-card,
    .designer-card,
    .recommendation-card,
    .education-card,
    .action-card,
    .quick-stat,
    .brand-card {
      cursor: none !important;
    }

    /* Keep normal cursor everywhere else */
    body {
      cursor: auto !important;
    }

    * {
      /* Allow elements to have their normal cursors */
      cursor: inherit;
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
      left: 0;
      top: 0;
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
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
      left: 0;
      top: 0;
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .adinkra-cursor {
      position: fixed !important;
      width: 50px !important;
      height: 50px !important;
      pointer-events: none !important;
      z-index: 100000 !important;
      transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
      transform: translate(-50%, -50%) scale(0) !important;
      opacity: 0 !important;
      left: 0;
      top: 0;
      display: block !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .adinkra-cursor img,
    .adinkra-cursor .adinkra-img {
      width: 100% !important;
      height: 100% !important;
      object-fit: contain !important;
      /* Default brown/rust color - will be overridden by JavaScript for different elements */
      filter: 
        brightness(0.8)
        sepia(100%)
        saturate(300%)
        hue-rotate(0deg)
        drop-shadow(0 2px 12px rgba(255, 154, 86, 0.9))
        drop-shadow(0 0 20px rgba(183, 65, 14, 0.7))
        drop-shadow(0 0 30px rgba(255, 154, 86, 0.5)) !important;
      /* Smooth transitions for color changes */
      transition: filter 0.3s ease-in-out !important;
      /* Ensure image is centered */
      display: block !important;
    }

    .adinkra-cursor.active {
      transform: translate(-50%, -50%) scale(1) !important;
      opacity: 1 !important;
      display: block !important;
      visibility: visible !important;
      animation: adinkra-rotate 0.4s ease-out !important;
      /* Make symbol more prominent since it's replacing the cursor */
      filter: drop-shadow(0 0 25px rgba(255, 154, 86, 0.6)) !important;
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
    
    // Create adinkra cursor element with image
    const adinkraCursor = document.createElement('div');
    adinkraCursor.className = 'adinkra-cursor';
    adinkraCursor.setAttribute('data-cursor-type', 'adinkra');
    
    // Create image element inside adinkra cursor
    const adinkraImg = document.createElement('img');
    adinkraImg.className = 'adinkra-img';
    adinkraImg.src = ADINKRA_SYMBOLS[0];
    adinkraImg.alt = 'Adinkra Symbol';
    
    // Add error handler to debug image loading
    adinkraImg.onerror = function() {
      console.error('✗ Failed to load Adinkra image:', this.src);
      console.error('Check that the file exists at:', this.src);
    };
    
    adinkraImg.onload = function() {
      console.log('✓ Adinkra image loaded successfully:', this.src);
    };
    
    adinkraCursor.appendChild(adinkraImg);
    
    console.log('✓ Adinkra cursor element created with image');
    console.log('Initial image src:', ADINKRA_SYMBOLS[0]);
    
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
  const adinkraImg = document.querySelector('.adinkra-img');

  // Check if cursor elements exist
  if (!cursor || !cursorRing || !adinkraCursor || !adinkraImg) {
    console.error('Cursor elements not found:', {
      cursor: !!cursor,
      cursorRing: !!cursorRing,
      adinkraCursor: !!adinkraCursor,
      adinkraImg: !!adinkraImg
    });
    return; // Exit if elements don't exist (mobile device)
  }

  // Set initial position - keep custom cursor and ring HIDDEN
  cursor.style.left = mouseX + 'px';
  cursor.style.top = mouseY + 'px';
  cursor.style.display = 'none';
  cursor.style.visibility = 'hidden';
  cursor.style.opacity = '0';
  cursorRing.style.left = ringX + 'px';
  cursorRing.style.top = ringY + 'px';
  cursorRing.style.display = 'none';
  cursorRing.style.visibility = 'hidden';
  cursorRing.style.opacity = '0';
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

    // Update positions - keep custom cursor and ring HIDDEN
    cursor.style.left = mouseX + 'px';
    cursor.style.top = mouseY + 'px';
    cursor.style.display = 'none';
    cursor.style.visibility = 'hidden';
    cursor.style.opacity = '0';
    
    cursorRing.style.left = ringX + 'px';
    cursorRing.style.top = ringY + 'px';
    cursorRing.style.display = 'none';
    cursorRing.style.visibility = 'hidden';
    cursorRing.style.opacity = '0';
    
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
      const element = target.closest(interactiveSelectors) || target;
      const colors = getElementColor(element);
      
      console.log('Hover detected on interactive element:', target.tagName, target.className, 'Color scheme:', colors);
      
      adinkraCursor.classList.add('active');
      cursor.classList.add('hover');
      cursorRing.classList.add('hover');

      // Change to next Adinkra symbol (update image src)
      currentSymbolIndex = (currentSymbolIndex + 1) % ADINKRA_SYMBOLS.length;
      const adinkraImg = adinkraCursor.querySelector('.adinkra-img');
      if (adinkraImg) {
        const newSrc = ADINKRA_SYMBOLS[currentSymbolIndex];
        console.log('Changing to symbol:', currentSymbolIndex, 'Path:', newSrc);
        adinkraImg.src = newSrc;
        
        // Apply dynamic color filter based on element type
        adinkraImg.style.filter = `
          brightness(${colors.brightness})
          sepia(100%)
          saturate(300%)
          hue-rotate(${colors.hueRotate})
          drop-shadow(0 2px 12px ${colors.primary})
          drop-shadow(0 0 20px ${colors.secondary})
          drop-shadow(0 0 30px ${colors.primary})
        `;
        
        // Add error handler for debugging
        adinkraImg.onerror = function() {
          console.error('✗ Failed to load Adinkra image:', this.src);
        };
        adinkraImg.onload = function() {
          console.log('✓ Adinkra image loaded:', this.src);
        };
      } else {
        console.error('✗ Adinkra image element not found');
      }
      
      // Ensure cursor is visible
      adinkraCursor.style.opacity = '1';
      adinkraCursor.style.display = 'block';
      adinkraCursor.style.visibility = 'visible';
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(1)';
      adinkraCursor.style.zIndex = '100000';
      
      // Rotation animation is handled by CSS (.adinkra-cursor.active)
      // Keep custom cursor and ring hidden
      cursor.style.display = 'none';
      cursor.style.opacity = '0';
      cursorRing.style.display = 'none';
      cursorRing.style.opacity = '0';
    } else if (isInteractive && adinkraCursor.classList.contains('active')) {
      // Element type might have changed, update color
      const element = target.closest(interactiveSelectors) || target;
      const colors = getElementColor(element);
      const adinkraImg = adinkraCursor.querySelector('.adinkra-img');
      
      if (adinkraImg) {
        // Update color if hovering different element type
        adinkraImg.style.filter = `
          brightness(${colors.brightness})
          sepia(100%)
          saturate(300%)
          hue-rotate(${colors.hueRotate})
          drop-shadow(0 2px 12px ${colors.primary})
          drop-shadow(0 0 20px ${colors.secondary})
          drop-shadow(0 0 30px ${colors.primary})
        `;
      }
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
      
      // Keep custom cursor and ring hidden
      cursor.style.display = 'none';
      cursor.style.opacity = '0';
      cursorRing.style.display = 'none';
      cursorRing.style.opacity = '0';
      
      // Reset color filter to default
      const adinkraImg = adinkraCursor.querySelector('.adinkra-img');
      if (adinkraImg) {
        adinkraImg.style.filter = ''; // Clear inline styles, use CSS default
      }
      
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
    cursor.style.display = 'none';
    cursor.style.opacity = '0';
    cursorRing.style.display = 'none';
    cursorRing.style.opacity = '0';
    adinkraCursor.style.opacity = '0';
  });

  document.addEventListener('mouseenter', () => {
    // Keep custom cursor and ring hidden on mouse enter
    cursor.style.display = 'none';
    cursor.style.opacity = '0';
    cursorRing.style.display = 'none';
    cursorRing.style.opacity = '0';
  });

  // Click effect - scale down on click with enhanced glow
  document.addEventListener('mousedown', () => {
    cursor.style.transform = 'translate(-50%, -50%) scale(0.8)';
    cursorRing.style.transform = 'translate(-50%, -50%) scale(0.9)';
    if (adinkraCursor.classList.contains('active')) {
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(0.8)';
      // Enhance glow on click
      if (adinkraImg) {
        adinkraImg.style.filter = 'drop-shadow(0 6px 20px rgba(255, 154, 86, 1)) drop-shadow(0 0 24px rgba(255, 154, 86, 1)) drop-shadow(0 0 36px rgba(183, 65, 14, 1)) drop-shadow(0 0 60px rgba(255, 154, 86, 0.9)) brightness(1.4) contrast(1.2) saturate(1.5)';
      }
    }
  });

  document.addEventListener('mouseup', () => {
    cursor.style.transform = 'translate(-50%, -50%) scale(1)';
    cursorRing.style.transform = 'translate(-50%, -50%) scale(1)';
    if (adinkraCursor.classList.contains('active')) {
      adinkraCursor.style.transform = 'translate(-50%, -50%) scale(1)';
      // Restore normal glow on release (let CSS handle it)
      if (adinkraImg) {
        adinkraImg.style.filter = ''; // Reset to CSS default
      }
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
      // Keep custom cursor hidden
      cursor.style.display = 'none';
      cursor.style.visibility = 'hidden';
      cursor.style.opacity = '0';
      console.log('✓ Custom cursor kept hidden (normal cursor shown)');
    }
    if (cursorRing) {
      // Keep cursor ring hidden
      cursorRing.style.display = 'none';
      cursorRing.style.visibility = 'hidden';
      cursorRing.style.opacity = '0';
      console.log('✓ Cursor ring kept hidden (normal cursor shown)');
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


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
  // Disable on mobile/touch devices
  if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
    return; // Exit function - don't initialize cursor on touch devices
  }

  // Create cursor HTML
  const cursorHTML = `
    <div class="adinkra-cursor">✦</div>
    <div class="custom-cursor"></div>
    <div class="cursor-ring"></div>
  `;
  
  document.body.insertAdjacentHTML('beforeend', cursorHTML);
  
  // Add styles
  const style = document.createElement('style');
  style.textContent = `
    body {
      cursor: none !important;
    }
    
    * {
      cursor: none !important;
    }

    .custom-cursor {
      position: fixed;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: ${CURSOR_COLORS.primary};
      pointer-events: none;
      z-index: 10000;
      transition: all 0.15s ease-out;
      transform: translate(-50%, -50%);
      box-shadow: 0 0 20px ${CURSOR_COLORS.primary}, 0 0 40px rgba(255, 154, 86, 0.5);
      opacity: 1;
      left: 50%;
      top: 50%;
      display: block !important;
      visibility: visible !important;
    }

    .cursor-ring {
      position: fixed;
      width: 40px;
      height: 40px;
      border: 2px solid ${CURSOR_COLORS.secondary};
      border-radius: 50%;
      pointer-events: none;
      z-index: 9999;
      transition: all 0.2s ease-out;
      transform: translate(-50%, -50%);
      box-shadow: 0 0 15px rgba(183, 65, 14, 0.5), 0 0 30px rgba(183, 65, 14, 0.3);
      opacity: 1;
      left: 50%;
      top: 50%;
      display: block !important;
      visibility: visible !important;
    }

    .adinkra-cursor {
      position: fixed;
      font-size: 32px;
      pointer-events: none;
      z-index: 10001;
      transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      transform: translate(-50%, -50%) scale(0);
      opacity: 0;
      color: ${CURSOR_COLORS.secondary};
      filter: drop-shadow(0 2px 8px rgba(255, 154, 86, 0.5));
      text-shadow: 0 0 10px ${CURSOR_COLORS.primary};
      left: 50%;
      top: 50%;
    }

    .adinkra-cursor.active {
      transform: translate(-50%, -50%) scale(1);
      opacity: 1;
    }

    .adinkra-cursor.active ~ .custom-cursor,
    .adinkra-cursor.active ~ .cursor-ring {
      opacity: 0;
      transform: translate(-50%, -50%) scale(0);
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
  document.head.appendChild(style);
}

// Main cursor logic
function setupAdinkraCursor() {
  // Disable on mobile/touch devices
  if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
    return; // Exit function - don't setup cursor on touch devices
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
  cursorRing.style.left = ringX + 'px';
  cursorRing.style.top = ringY + 'px';
  cursorRing.style.opacity = '1';
  cursorRing.style.display = 'block';
  adinkraCursor.style.left = mouseX + 'px';
  adinkraCursor.style.top = mouseY + 'px';
  adinkraCursor.style.display = 'block';
  
  // Debug: Log cursor initialization
  console.log('Adinkra cursor initialized', {
    cursor: cursor,
    cursorRing: cursorRing,
    adinkraCursor: adinkraCursor,
    mouseX: mouseX,
    mouseY: mouseY
  });

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
    
    cursorRing.style.opacity = '1';
    cursorRing.style.display = 'block';
    cursorRing.style.visibility = 'visible';
    
    adinkraCursor.style.left = mouseX + 'px';
    adinkraCursor.style.top = mouseY + 'px';
    adinkraCursor.style.display = 'block';

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

  // Track elements that already have hover effects
  const hoveredElements = new WeakSet();

  // Set up hover effects
  function addHoverEffects() {
    // Select all interactive elements
    const selectors = [
      'a', 'button', 
      '.product-card', '.warm-card', '.heritage-card',
      '.impact-card', '.feature-card', '.testimonial-card',
      '.designer-card', '.recommendation-card', '.education-card',
      '.action-card', '.quick-stat', '.brand-card',
      'input', 'textarea', 'select',
      '[role="button"]', '[onclick]',
      '.btn', '.card', '.sidebar-link',
      '.top-navbar-link', '.footer-link', '.social-link'
    ];
    
    const hoverElements = document.querySelectorAll(selectors.join(', '));

    hoverElements.forEach(element => {
      // Skip if already has hover effects
      if (hoveredElements.has(element)) {
        return;
      }

      // Mark as processed
      hoveredElements.add(element);
      
      element.addEventListener('mouseenter', () => {
        adinkraCursor.classList.add('active');
        cursor.classList.add('hover');
        cursorRing.classList.add('hover');

        // Change to next Adinkra symbol
        currentSymbolIndex = (currentSymbolIndex + 1) % ADINKRA_SYMBOLS.length;
        adinkraCursor.textContent = ADINKRA_SYMBOLS[currentSymbolIndex];
        
        // Add rotation animation
        adinkraCursor.style.animation = 'adinkra-rotate 0.4s ease-out';
      });

      element.addEventListener('mouseleave', () => {
        adinkraCursor.classList.remove('active');
        cursor.classList.remove('hover');
        cursorRing.classList.remove('hover');
      });
    });
  }

  // Initial setup
  addHoverEffects();

  // Re-apply to dynamically added elements
  const observer = new MutationObserver(() => {
    addHoverEffects();
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

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
  initAdinkraCursor();
  
  // Small delay to ensure elements are created before setup
  setTimeout(() => {
    const cursor = document.querySelector('.custom-cursor');
    const cursorRing = document.querySelector('.cursor-ring');
    const adinkraCursor = document.querySelector('.adinkra-cursor');
    
    if (!cursor || !cursorRing || !adinkraCursor) {
      console.error('Cursor elements not found after initialization!', {
        cursor: !!cursor,
        cursorRing: !!cursorRing,
        adinkraCursor: !!adinkraCursor
      });
      return;
    }
    
    console.log('Setting up Adinkra cursor...');
    setupAdinkraCursor();
  }, 100);
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
  if (!document.querySelector('.custom-cursor')) {
    console.log('Retrying cursor initialization after window load...');
    initializeCursor();
  }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { initAdinkraCursor, setupAdinkraCursor };
}


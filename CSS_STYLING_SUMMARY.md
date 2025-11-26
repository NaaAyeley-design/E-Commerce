# KenteKart CSS Styling Summary

## 🎨 **Design Philosophy**
**Refined Minimal Design** with a **Warm Terracotta & Beige Aesthetic** inspired by African Savanna themes.

---

## 🎨 **Color Palette**

### Primary Colors
- **White**: `#FFFFFF` - Pure white backgrounds
- **Warm Beige**: `#F4EDE4` - Light background tones
- **Terracotta**: `#C67D5C` - Primary accent color (buttons, links, borders)
- **Deep Brown**: `#8B6F47` - Secondary accent, hover states
- **Dark Brown**: `#5C4A3A` - Darker accents
- **Text Dark**: `#3A2F26` - Primary text color
- **Text Light**: `#8B7F74` - Secondary text, placeholders

### Legacy/Accent Colors (for hero sections)
- **Ochre**: `#CC8B3C`
- **Rust**: `#B7410E`
- **Amber**: `#FF9A56`
- **Sand**: `#E3B778`
- **Burnt Sienna**: `#E97451`
- **Savanna Gold**: `#D4A574`
- **Earth**: `#6B4423`
- **Cream**: `#FFF8E7`
- **Light Cream**: `#FFFBF5`

---

## 📝 **Typography**

### Font Families
- **Headings**: `'Cormorant Garamond', serif` - Elegant serif for titles
- **Body Text**: `'Spectral', serif` - Clean serif for content

### Font Weights
- **Light**: 300 (default body text)
- **Regular**: 400 (standard)
- **Semi-bold**: 600 (headings)

### Typography Characteristics
- **Line Height**: 1.8 (generous spacing for readability)
- **Letter Spacing**: 
  - Headings: `0.15em` to `0.2em` (uppercase)
  - Body: `0.03em` (minimal)
- **Text Transform**: Uppercase for buttons and some headings
- **Font Size**: Base `1rem` (16px)

---

## 📏 **Spacing System**

### Spacing Scale (CSS Variables)
```css
--spacing-xs: 0.5rem;    /* 8px */
--spacing-sm: 0.75rem;   /* 12px */
--spacing-md: 1rem;      /* 16px */
--spacing-lg: 1.5rem;    /* 24px */
--spacing-xl: 2rem;      /* 32px */
--spacing-2xl: 3rem;     /* 48px */
--spacing-3xl: 4rem;     /* 64px */
--spacing-4xl: 5rem;     /* 80px */
```

### Section Spacing
- **Section Padding Vertical**: `80px` (60px on tablet, 60px on mobile)
- **Section Padding Horizontal**: `60px` (40px on tablet, 30px on mobile)
- **Section Gap**: `120px` (100px on tablet, 80px on mobile)

---

## 📦 **Layout & Containers**

### Container
- **Max Width**: `1200px` (centered)
- **Padding**: Responsive based on screen size
- **Centered**: `margin: 0 auto`

### Grid System
- Uses CSS Grid and Flexbox
- Responsive breakpoints:
  - **Desktop**: 1024px+
  - **Tablet**: 768px - 1023px
  - **Mobile**: < 768px

---

## 🔲 **Border Radius**

```css
--radius-sm: 4px;      /* Small elements */
--radius-md: 8px;      /* Standard (buttons, cards, inputs) */
--radius-lg: 12px;     /* Large elements */
--radius-full: 50%;    /* Circular elements (icons, avatars) */
```

---

## 🔲 **Borders**

```css
--border-thin: 1px solid rgba(198, 125, 92, 0.15);      /* Subtle borders */
--border-medium: 1px solid rgba(198, 125, 92, 0.2);     /* Standard borders */
--border-divider: 1px solid var(--terracotta);          /* Strong dividers */
```

---

## 🌑 **Shadows**

```css
--shadow-card: 0 20px 60px rgba(139, 111, 71, 0.08);           /* Card shadow */
--shadow-card-hover: 0 20px 60px rgba(139, 111, 71, 0.12);     /* Card hover */
--shadow-subtle: 0 10px 40px rgba(139, 111, 71, 0.06);         /* Subtle shadow */
```

**Shadow Philosophy**: Soft, warm shadows using brown tones with low opacity (0.06-0.12)

---

## ⚡ **Transitions & Animations**

### Transition Durations
```css
--transition-fast: 0.3s ease;
--transition-base: 0.4s ease;
--transition-slow: 0.6s cubic-bezier(0.4, 0, 0.2, 1);
```

### Keyframe Animations
- **fadeIn**: Opacity 0 → 1
- **fadeInUp**: Fade + translateY(30px → 0)
- **fadeInDown**: Fade + translateY(-20px → 0)
- **scaleX**: Scale from 0 to 1 (for underlines, progress bars)
- **float**: Gentle vertical movement (for badges)
- **stars-drift**: Slow background pattern movement

---

## 🎯 **Component Styles**

### Buttons

**Base Button**:
- **Padding**: `20px 60px` (16px 40px on mobile)
- **Border**: `1px solid var(--terracotta)`
- **Background**: Transparent (outline) or `var(--terracotta)` (primary)
- **Color**: `var(--terracotta)` (outline) or `var(--white)` (primary)
- **Font**: `Spectral`, `0.875rem`, uppercase, `0.2em` letter-spacing
- **Border Radius**: `8px`
- **Hover Effect**: Slide-in background animation using `::before` pseudo-element

**Button Variants**:
- `.btn-primary`: Solid terracotta background, white text
- `.btn-outline`: Transparent background, terracotta border and text

### Cards

**Base Card**:
- **Background**: `var(--white)`
- **Padding**: `50px 60px` (40px 30px on mobile)
- **Border**: `var(--border-thin)`
- **Border Radius**: `8px`
- **Box Shadow**: None by default
- **Hover Effect**: 
  - `translateY(-10px)` lift
  - `box-shadow: var(--shadow-card-hover)`
  - Border color intensifies

### Input Fields

**Base Input**:
- **Width**: `100%`
- **Padding**: `14px 18px`
- **Border**: `var(--border-medium)`
- **Border Radius**: `8px`
- **Font**: `Spectral`, `0.95rem`, weight 300
- **Focus State**: 
  - Border color: `var(--terracotta)`
  - Box shadow: `0 0 0 3px rgba(198, 125, 92, 0.1)`

### Icons

**Icon Containers**:
- **Size**: `50px × 50px` (standard), `60px × 60px` (stat icons)
- **Background**: `var(--warm-beige)` (default), `var(--terracotta)` (stat icons)
- **Border Radius**: `8px` (standard), `50%` (circular stat icons)
- **Hover**: Background changes to `var(--terracotta)`, icon fill changes to white

---

## 🎨 **Background Patterns**

### Subtle Grid Pattern
- Fixed position overlay
- Repeating linear gradients at 0° and 90°
- Very low opacity (`rgba(198, 125, 92, 0.015)`)
- Creates subtle texture without distraction

### Adinkra Pattern
- Radial gradient circles
- Used in section backgrounds
- Low opacity (`0.05`)
- Cultural design element

---

## 📱 **Responsive Design**

### Breakpoints
- **Desktop**: `1024px+` (full spacing and layout)
- **Tablet**: `768px - 1023px` (reduced spacing)
- **Mobile**: `< 768px` (minimal spacing, stacked layouts)

### Mobile Adjustments
- Reduced padding on buttons and cards
- Smaller font sizes
- Stacked grid layouts
- Horizontal scrolling for navigation menus
- Touch-friendly tap targets (minimum 44px)

---

## 🎭 **Z-Index Layers**

```css
--z-base: 1;        /* Base content */
--z-elevated: 10;   /* Elevated elements */
--z-overlay: 100;   /* Overlays */
--z-modal: 1000;    /* Modals */
```

---

## 🎨 **Special Effects**

### Glassmorphism
- `backdrop-filter: blur(10px)`
- Semi-transparent backgrounds
- Used in hero badges and overlays

### Hover Effects
- **Cards**: Lift animation (`translateY(-10px)`) + shadow increase
- **Buttons**: Background slide-in animation
- **Icons**: Background color change + scale transform
- **Links**: Underline animation from center

### Gradient Backgrounds
- Hero sections use warm brown gradients
- Animated wave patterns using SVG backgrounds
- Floating particles/stars using radial gradients

---

## 📐 **Layout Patterns**

### Hero Sections
- Full viewport height (`min-height: 100vh`)
- Centered content with flexbox/grid
- Animated background patterns
- Overlay effects with z-index layering

### Section Layouts
- Generous vertical spacing (`120px` gap)
- Consistent horizontal padding
- Max-width containers for readability
- Grid-based content organization

### Navigation
- Sticky positioning (`position: sticky, top: 0`)
- Horizontal scrolling on mobile
- Underline hover effects
- Icon + text combinations

---

## 🎯 **Design Principles**

1. **Minimalism**: Clean, uncluttered layouts with generous whitespace
2. **Warmth**: Terracotta and beige tones create inviting atmosphere
3. **Elegance**: Serif typography adds sophistication
4. **Accessibility**: High contrast, readable fonts, touch-friendly targets
5. **Cultural Identity**: Subtle African-inspired patterns and colors
6. **Smooth Interactions**: Gentle transitions and hover effects
7. **Responsive First**: Mobile-optimized with progressive enhancement

---

## 📋 **CSS File Structure**

1. **kentekart-design-system.css** - Core design tokens and base components
2. **african-savanna-theme.css** - Theme colors and patterns
3. **header_footer.css** - Navigation and footer styles
4. **homepage.css** - Homepage-specific styles (hero section preserved)
5. **sidebar.css** - Admin sidebar navigation
6. **toast.css** - Notification/toast messages
7. **sleep.css** - Base reset and utilities
8. **Page-specific CSS** - Additional stylesheets loaded per page

---

## 🔧 **CSS Variables Usage**

All design tokens are stored as CSS custom properties (`:root` variables), making it easy to:
- Update colors globally
- Adjust spacing consistently
- Maintain design system integrity
- Theme switching (future enhancement)

---

## 💡 **Key Styling Patterns**

### Color Usage
- **Primary Actions**: Terracotta (`#C67D5C`)
- **Hover States**: Deep Brown (`#8B6F47`)
- **Text**: Dark Brown (`#3A2F26`) for primary, Light (`#8B7F74`) for secondary
- **Backgrounds**: White or Warm Beige (`#F4EDE4`)

### Spacing Philosophy
- Generous spacing for breathing room
- Consistent use of spacing scale
- Section gaps create visual separation

### Typography Hierarchy
- Large, elegant headings (Cormorant Garamond)
- Readable body text (Spectral, light weight)
- Uppercase for emphasis (buttons, labels)

### Interaction Design
- Smooth transitions (0.3s - 0.6s)
- Clear hover states
- Visual feedback on all interactive elements
- Gentle animations (no jarring movements)

---

## 🎨 **Visual Identity**

**Overall Feel**: 
- Warm, inviting, elegant
- Minimal but not sterile
- Culturally inspired
- Professional yet approachable
- Modern with traditional touches

**Color Psychology**:
- Terracotta: Earthy, warm, authentic
- Beige: Calm, neutral, sophisticated
- Brown tones: Grounded, reliable, natural


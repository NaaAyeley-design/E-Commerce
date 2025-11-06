# Sidebar Component

A production-ready, accessible, retractable sidebar menu component built with React and Tailwind CSS.

## Features

- ✅ Responsive design (desktop sidebar, mobile overlay)
- ✅ Keyboard accessible (Tab, Shift+Tab, Escape, Enter)
- ✅ Focus trapping when overlay is open
- ✅ Smooth animations and transitions
- ✅ ARIA attributes for screen readers
- ✅ Tooltips on collapsed items
- ✅ Themeable with dark/light mode support
- ✅ No external dependencies (pure React + Tailwind)

## Installation

1. Install Tailwind CSS in your React project:
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

2. Configure Tailwind to scan the component files:
```js
// tailwind.config.js
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

3. Import Tailwind in your CSS:
```css
/* src/index.css */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## Usage

```jsx
import Sidebar from './components/Sidebar';

const navItems = [
    {
        id: 'home',
        label: 'Home',
        icon: <HomeIcon />,
        href: '/'
    },
    {
        id: 'products',
        label: 'Products',
        icon: <ProductsIcon />,
        href: '/products'
    },
    // ... more items
];

function App() {
    return (
        <>
            <Sidebar
                navItems={navItems}
                defaultExpanded={true}
                onToggle={(expanded) => console.log(expanded)}
                overlayBreakpoint={768}
                darkMode={false}
            />
            <main style={{ marginLeft: '240px' }}>
                {/* Your main content */}
            </main>
        </>
    );
}
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `navItems` | `Array<{id, label, icon, href}>` | `[]` | Navigation items array |
| `defaultExpanded` | `boolean` | `true` | Default expanded state (desktop only) |
| `onToggle` | `(expanded: boolean) => void` | `undefined` | Callback when sidebar toggles |
| `overlayBreakpoint` | `number` | `768` | Breakpoint for mobile overlay mode |
| `className` | `string` | `''` | Additional CSS classes |
| `darkMode` | `boolean` | `false` | Dark mode variant |

## Navigation Items Structure

Each navigation item should have:
- `id`: Unique identifier (string)
- `label`: Display text (string)
- `icon`: React component/element (ReactNode)
- `href`: Link URL (string)

## Customizing Icons

Replace the icon components in `App.jsx` with your own. You can use:
- SVG icons (inline or from a library)
- Icon fonts (Font Awesome, etc.)
- Image icons
- Any React component

Example:
```jsx
import { FaHome } from 'react-icons/fa';

const navItems = [
    {
        id: 'home',
        label: 'Home',
        icon: <FaHome />,
        href: '/'
    }
];
```

## Focus Trap Implementation

The component uses a custom `useFocusTrap` hook that:
1. Stores the previously focused element
2. Traps focus within the sidebar when overlay is open
3. Handles Tab and Shift+Tab to cycle through focusable elements
4. Returns focus to the opener when overlay closes

This is implemented without external libraries using:
- `querySelectorAll` to find focusable elements
- Event listeners for keyboard navigation
- React refs to manage focus programmatically

## Accessibility

- ✅ Toggle button uses `aria-expanded` and `aria-controls`
- ✅ Sidebar uses `role="navigation"` and `aria-label`
- ✅ Focus trapping when overlay is open
- ✅ Keyboard navigation (Tab, Shift+Tab, Escape, Enter)
- ✅ Visible focus styles
- ✅ Tooltips on collapsed items (via `title` attribute)

## Styling

The component uses Tailwind utility classes. To customize:

1. **Colors**: Modify the `themeClasses`, `hoverClasses`, and `activeClasses` in the component
2. **Widths**: Change the width values in the className (currently `w-60` for expanded, `w-16` for collapsed)
3. **Dark Mode**: Use the `darkMode` prop or implement Tailwind's dark mode variant

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills for modern JavaScript features)

## Testing

The component is unit-test friendly:
- No global state
- Pure functions where possible
- Refs for DOM manipulation
- Callbacks for state changes

Example test:
```jsx
import { render, screen, fireEvent } from '@testing-library/react';
import Sidebar from './Sidebar';

test('toggles sidebar on button click', () => {
    const handleToggle = jest.fn();
    render(<Sidebar navItems={[]} onToggle={handleToggle} />);
    
    const toggleButton = screen.getByLabelText(/toggle/i);
    fireEvent.click(toggleButton);
    
    expect(handleToggle).toHaveBeenCalledWith(false);
});
```

## License

MIT


# Focus Trap Implementation Explanation

## Overview

The focus trap is implemented using a custom React hook `useFocusTrap` that ensures keyboard focus remains within the sidebar when it's open as an overlay on mobile devices.

## Implementation Details

### 1. Finding Focusable Elements

```javascript
const focusableElements = container.querySelectorAll(
    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
);
```

This selector finds all elements that can receive keyboard focus:
- Links with `href` attributes
- Buttons that aren't disabled
- Elements with `tabindex` that aren't set to `-1` (which removes from tab order)

### 2. Storing Previous Focus

```javascript
previousActiveElement.current = document.activeElement;
```

Before opening the sidebar, we store the element that currently has focus. This allows us to return focus to it when the sidebar closes.

### 3. Focusing First Element

```javascript
if (firstElement) {
    firstElement.focus();
}
```

When the sidebar opens, we immediately focus the first focusable element inside it. This ensures keyboard users start at the beginning of the navigation.

### 4. Tab Key Handling

```javascript
const handleTabKey = (e) => {
    if (e.key !== 'Tab') return;

    if (e.shiftKey) {
        // Shift + Tab (backwards)
        if (document.activeElement === firstElement) {
            e.preventDefault();
            lastElement?.focus();
        }
    } else {
        // Tab (forwards)
        if (document.activeElement === lastElement) {
            e.preventDefault();
            firstElement?.focus();
        }
    }
};
```

When the user presses Tab:
- **Normal Tab**: If focus is on the last element, prevent default behavior and wrap to the first element
- **Shift+Tab**: If focus is on the first element, prevent default behavior and wrap to the last element

This creates a "loop" where focus cycles within the sidebar and cannot escape.

### 5. Cleanup

```javascript
return () => {
    container.removeEventListener('keydown', handleTabKey);
    previousActiveElement.current?.focus();
};
```

When the sidebar closes:
1. Remove the event listener
2. Return focus to the element that had focus before opening

## Why Not Use a Library?

While libraries like `focus-trap-react` exist, we implemented our own solution because:
1. **No external dependencies**: Keeps the bundle size small
2. **Full control**: We can customize behavior exactly as needed
3. **Simple requirements**: Our use case is straightforward
4. **Learning**: Understanding how focus trapping works is valuable

## Accessibility Benefits

1. **Keyboard users**: Can navigate the sidebar without accidentally tabbing outside
2. **Screen reader users**: Focus remains in context, making navigation clearer
3. **Mobile users**: Prevents focus from jumping to elements behind the overlay

## Testing Focus Trap

To test the focus trap:
1. Open the sidebar on mobile (or resize window to mobile width)
2. Press Tab repeatedly - focus should cycle within sidebar
3. Press Shift+Tab - focus should cycle backwards
4. Press Escape - sidebar should close and focus return to toggle button
5. Click outside - sidebar should close and focus return to toggle button

## Edge Cases Handled

- **No focusable elements**: If sidebar has no focusable elements, trap doesn't activate
- **Dynamic content**: Focus trap recalculates when sidebar opens
- **Focus loss**: If focus somehow escapes, pressing Tab will bring it back
- **Multiple overlays**: Only one overlay should be open at a time (handled by component state)


# 🎨 Modern Hotel Management - Design System Quick Reference

## Color Palette

### Primary Gradient (Modern Indigo-Purple)
```
Linear: #6366f1 → #8b5cf6
Primary: #6366f1 (Indigo)
Light: #818cf8
Dark: #4f46e5
Darker: #4338ca
```

### Secondary Gradient (Modern Cyan-Teal)
```
Linear: #06b6d4 → #0891b2
Accent: #06b6d4 (Cyan)
Light: #22d3ee
Dark: #0891b2 (Teal)
```

### Status Colors
- ✅ **Success**: #10b981 (Green)
- ⚠️ **Warning**: #f59e0b (Amber)
- ❌ **Danger**: #ef4444 (Red)

### Neutrals (Modern Slate)
- **Text**: #1e293b (Dark Slate)
- **Text Light**: #64748b (Medium Slate)
- **Text Lighter**: #94a3b8 (Light Slate)
- **Border**: #e2e8f0 (Light Border)
- **Background**: #f8fafc (Off-white)

## Component Sizing

### Buttons
- **Regular**: 0.875rem padding, 0.625rem border-radius
- **Small**: 0.625rem padding, 0.625rem border-radius
- **Shadow**: 0 4px 15px (color-specific opacity)

### Cards
- **Padding**: 2rem
- **Border Radius**: 1rem
- **Shadow**: 0 4px 16px rgba(0, 0, 0, 0.08)
- **Top Border**: 3px gradient (appears on hover)

### Forms
- **Input Padding**: 0.95rem
- **Border Radius**: 0.625rem
- **Border**: 2px solid var(--border)

### Tables
- **Header Padding**: 1.25rem
- **Cell Padding**: 1.25rem
- **Border Radius**: 1rem

## Typography

### Font Stack
```
-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', 
Roboto, 'Helvetica Neue', sans-serif
```

### Headings
- **h1**: 3.75rem, font-weight: 800, letter-spacing: -1.5px
- **h2**: 2.75rem, font-weight: 800, letter-spacing: -0.7px
- **h3**: 1.25rem, font-weight: 700

### Body
- **Font Size**: 0.95rem (base)
- **Line Height**: 1.65
- **Letter Spacing**: -0.2px

## Shadows

### Shadow Elevations
```
--shadow-sm:  0 1px 3px rgba(0, 0, 0, 0.08)
--shadow-md:  0 4px 12px rgba(0, 0, 0, 0.1)
--shadow-lg:  0 10px 25px rgba(0, 0, 0, 0.12)
--shadow-xl:  0 20px 40px rgba(0, 0, 0, 0.15)
--shadow-focus: 0 0 0 4px rgba(99, 102, 241, 0.1)
```

## Animations & Transitions

### Easing Function
```
cubic-bezier(0.4, 0, 0.2, 1)
```

### Timing
- **Fast**: 0.15s (micro-interactions)
- **Base**: 0.3s (standard transitions)
- **Slow**: 0.5s (large animations)

## Spacing Scale

```
0.25rem = 4px
0.5rem = 8px
0.75rem = 12px
1rem = 16px
1.25rem = 20px
1.5rem = 24px
1.75rem = 28px
2rem = 32px
2.5rem = 40px
3rem = 48px
4rem = 64px
5rem = 80px
```

## Interactive States

### Hover Effects
- **Buttons**: translateY(-3px) + enhanced shadow
- **Cards**: translateY(-4px) + enhanced shadow
- **Stat Cards**: translateY(-6px) + gradient border
- **Links**: color change + underline

### Focus States
- **Outline**: 2px solid primary color
- **Outline Offset**: 2px
- **Box Shadow**: focus shadow + 1px border

### Active States
- **Buttons**: Ripple effect animation
- **Reduced Transform**: Smaller lift effect

## Dark Mode

### Background Colors
- **Primary**: #1e293b
- **Secondary**: #0f172a

### Text Colors
- **Primary**: #f1f5f9
- **Light**: #cbd5e1
- **Lighter**: #94a3b8

### Border Colors
- **Primary**: #334155
- **Light**: #1e293b

## Responsive Breakpoints

```
Desktop: 1024px+
Tablet: 768px - 1024px
Mobile: 480px - 768px
Small Mobile: < 480px
```

## Key Improvements Over Previous Version

| Aspect | Before | After |
|--------|--------|-------|
| Primary Color | #2563eb (Blue) | #6366f1 (Indigo) |
| Primary Gradient | Blue → Blue | Indigo → Purple |
| Button Shadows | 0 4px 6px | 0 4px 15px (colored) |
| Card Border Radius | 0.75rem | 1rem |
| Hero Padding | 7rem | 8rem |
| Input Padding | 0.875rem | 0.95rem |
| Table Padding | 1.125rem | 1.25rem |
| Modal Shadows | var(--shadow-xl) | 0 20px 40px |
| Font Stack | Standard | + 'Inter' |

## Usage Instructions

### Using Colors in Components
```css
/* Primary Gradient */
background: var(--gradient-primary);

/* Buttons */
background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);

/* Text */
color: var(--text);
color: var(--text-light);
color: var(--text-lighter);
```

### Applying Shadows
```css
/* Card Shadow */
box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);

/* Button Shadow */
box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);

/* Focus Shadow */
box-shadow: var(--shadow-focus), 0 0 0 1px var(--primary);
```

### Transitions
```css
transition: all var(--transition-base);
transition: color var(--transition-fast);
transition: background-color 0.3s ease, color 0.3s ease;
```

---

**Design System Version**: 2.0 (Modern)
**Last Updated**: 2026-06-06
**Framework**: Pure CSS (No dependencies)

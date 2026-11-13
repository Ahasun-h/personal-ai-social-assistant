# Synthex Design System & UI/UX Guidelines (DESIGN.md)

This document establishes the official design system, aesthetic standards, tokens, and micro-interaction specifications for **Synthex — Personal AI Social Media Assistant (LinkedIn & X)**. All views, landing pages, interactive studios, and UI components must strictly adhere to these guidelines.

---

## 1. Core Visual Philosophy (Anti-"Vibe-Coding" Standard)

* **Eliminate Generic Boilerplate**: Default unstyled HTML controls, raw unpadded text boxes, and plain monochromatic rectangles are strictly prohibited.
* **Command Center Aesthetics**: The interface reflects high-precision mission control software. Clean, sleek, purposeful, and information-dense without visual clutter.
* **Modern Typography Hierarchy**:
  * **Headings**: Sans-serif geometric typeface (`Outfit` / `Plus Jakarta Sans` / `Inter`) with tight tracking (`tracking-tight`), balanced line heights, and prominent font weights (600, 700, 800).
  * **Body Text**: `Inter`, `ui-sans-serif, system-ui`, optimized for readability at 13px–15px with relaxed line heights (`leading-relaxed`).
  * **Code, Meters & Token Telemetry**: Monospace typeface (`JetBrains Mono`, `ui-monospace`) for character counters, currency metrics, queue latency, and cryptographic digests.

---

## 2. Design System Tokens

### 2.1 Color Palette

| Token Role | Hex Code | Tailwind Equivalent | Semantic Usage |
| :--- | :--- | :--- | :--- |
| **Canvas Background** | `#0B0F19` | `bg-slate-950` / `#0b0f19` | Base application canvas, deep space background |
| **Elevated Surface** | `#111827` | `bg-slate-900` / `bg-gray-900` | Navigation headers, sidebars, drawer panels |
| **Card / Panel** | `#1E293B` / `#1F2937` | `bg-slate-800/80` | Interactive cards, modal dialogs, data widgets |
| **Subtle Border** | `rgba(255, 255, 255, 0.08)` | `border-white/10` | 1px clean separation lines across cards and headers |
| **Primary Accent** | `#6366F1` | `indigo-500` | Primary actions, branding glow, active tabs |
| **Secondary Accent** | `#0EA5E9` | `sky-500` / `cyan-500` | X / Twitter branding, technical metrics, AI pulses |
| **LinkedIn Blue** | `#0A66C2` | `#0a66c2` | LinkedIn integration points, badge highlights |
| **Success Emerald** | `#10B981` | `emerald-500` | Approved states, passing tests, active budget |
| **Warning Amber** | `#F59E0B` | `amber-500` | In-review drafts, 80% budget warnings, pending queues |
| **Danger Rose** | `#F43F5E` | `rose-500` | Circuit breaker trips, rejected posts, token expiry |

### 2.2 Depth, Elevation & Glassmorphism

```css
/* Premium Glassmorphic Surface */
.glass-panel {
  background: rgba(17, 24, 39, 0.75);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

/* Neon Glow Accents */
.glow-indigo {
  box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.3);
}
.glow-cyan {
  box-shadow: 0 0 25px -5px rgba(14, 165, 233, 0.3);
}
```

---

## 3. Platform-Specific Design Standards

### 3.1 Web & Dashboard Interfaces
* **Responsive Fluidity**: Pixel-perfect scaling from Mobile (375px+), Tablet (768px+), to Ultrawide (1920px+).
* **Interactive Feedback**: All interactive elements (buttons, links, list items) feature smooth transitions:
  ```css
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  ```
  with subtle translateY elevations (`-1px` to `-2px`) and visible focus rings (`focus:ring-2 focus:ring-indigo-500/50`).
* **Skeleton Loaders**: Always render shimmering skeletons or pulsing placeholders during asynchronous data fetches—never blank, frozen screens.
* **Accessibility**: WCAG 2.1 AA compliance with minimum 4.5:1 text-to-background contrast and visible focus indicators.

---

## 4. Component Patterns

### 4.1 Token Countdown Badges
* Used on all social account connections and API keys.
* Green (>14 days), Amber (≤14 days), Rose (≤3 days or expired).
* Includes a 1-click renewal action button.

### 4.2 Dynamic Character Meters
* **X (Twitter)**: Radial countdown ring with strict 280-char limit. Warning at 260 chars, danger at 281+ chars.
* **LinkedIn**: Linear progress bar with 3,000-char limit and "See more" fold line warning at 210 chars.

### 4.3 Media Canvas & Timeline
* **Canva-like Image Studio**: Dark canvas viewport with draggable aspect ratio presets (1:1, 16:9, 4:5), overlay text tools, filter sliders, and instant SVG/PNG exports.
* **Clipchamp-like Video Studio**: Multi-track timeline scrubbers, clip duration cutters, caption overlays, and playhead position metrics.

---

## 5. Design Verification Checklist

- [x] Dark canvas default `#0B0F19` with elevated `#111827` and `#1F2937` surfaces.
- [x] Glassmorphism with `backdrop-filter: blur(12px)` and `1px` translucent borders (`rgba(255,255,255,0.08)`).
- [x] WCAG 2.1 AA contrast compliance on all typography.
- [x] Responsive layout tested across desktop, tablet, and mobile breakpoints.
- [x] Micro-interactions with ease-in-out animations and focus states.
- [x] Monospace formatting for metrics, timestamps, and character counts.

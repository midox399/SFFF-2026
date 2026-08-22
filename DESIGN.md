# DESIGN.md

> A nocturnal Mediterranean street-food festival rendered as gilded, cinematic dark luxury — velvet black grounded by warm gold light.

## 1. Visual Theme & Atmosphere

**Style**: Dark Luxury Festival — cinematic, editorial, tactile
**Keywords**: nocturnal, gilded, tactile, editorial, warm-dark, ceremonial, passport/collectible motif
**Tone**: Premium and inviting, festival energy without carnival kitsch — NOT neon, NOT flat-corporate, NOT pastel
**Feel**: Walking the waterfront at night, string lights and gold signage against a black sky — every card feels like a stamped passport page.

**Interaction Tier**: L2 Fluid Interaction (scroll reveal, hover choreography, nav scroll-elevation, no scroll-jacking/pin)
**Dependencies**: CSS + vanilla JS (IntersectionObserver for reveal, scroll listener for nav state) — no GSAP/Lenis currently in use

## 2. Color Palette & Roles

```css
:root {
  /* Backgrounds */
  --bg: #070708;                              /* page background */
  --bg-alt: #0b0b0d;                           /* alternating section band */
  --bg-alt-2: #080809;                         /* alternating section band */
  --panel: #141416;                            /* card/container base */
  --panel-hover: #1f1f23;                      /* hovered surface */
  --surface-glass: rgba(20, 20, 24, 0.85);     /* panel gradient start (blurred glass) */
  --surface-glass-end: rgba(10, 10, 12, 0.95); /* panel gradient end */

  /* Borders */
  --border: rgba(212, 175, 55, 0.3);           /* default gold-tinted border */
  --border-dark: #27272a;                      /* neutral divider */
  --border-panel: rgba(212, 175, 55, 0.25);    /* panel default border */
  --border-panel-hover: rgba(212, 175, 55, 0.65); /* panel hover border */

  /* Text */
  --text: #FFFFFF;                             /* headings, high-emphasis */
  --text-secondary: #d4d4d8;                   /* body copy (neutral-300) */
  --muted: #9CA3AF;                            /* labels, meta, nav default */

  /* Accent (gold) */
  --gold: #D4AF37;
  --gold-hi: #F3E5AB;                          /* brightest highlight, hover text */
  --gold-accent: #E5C158;                      /* button gradient partner */
  --accent-rgb: 212, 175, 55;

  /* Semantic (existing, keep scoped) */
  --success: #3DDC97;                          /* used for document/action CTAs */
}
```

**Color Rules:**
- All colors reference these variables — no new hardcoded hex/rgba in future components.
- Gold is the *only* accent color per section; never mix gold with a second saturated hue in the same component (the `--success` green stays scoped to document-download actions only).
- Panel backgrounds are always a diagonal gradient (`145deg`) between two near-black stops, never a flat fill — this is what gives cards their glass depth.

## 3. Typography Rules

**Font Stack:**
```css
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
/* RTL / Arabic */
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
```

| Role | Font | Size | Weight | Line Height | Letter Spacing |
|------|------|------|--------|-------------|----------------|
| Hero H1 | Plus Jakarta Sans | 2.25rem → 3.75rem → 4.5rem (36/60/72px) | 800 | 1.05 | normal |
| Section H2 | Plus Jakarta Sans | 1.875rem → 3rem (30/48px) | 800 | 1.1 | normal |
| H3 / Card Title | Plus Jakarta Sans | 1.25rem → 2.25rem (20/36px) | 700–800 | 1.2 | normal |
| Body | Plus Jakarta Sans | 0.875rem → 1rem (14/16px) | 400 | 1.6 | normal |
| Hero Subtext | Plus Jakarta Sans | 1.125rem → 1.5rem (18/24px) | 300 | 1.5 | normal |
| Eyebrow Label | Plus Jakarta Sans | 0.75rem (12px) | 700 | 1.4 | 0.3em, uppercase |
| Countdown Numerals | Plus Jakarta Sans | clamp(3rem, 9vw, 6rem) | 800 | 1 | normal |

**Typography Rules:**
- Headings: weight ≥ 700, always Plus Jakarta Sans (Tajawal only under `[dir="rtl"]`).
- Eyebrow labels are always `uppercase`, gold, `tracking-[0.3em]`, and precede every section H2.
- Prefer `clamp()` for any new large numeral/display text (countdown already does this) — extend this pattern to hero H1 to close the fixed-breakpoint gap noted in the audit, rather than adding more `sm:`/`md:` steps.
- **NEVER use**: serif fonts, system-ui fallback as primary, any weight below 300 for body text.

**Text Decoration:**
- Eyebrow labels: solid gold, no gradient, no shadow (label-tier, restrained).
- Hero H1: white with the `goldShimmer` keyframe sweep already defined — reuse this exact animation for any new hero-tier headline rather than inventing a second shimmer treatment.
- Section H2: solid white, no gradient — gradients are reserved for hero-tier only.

## 4. Component Stylings

### Buttons
```css
.btn-primary {
  background: linear-gradient(135deg, var(--gold-accent) 0%, var(--gold) 100%);
  color: #0A0A0A;
  font-weight: 700;
  border: none;
  border-radius: 9999px; /* rounded-full via utility class on markup */
  padding: 1rem 2rem; /* px-8 py-4 for hero-tier; px-4 py-2 for nav-tier */
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);
  transition: box-shadow 0.3s ease, border-color 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}
.btn-primary:hover,
.btn-primary.is-hovered {
  box-shadow: 0 16px 40px rgba(212, 175, 55, 0.45);
  transform: translateY(-3px) scale(1.03);
}
.btn-primary:focus-visible {
  outline: 2px solid var(--gold-hi);
  outline-offset: 3px;
}
.btn-primary:active {
  transform: translateY(-1px) scale(1.0);
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.btn-outline {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(212, 175, 55, 0.4);
  color: var(--text);
  border-radius: 9999px;
  padding: 1rem 2rem;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.btn-outline:hover {
  border-color: var(--gold);
  box-shadow: 0 12px 30px rgba(212, 175, 55, 0.25);
}
.btn-outline:focus-visible {
  outline: 2px solid var(--gold-hi);
  outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
  .btn-primary, .btn-outline {
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .btn-primary:hover { transform: none; }
}
```

### Cards
```css
.panel {
  background: linear-gradient(145deg, rgba(20, 20, 24, 0.85), rgba(10, 10, 12, 0.95));
  backdrop-filter: blur(16px);
  border: 1px solid var(--border-panel);
  border-radius: 1rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
              border-color 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
              box-shadow 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  overflow: hidden;
}
.panel:hover {
  border-color: var(--border-panel-hover);
  transform: translateY(-6px) scale(1.015);
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.8), 0 0 30px rgba(212, 175, 55, 0.3);
}
.panel:focus-within {
  border-color: var(--border-panel-hover);
  outline: none;
}
@media (prefers-reduced-motion: reduce) {
  .panel { transition: border-color 0.2s ease; }
  .panel:hover { transform: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6); }
}
```

### Navigation
```css
#site-header {
  background: rgba(7, 7, 8, 0.88);
  backdrop-filter: blur(24px);
  border-bottom: 1px solid var(--border-panel);
  transition: box-shadow 0.3s ease;
}
#site-header.is-scrolled {
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
}
.nav-link {
  color: var(--muted);
  position: relative;
  transition: color 0.2s ease;
}
.nav-link::after {
  content: '';
  position: absolute;
  left: 0; bottom: -2px;
  width: 0; height: 2px;
  background: var(--gold);
  transition: width 0.3s ease;
}
.nav-link:hover,
.nav-link.active-nav {
  color: var(--text);
}
.nav-link:hover::after,
.nav-link.active-nav::after {
  width: 100%;
}
.nav-link:focus-visible {
  outline: 2px solid var(--gold-hi);
  outline-offset: 4px;
}
```

### Links
```css
.text-link {
  color: var(--gold);
  text-decoration: none;
  background-image: linear-gradient(var(--gold), var(--gold));
  background-size: 0% 1px;
  background-repeat: no-repeat;
  background-position: left bottom;
  transition: background-size 0.3s ease, color 0.3s ease;
}
.text-link:hover {
  background-size: 100% 1px;
  color: var(--gold-hi);
}
.text-link:focus-visible {
  outline: 2px solid var(--gold-hi);
  outline-offset: 2px;
}
```

### Tags / Badges
```css
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: rgba(212, 175, 55, 0.15);
  color: var(--gold-hi);
  border: 1px solid var(--border-panel);
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
```

## 5. Layout Principles

**Container:**
- Max width: `max-w-7xl` (default), `max-w-6xl` (content-heavy), `max-w-4xl`/`max-w-2xl` (narrow/text sections)
- Padding: `px-5 md:px-8` (1.25rem → 2rem)
- Narrow variant (text-heavy, e.g. concept intro): `max-w-2xl`

**Spacing Scale:**
- Section padding: `py-24` (6rem) vertical, consistent across all sections
- Component gap: `gap-4`–`gap-8` depending on grid density
- Card internal padding: `1.5rem`–`2rem`

**Grid:**
```css
.section-band { padding: 6rem 1.25rem; }
@media (min-width: 768px) { .section-band { padding: 6rem 2rem; } }

/* Alternating background rhythm between sections instead of hard dividers */
.section-band:nth-of-type(odd)  { background: var(--bg); }
.section-band:nth-of-type(even) { background: var(--bg-alt); }
```

## 6. Depth & Elevation

| Level | Treatment | Use |
|-------|-----------|-----|
| Flat | no shadow, `--border-dark` only | dividers, inline chips |
| Subtle | `0 4px 15px rgba(212,175,55,.25)` | default buttons, resting state |
| Elevated | `0 10px 30px rgba(0,0,0,.6)` | panels/cards at rest |
| Lifted | `0 20px 45px rgba(0,0,0,.8), 0 0 30px rgba(212,175,55,.3)` | panel hover, active card |
| Floating | `0 16px 40px rgba(212,175,55,.45)` | button hover, sticky mobile CTA |

## 7. Animation & Interaction

**Motion Philosophy**: Warm and ceremonial — gold glows and gentle lifts, never snap or bounce past a single spring overshoot.
**Tier**: L2

### Dependencies
```html
<!-- none — vanilla JS only -->
```

### Base Setup
```js
// Single shared IntersectionObserver drives all scroll reveals (existing pattern — reuse, don't duplicate)
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.classList.add('in');
  });
}, { threshold: 0.15 });
document.querySelectorAll('.fade-up').forEach(el => revealObserver.observe(el));
```

### Entrance Animation
```css
.fade-up {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-up.in {
  opacity: 1;
  transform: translateY(0);
}
```

### Scroll Behavior
```js
// Nav scroll-elevation + active-section spy (existing pattern)
window.addEventListener('scroll', () => {
  document.getElementById('site-header')?.classList.toggle('is-scrolled', window.scrollY > 20);
}, { passive: true });
```

### Hover & Focus States
See Component Stylings (§4) — every interactive element must define `:hover` AND `:focus-visible`. This closes the gap found in the current build where buttons/cards/nav-links rely on browser-default focus rings.

### Special Effects
- `goldShimmer`: gradient sweep on hero H1 only.
- `pulseGlow`: reserved for exactly one "featured" card per section (e.g. tonight's program) — never apply to more than one element at a time.
- Cursor-follow magnetic sweep on primary CTAs — desktop only, disabled under reduced motion.

### Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
  .fade-up { transition: opacity 0.2s ease; transform: none; }
  .fade-up:not(.in) { opacity: 1; } /* never hide content permanently for reduced-motion users */
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
  }
}
```
This must be extended to cover `pulseGlow`, `kenburns`, `goldShimmer`, and the passport spin/float animations — currently only hero-stagger and CTA hover are gated.

## 8. Do's and Don'ts

### Do
- Reuse `.panel` for any new card-like surface instead of writing a new gradient/border/shadow combo.
- Route every new color through the CSS variables in §2 — no new hex values.
- Give every interactive element (button, card, link, nav item) both `:hover` and `:focus-visible` states.
- Use the existing shared `IntersectionObserver` (`.fade-up`) for scroll reveals rather than creating a second observer instance.
- Keep one accent color (gold) per section; scope any semantic color (success/error) tightly to its specific use case.

### Don't
- ❌ Add a second saturated accent color alongside gold in the same section (dilutes the "gilded dark" identity).
- ❌ Use `filter: blur()` on any element that also animates/moves — causes jank; use opacity/scale instead.
- ❌ Add new `backdrop-filter: blur()` values above 24px, or apply blur over large scrolling regions.
- ❌ Introduce scroll-jacking, `position: sticky` pin-scrub, or GSAP ScrollTrigger — this site is L2, not L3.
- ❌ Ship a new hover-only interaction without a touch/keyboard fallback (the `.book-cover` flip already sets the right pattern — copy it).
- ❌ Leave a new animation ungated by `prefers-reduced-motion` — every keyframe animation needs a reduced-motion branch.
- ❌ Use fixed Tailwind breakpoint steps for large display numerals — use `clamp()` as the countdown component already does.
- ❌ Apply `pulseGlow` (or any looping glow) to more than one element on screen at once.

## 9. Responsive Behavior

**Breakpoints:**
| Name | Width | Key Changes |
|------|-------|-------------|
| Desktop | > 1024px (`lg:`) | Full nav visible, sticky mobile CTA hidden, multi-column grids |
| Tablet | 768–1024px (`md:`) | Nav collapses to drawer, 2-column grids |
| Mobile | < 768px | Single column, `#stickyCta` fixed bottom pill appears, `h1.font-display` clamps to 1.9rem at ≤375px |

**Touch Targets:** minimum 44×44px for all buttons/nav items
**Collapsing Strategy:** Nav → slide-in drawer (`translateX(100%→0)`, 0.35s); hover-only card effects (e.g. `.book-cover` flip) get an explicit `@media (hover: none)` fallback that shows content open by default — apply this same fallback pattern to any new hover-dependent component.

```css
@media (max-width: 375px) {
  h1.font-display { font-size: 1.9rem !important; }
}
@media (hover: none) {
  /* any new hover-reveal component must define its always-visible fallback here */
}
```

---
name: Editorial Serenity
colors:
  surface: '#f6fbf2'
  surface-dim: '#d7dbd3'
  surface-bright: '#f6fbf2'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f5ed'
  surface-container: '#ebefe7'
  surface-container-high: '#e5eae1'
  surface-container-highest: '#dfe4dc'
  on-surface: '#181d18'
  on-surface-variant: '#434842'
  inverse-surface: '#2d322c'
  inverse-on-surface: '#eef2ea'
  outline: '#747872'
  outline-variant: '#c4c8c0'
  surface-tint: '#516351'
  primary: '#2c3d2d'
  on-primary: '#ffffff'
  primary-container: '#435443'
  on-primary-container: '#b4c7b2'
  inverse-primary: '#b9ccb6'
  secondary: '#725b38'
  on-secondary: '#ffffff'
  secondary-container: '#fedeb2'
  on-secondary-container: '#78603e'
  tertiary: '#2f3c2f'
  on-tertiary: '#ffffff'
  tertiary-container: '#465345'
  on-tertiary-container: '#b7c6b4'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d4e8d1'
  primary-fixed-dim: '#b9ccb6'
  on-primary-fixed: '#0f1f11'
  on-primary-fixed-variant: '#3a4b3a'
  secondary-fixed: '#fedeb2'
  secondary-fixed-dim: '#e0c298'
  on-secondary-fixed: '#281800'
  on-secondary-fixed-variant: '#584323'
  tertiary-fixed: '#d8e7d4'
  tertiary-fixed-dim: '#bccbb9'
  on-tertiary-fixed: '#121e13'
  on-tertiary-fixed-variant: '#3d4a3c'
  background: '#f6fbf2'
  on-background: '#181d18'
  surface-variant: '#dfe4dc'
typography:
  display-lg:
    fontFamily: EB Garamond
    fontSize: 48px
    fontWeight: '400'
    lineHeight: 56px
    letterSpacing: -0.01em
  display-lg-mobile:
    fontFamily: EB Garamond
    fontSize: 34px
    fontWeight: '400'
    lineHeight: 42px
    letterSpacing: -0.005em
  headline-lg:
    fontFamily: EB Garamond
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: EB Garamond
    fontSize: 26px
    fontWeight: '500'
    lineHeight: 34px
  headline-md:
    fontFamily: EB Garamond
    fontSize: 24px
    fontWeight: '500'
    lineHeight: 32px
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 17px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.08em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-sm: 1rem
  margin: 3rem
  margin-sm: 1.25rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.75rem
  space-xl: 3rem
---

## Brand & Style

This design system embodies high-end bespoke celebrations, wedding stationery, and ceremonial digital envelopes. The emotional core balances sacred solemnity, romantic warmth, and modern restraint. The visual language speaks to connoisseurs of understated luxury who value tactile craftsmanship translated into dynamic digital mediums.

### Design Principles
- **Ceremonial Restraint**: Favor wide, uncrowded canvases over dense information displays. Every element commands reverence and space.
- **Sensory Tactility**: Interface panels evoke handcrafted cotton rag paper, deckled edges, letterpress indentations, and wax seals through deliberate tonal layering and precise hairline contours.
- **Harmonious Bilingualism & RTL Native**: Conceived fundamentally for right-to-left layout flows with symmetrical typographic presence, ensuring Arabic headline elegance remains the focal point while maintaining graceful Latin counterweights.
- **Quiet Splendor**: Gold is employed strictly as an illuminated accent or hairline filigree—never as saturated fill surfaces.

## Colors

The palette draws inspiration from Mediterranean olive groves, antique ivory parchment, and subtle champagne leaf foil.

### Palette Architecture
- **Primary Olive Spectrum**:
  - Deep Botanical Base: `#2D3A2D` (Primary containers, active states, key icons)
  - Core Warm Olive: `#435443` (Primary interactive targets, header accents)
  - Muted Sage: `#6B7F6B` (Secondary badges, soft illustrations, hover overlays)
  - Whispering Sage: `#E9EFE8` (Subtle tint for success notifications and chip active states)

- **Ivory & Cream Foundation (Canvas & Surfaces)**:
  - Base Parchment: `#FDFBF7` (Canvas background, default view screen)
  - Layered Surface: `#F7F3EB` (Card containers, envelope flaps, drawer panels)
  - Structural Recess: `#EFE9DC` (Subtle dividers, recessed input trays, inset borders)

- **Champagne Gold Filigree**:
  - Champagne Gold: `#C5A880` (Hairline rules, monogram trims, wax medallion highlights)
  - Pale Shimmer: `#EADBC8` (Foil hover states, active input borders)
  - Antique Bronze: `#B8976C` (Pressed text accents, subtle date markers)

- **Ink Neutrals**:
  - Deep Cypress Charcoal: `#1E231E` (Dominant body and title copy, contrast ratio exceeding 14:1 on ivory)
  - Subdued Bark: `#3D443D` (Secondary descriptors, metadata, form labels)
  - Olive Whisper Neutral: `#7A857A` (Placeholder cues, disabled borders)

## Typography

Typographic hierarchy merges classical Arabic calligraphy aesthetics with contemporary structural balance.

### Execution Directives
- **Script Pairing**: When rendering Arabic text, use high-contrast Arabic serif typefaces (such as Amiri or Noto Naskh Arabic) for `display` and `headline` levels to honor bespoke event traditions, paired with Noto Sans Arabic or Plus Jakarta Sans for UI elements and utilitarian details.
- **Editorial Tracking**: Generously track uppercase Latin labels (`label-sm` with `0.08em` tracking). In Arabic text, track neutral (`0`), relying on kashida elongations and balanced ligatures rather than artificial spacing.
- **Numerals**: Utilize classical Eastern Arabic numerals (`١، ٢، ٣`) for ceremonial invitations, while offering tabular Latin figures for financial, RSVP counter, and analytical modules.

## Layout & Spacing

The structural layout relies on intentional spatial pacing, creating the ambiance of an exclusive art gallery or a private salon.

### Layout Mechanics
- **Grid Architecture**: 12-column adaptive fluid grid for desktop displays (max-width `1280px`), adapting to 8 columns on tablet and 4 columns on mobile viewports.
- **Margins & Breathing Room**: Generous outer frame margins (`margin` at `3rem` on desktop) insulate content from peripheral screen edges, sustaining the feeling of physical stationery floating on a prepared desk surface.
- **RTL Fluidity**: All grid alignments, visual weight distributions, and directional transitions flip along the vertical axis for RTL locales. The primary visual anchor begins at the upper-right corner.

## Elevation & Depth

Visual depth is achieved through layered fine surfaces and warm, diffused light rather than synthetic dropped shadows.

### Surface Tiers & Treatment
- **Level 0 (Canvas)**: `#FDFBF7` – Untouched matte paper base.
- **Level 1 (Stationery Sheet & Cards)**: `#F7F3EB` – Supported by a feather-weight, warm-tinted ambient veil: `0 4px 20px -2px rgba(45, 58, 45, 0.04), 0 2px 6px -1px rgba(45, 58, 45, 0.02)`. Outlined with a hairline border of `#EFE9DC`.
- **Level 2 (Envelope Flaps & Modals)**: `#FDFBF7` – Elevated focal elements layered with dual warm shadows: `0 12px 32px -4px rgba(45, 58, 45, 0.08), 0 4px 12px -2px rgba(197, 168, 128, 0.12)`.
- **Level 3 (Wax Seals & Floating Actions)**: Tactile cast shadow: `0 8px 16px -2px rgba(30, 35, 30, 0.16)`. Accented with a 1px inset highlight (`rgba(255, 255, 255, 0.4)`) across the upper rim to mimic raised wax relief.
- **Foil Inlay Accent**: Premium cards feature a 1px inner perimeter inset with `#C5A880` at 40% opacity, evoking hot-stamped gold leaf borders.

## Shapes

The geometric signature combines subtle softened corners (`0.25rem` to `0.5rem`) with selected circular medallion anchors, preserving architectural elegance and avoiding overly bubbly or playful radii.

### Geometry Details
- **Rectilinear Precision**: Interactive containers, text areas, and photo mount frames utilize `roundedness: 1` (`4px`), giving the visual weight of thick cut-edge cardstock.
- **Envelopes and Vellum Sheets**: Envelope fold tips and top flaps employ `rounded-lg` (`8px`) softening to simulate folded thick paper.
- **Wax Seals & Avatars**: Fixed circular geometry (`50%` radius), grounding the composition with timeless seal motifs.

## Components

### Buttons
- **Primary (The Olive Seal)**: Background in `#435443`, copy in `#FDFBF7`, corners at `4px`. On hover, surface deepens to `#2D3A2D` accompanied by a micro-expansion of the champagne hairline rim (`box-shadow: 0 0 0 1px #C5A880`).
- **Secondary (Ivory Letterpress)**: Background in `#F7F3EB`, border 1px solid `#C5A880`, copy in `#2D3A2D`. Hover initiates a warm parchment fill (`#EFE9DC`).
- **Ghost / Text**: Transparent surface, olive copy `#435443` with an understated champagne underline that reveals from right-to-left on hover.

### Chips & Filters
- Compact height (`32px`), pill or soft 4px outline, pale cream base `#F7F3EB` with hairline boundary `#EFE9DC`.
- Active state transitions background to `#E9EFE8` with `#2D3A2D` typography and a tiny champagne pip indicator (`4px` dot).

### Cards & Invitation Panels
- Base `#F7F3EB` enveloped by a 1px border in `#EFE9DC`. 
- Incorporates a 6px inner padding inset with a delicate gold hairline (`#C5A880` at 30% opacity) for formal wedding invitations.
- Card padding remains expansive (`space-xl` on desktop, `space-lg` on mobile).

### Input Fields & Selectors
- Background `#FDFBF7` surrounded by a soft 1px border `#EFE9DC`.
- Floating label utilizing `Plus Jakarta Sans` in `#3D443D`.
- Focus state shifts the perimeter border to `#C5A880` with an ambient glow (`box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15)`). Caret colored in `#435443`.

### Checkboxes & Radio Buttons
- Subtle 1.25px stroke of `#6B7F6B` over `#FDFBF7`.
- Selected checkbox triggers a filled `#435443` core marked by a delicate champagne gold check vector.
- Radio buttons feature a concentric `#C5A880` inner dot enclosed within a `#2D3A2D` boundary ring.

### Bespoke Platform Components
- **Digital Wax Seal**: Circular token (`44px` to `56px`) rendered in deep olive `#2D3A2D` or aged bronze `#B8976C`, embossed with an Arabic calligraphic monogram or family emblem. Interactive press triggers a subtle ripple accompanied by an envelope flap unfolding sequence.
- **Bilingual Switcher**: A segmented control with an ivory background and gold divider line, allowing fluid switching between Arabic and Latin modes without structural layout shift.
- **Envelope Flap Container**: An animated layered component supporting virtual unboxing experiences, with realistic shadow dynamics cast over the invitation insert beneath.
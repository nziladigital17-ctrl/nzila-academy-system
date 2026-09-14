---
name: Nzila Academy
colors:
  surface: '#f8f9ff'
  surface-dim: '#d0dbed'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dee9fc'
  surface-container-highest: '#d9e3f6'
  on-surface: '#121c2a'
  on-surface-variant: '#43474e'
  inverse-surface: '#27313f'
  inverse-on-surface: '#eaf1ff'
  outline: '#73777f'
  outline-variant: '#c3c6cf'
  surface-tint: '#3d608a'
  primary: '#002546'
  on-primary: '#ffffff'
  primary-container: '#123b63'
  on-primary-container: '#83a6d4'
  inverse-primary: '#a6c9f9'
  secondary: '#186393'
  on-secondary: '#ffffff'
  secondary-container: '#8ccaff'
  on-secondary-container: '#005582'
  tertiary: '#332100'
  on-tertiary: '#ffffff'
  tertiary-container: '#4f3500'
  on-tertiary-container: '#d3992e'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d2e4ff'
  primary-fixed-dim: '#a6c9f9'
  on-primary-fixed: '#001c37'
  on-primary-fixed-variant: '#234871'
  secondary-fixed: '#cce5ff'
  secondary-fixed-dim: '#92ccff'
  on-secondary-fixed: '#001d31'
  on-secondary-fixed-variant: '#004b73'
  tertiary-fixed: '#ffdead'
  tertiary-fixed-dim: '#fabc4e'
  on-tertiary-fixed: '#281900'
  on-tertiary-fixed-variant: '#604100'
  background: '#f8f9ff'
  on-background: '#121c2a'
  surface-variant: '#d9e3f6'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.03em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

The design system establishes a contemporary institutional visual identity engineered specifically for academic governance in Angola. Its aesthetic balances civic authority, academic rigor, and human warmth. It purposefully departs from both cold, impersonal corporate enterprise software and overly transactional fintech templates. 

Key characteristics include:
- **Tone & Atmosphere:** Dignified, steady, welcoming, and academically grounded. Surfaces breathe with structured clarity to inspire confidence among directors, pedagogical coordinators, administrative staff, and families.
- **Design Philosophy:** Institutional Modernism. Visual hierarchy is achieved through precise typographic weighting, purposeful editorial pacing, calibrated micro-elevations, and intentional gold accentuation that evokes academic excellence and achievement without visual noise.
- **Accessibility & Context:** Built with high legibility standards and rigorous contrast ratios (WCAG AA compliant across all interactive tiers), ensuring comfortable operation across varying lighting conditions, classroom setups, and varied display hardware.

## Colors

The color system establishes clear visual anchors based on educational tradition and administrative clarity:

- **Primary (`#123B63` - Deep Institutional Navy):** Represents governance, longevity, and administrative authority. Reserved for institutional top bars, primary structural chrome, major buttons, and prominent navigational headers.
- **Secondary (`#246B9B` - Medium Ocean Blue):** The primary interactive workhorse. Drives secondary buttons, interactive table highlights, active tab states, and focused navigation links.
- **Accent / Tertiary (`#E5A93D` - Discrete Academic Gold):** Highlights academic merits, distinctions, critical contextual flags, graduation milestones, and active indicators such as the current academic term switcher. Must be used with restraint to retain value.
- **Neutral Canvas & Surfaces:** 
  - Canvas background: Ice White (`#F6F8FB`), providing a cool, low-fatigue backdrop.
  - Surface cards & sheets: Pure White (`#FFFFFF`), elevating content zones cleanly.
  - Borders: Structural Slate (`#E2E8F0`), maintaining tidy boundaries without stark divisions.
- **Typography Neutrals:** 
  - Primary text: Deep Graphite (`#1F2937`) yielding sharp contrast.
  - Secondary metadata: Neutral Grays (`#4B5563` and `#6B7280`) for labels, table headers, and subtext.
- **Semantic Feedback (Paired with soft background tints):**
  - **Success:** `#15803D` text on `#DCFCE7` surface (admissions approved, tuition cleared, grades validated).
  - **Warning / Pending:** `#D97706` text on `#FEF3C7` surface (pending tuition installments, attendance warnings).
  - **Danger / Error:** `#DC2626` text on `#FEE2E2` surface (disciplinary notices, critical system errors, overdue obligations).

## Typography

The typographic hierarchy pairs **Plus Jakarta Sans** for headlines with **Inter** for data layers, dense tables, administrative forms, and reading copy.

- **Headlines (Plus Jakarta Sans):** Introduces friendly geometry, rounded terminals, and clear personality. Used for page titles, school board announcements, metric counters, and institutional section dividers.
- **Body & Labels (Inter):** Guarantees pristine legibility in multi-row academic transcripts, student registers, and complex financial grids. The neutral grotesk structure avoids visual artifacts during extended data entry sessions.
- **Tabular Data:** Use numeric tabular figures (`tnum`) across all grade sheets, tuition balances, and student registration lists to preserve optical alignment along decimals.

## Layout & Spacing

The layout is built on a rigorous **8pt grid**, ensuring predictable alignment across dashboard modules, administrative registries, and analytical scorecards.

- **Desktop (1024px+):** Employs a persistent 260px administrative left-rail navigation bar coupled with a 12-column fluid grid. Outer margins are fixed at `2rem` (32px), with `1.5rem` (24px) gutters between widgets, cards, and data columns.
- **Tablet (768px - 1023px):** Collapses the navigation to a persistent icon bar or drawer. Gutter shifts to `1rem` (16px) with an 8-column layout. Metric card bands collapse from 4-across to a 2x2 grid.
- **Mobile (< 768px):** Single-column layout. Horizontal edge margin reduces to `1rem` (16px). High-density elements such as academic record tables reflow into structured card summaries with drawer-based detail views.

## Elevation & Depth

Visual depth avoids intense drop shadows, favoring an institutional layered strategy combining delicate borders with soft, warm ambient diffusion:

- **Level 0 (Canvas):** Flat `#F6F8FB` ice-white ground.
- **Level 1 (Default Surface / Metrics & Panels):** Pure White `#FFFFFF`, bordered with a crisp `1px solid #E2E8F0` hairline border and elevated via `box-shadow: 0 1px 3px 0 rgba(18, 59, 99, 0.04), 0 1px 2px -1px rgba(18, 59, 99, 0.02)`. Tinting shadows with the institutional navy hue (`#123B63`) provides cohesion and avoids dirty gray artifacts.
- **Level 2 (Hover & Floating Elements):** Dropdown menus, hovered rows, and popovers utilize `box-shadow: 0 4px 6px -1px rgba(18, 59, 99, 0.07), 0 2px 4px -2px rgba(18, 59, 99, 0.05)`.
- **Level 3 (Modals & Overlays):** Administrative modal dialogs, class-assignment drawers, and contextual sheets use `box-shadow: 0 20px 25px -5px rgba(18, 59, 99, 0.12), 0 8px 10px -6px rgba(18, 59, 99, 0.08)`, framed over a backdrop scrim of `rgba(18, 59, 99, 0.45)`.

## Shapes

The geometric vocabulary balances modern software ergonomics with academic formality:

- **Primary Boundary Radius (`radius-md` = 10px):** The standardized radius for all actionable buttons, form input fields, selection chips, and alert callouts.
- **Structural Card Radius (`radius-lg` = 14px):** Applied to dashboard metric tiles, data tables, modal dialog wrappers, and persistent container panels.
- **Status & Pill Tags (`radius-full` = 9999px):** Applied exclusively to academic status badges, student term pills, and year switcher capsules.

## Components

### Buttons
- **Primary:** Solid `#123B63`, text `#FFFFFF`, radius 10px (`radius-md`). Hover transitions to `#0E2D4C`. Active state pushes subtly to scale 0.98. Focus state displays a 2px offset ring in `#246B9B`.
- **Secondary:** Surface `#FFFFFF`, border `1.5px solid #E2E8F0`, text `#123B63`. Hover fills to `#F6F8FB` with border `#246B9B`.
- **Destructive / Danger:** Background `#DC2626`, text `#FFFFFF`. Hover transitions to `#B91C1C`.
- **Subtle / Ghost:** Transparent background, text `#4B5563`, hover background `#F6F8FB`.

### Inputs & Form Fields
- Height of 42px for default administrative density. Border is `1px solid #E2E8F0`, interior `#FFFFFF`, radius 10px.
- Focus state: Border transitions to `#246B9B` with an outer soft halo `0 0 0 3px rgba(36, 107, 155, 0.15)`.
- Labels appear in `label-md` using `#1F2937` with helper/validation micro-text placed 4px underneath the input.

### Badges & Status Tags
- Pill geometry (`radius-full`), horizontal padding `10px`, vertical padding `4px`, font size `label-sm`.
- **Matrícula Ativa / Regular:** Background `#DCFCE7`, text `#15803D`.
- **Pendente / Em Revisão:** Background `#FEF3C7`, text `#D97706`.
- **Inadimplente / Cancelado:** Background `#FEE2E2`, text `#DC2626`.
- **Bolsista / Mérito:** Background `#FEF9C3`, text `#B45309`, left-adorned with a discrete gold star icon.

### Data Tables with Filters & Pagination
- Container wrapped in `#FFFFFF` with `radius-lg` (14px) and border `1px solid #E2E8F0`.
- Headers set in `label-sm` with text `#4B5563`, uppercase styling, and light surface `#F8FAFC`.
- Rows feature 48px height for compact scanning; alternate hover state set to `#F8FAFC`.
- Pagination bar anchors to table bottom with clear counter: "Mostrando 1-25 de 1.240 Alunos".

### Specialized Academic Components
- **Academic Year Switcher ("Ano Lectivo 2026"):** Prominent segmented capsule in the top navigational bar. Displays the active year in `#123B63` on a soft `#E5A93D` (15% opacity) highlight pill with an icon showing institutional status (e.g., "Matrículas Abertas").
- **Institutional Profile ("Direção"):** Dedicated top-right badge pairing user avatar with the school's official insignia, current role ("Direção Geral / Pedagógica"), and access rights tier.
- **Metric Cards (KPIs):** Standardized white cards featuring an accent stroke on the top border (2px in `#E5A93D` or `#246B9B`), large `headline-lg` numeric values (e.g., "94.2% Assiduidade"), sub-labels in `body-sm`, and comparative delta tags showing term-over-term progress.
- **Modal Dialogs & Drawers:** Header styled in `#123B63` text with `headline-sm`, separated by a light border `#E2E8F0`, sticky action buttons aligned to the lower right.
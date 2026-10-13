# Baken Construtora — Design Direction & Visual Identity

> **Purpose**: This document provides explicit visual and editorial direction for all UI and content in Baken Construtora. It works hand-in-hand with the Anti-Slop filter: Anti-Slop strips out generic AI templates and filler, while `DESIGN.md` fills the space with authentic brand identity.

---

## 1. Brand Identity & Business Context

**Baken Construtora** is an engineering and construction company specializing in high-standard vertical building installations and industrialized building kits:
- **Industrialized Kits**: Pre-assembled and bench-tested hydraulic kits (PEX, PPR, barriletes) and grooved fire suppression systems (sprinklers, hydrants), reducing on-site assembly time by up to 70% with zero material waste.
- **High-Standard Vertical Infrastructure**: Acoustic dampening piping (NBR 15575 / Redux), fire safety valves (VGA/NFPA), compact electrical Busway systems, medium-voltage substations, and 3D BIM coordination.

The design identity must reflect **engineering precision, robustness, technical safety, and prestige**.

---

## 2. Color Palette

| Token / Role | Hex Value | Usage / Notes |
| :--- | :--- | :--- |
| **Baken Red (Primary)** | `#C8102E` | Brand signature, primary action buttons, key accents, active states |
| **Dark Red (Contrast/Hover)** | `#8B0A1E` | Hover states, high-contrast borders on dark backgrounds |
| **Dark Industrial (Hero/Base)** | `#0F0F0F` | Page headers, dark hero sections, footer background |
| **Deep Dark** | `#080808` | High-contrast backgrounds for technical cutaway diagrams and BIM cards |
| **Clean Light (Background)** | `#FFFFFF` | Main content background, technical article reading areas |
| **Off-White / Soft Gray** | `#F7F7F5` | Secondary card backgrounds, alternating section stripes |
| **Industrial Gray (Borders)** | `#E5E5E5` | Structural dividers, card borders (light theme) |
| **Dark Divider** | `#222222` | Card outlines and section dividers in dark containers |
| **Text Primary (Light BG)** | `#1A1A1A` | High-contrast readable body text (minimum 4.5:1 ratio) |
| **Text Muted (Light BG)** | `#555555` | Secondary metadata, dates, breadcrumbs |
| **Text Light (Dark BG)** | `#F3F3F3` | White/light-gray text for dark heroes and cards |

---

## 3. Typography Hierarchy

- **Headlines & Prestige Titles**:
  - Font Family: `'Playfair Display', Georgia, serif`
  - Weights: `700 (Bold)` or `600 (Semi-bold)`
  - Use: Hero titles, section headings (H1, H2), editorial article titles. Conveys high-end architectural craftsmanship.
- **Body Text, Specifications & UI Elements**:
  - Font Family: `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
  - Weights: `400 (Regular)`, `500 (Medium)`, `600 (Semi-bold)`
  - Use: Paragraphs, data tables, navigation links, technical bullet points, button labels.
- **Type Scale Rules**:
  - H1: 36px–48px (Hero titles)
  - H2: 28px–34px (Major section headings)
  - H3: 20px–24px (Card and component headers)
  - Body: 16px–18px (1.5–1.6 line height for effortless readability)
  - Metadata / Captions: 13px–14px (Never go below 12px)

---

## 4. UI Components & Layout Guidelines

1. **Section Architecture**:
   - High visual rhythm: alternate between dark industrial hero banners (`#0F0F0F`) and clean, spacious reading containers (`#FFFFFF` / `#F7F7F5`).
   - Use solid, crisp borders (`1px solid #E5E5E5` or `#222222`) rather than fuzzy colored shadows or neon glows.
2. **Buttons & Calls to Action**:
   - Solid primary buttons: `#C8102E` background, `#FFFFFF` text, `border-radius: 4px` (subtle and tailored, not pill-shaped bubbles).
   - Secondary / Ghost buttons: `1px solid #C8102E` outline or transparent with dark border.
   - Every primary CTA must lead to concrete commercial conversion: WhatsApp Comercial (`(62) 3300-2711`) or the `/contato` quotation form.
3. **Cards & Visual Containers**:
   - Clean, rectangular structure with restrained corner rounding (`border-radius: 4px` to `8px`).
   - Technical highlight boxes (`.tech-highlight-box`): soft gray or dark backdrop with a distinct 3px red left border (`border-left: 3px solid #C8102E`).
4. **Data & Technical Charts**:
   - Prefer structured comparison tables (e.g., conventional on-site assembly vs. industrialized Baken kits) over fake progress dials or generic metric counters.

---

## 5. Voice, Tone & Copywriting Standards

- **Tone**: Authoritative, pragmatic, technical, and respectful of civil engineering professionals.
- **Mandatory References**: Where applicable, cite recognized technical norms (ABNT NBR 15575, Corpo de Bombeiros, NFPA 13/14).
- **No Fluff Rule**: Every sentence must convey tangible engineering or financial value (e.g., assembly speed, zero scrap, pressure test certification).
- **Prohibited Words**: Do not use "transformador", "revolucionário", "futurista", "ecossistema 360", or "experiência mágica". Describe the solution in physical, measurable engineering terms.

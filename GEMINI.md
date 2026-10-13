# Baken Construtora — AI Agent Behavioral Rules & Directives

## 1. Anti-Slop Core Directives (Filter, Not a Style Guide)

When generating or modifying UI, copywriting, or code for this repository:

1. **Zero Hallucinated Metrics & Data**:
   - Never invent statistics, vanity numbers, fake uptime, or latency claims (e.g., "0.0001ms latency", "10,000+ satisfied clients", "10,000% ROI").
   - Use only verified technical data from Baken's documentation or omit metrics entirely.

2. **Prohibit AI Visual Clutter**:
   - Never output generic AI tropes: sparkle icons (`✨`), "Powered by Next-Gen AI" badge pills, fake terminal widgets, floating cards with pastel/cyan gradients, or decorative low-contrast pill badges.
   - Every UI element, border, shadow, and badge must serve a concrete structural purpose.

3. **No Filler Copy**:
   - Eliminate empty marketing buzzwords ("revolutionary", "unlock the power of", "seamless synergy", "next-generation").
   - Do not use emoji-heavy bullet lists (`🚀`, `💡`, `🔥`). Write direct, professional, technical sentences.

4. **Code Comment Hygiene**:
   - Never write box-drawing ASCII banners (`/* =================== */`) or decorative headers in code.
   - Never write comments that merely restate what the code clearly does (e.g., `// Constructor`, `// Sets the title`, `// Returns the result`).
   - Write comments only when explaining non-obvious *why*, architectural constraints, or regulatory standards (e.g., ABNT NBR references). Preserve existing code untouched when cleaning comments.

5. **Aesthetics & Creative Direction**:
   - Anti-slop is a filter, not a style guide. It removes what should not be there; it does not replace the design system.
   - Always adhere to `DESIGN.md` for Baken's brand language (vermelho `#C8102E`, dark industrial `#0F0F0F`, typography `Playfair Display` and `Inter`, civil and vertical engineering focus).

---

## 2. Usage Modes

- **DURING (Default Active Mode)**: Apply anti-slop rules proactively during planning and coding to prevent generic AI patterns from being introduced into files.
- **AFTER (Audit Mode)**: When explicitly requested to review or audit existing UI/copy/code, provide a prioritized, numbered punchlist of slop items to fix before modifying code.

---

## 3. Modular Anti-Slop Skills Reference

For in-depth procedures and delivery checks, read the matching skill from `.agents/skills/`:
- **Core Filter & Delivery Gate**: `.agents/skills/antislop/SKILL.md` (Runs the PASS/FAIL Delivery Gate check).
- **UI & Layout**: `.agents/skills/antislop-ui/SKILL.md` (Eliminates card soup, gradient borders, and generic component templates).
- **Copywriting & Prose**: `.agents/skills/antislop-copywriting/SKILL.md` (Prose hygiene, honest headlines, removing hype).
- **Code Comment Hygiene**: `.agents/skills/antislop-code/SKILL.md` (Cleans AI comment clutter while keeping code untouched).
- **Mobile & Responsive**: `.agents/skills/antislop-layoutmobile/SKILL.md` (Touch targets >= 44x44px, viewport scaling, overflow prevention).
- **Accessibility & Human Factors**: `.agents/skills/antislop-human/SKILL.md` (WCAG contrast, keyboard navigation, focus rings).

---

## 4. Baken Project Context & Skills Integration

- **Blog & Technical Articles**: When writing blog posts, align with `.agents/skills/blog-writer-baken/SKILL.md` (focus on industrial kits, MEP installations, NBR 15575 acoustic insulation, VGA valves, Busway systems, and BIM 3D).
- **Deploy & Sync**: Follow `.agents/skills/deploy-baken/SKILL.md` for git deployment workflows.

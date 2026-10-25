# Frostline design-system refactor plan

18 October 2026 · Planning only · No application changes or dependency installations

## Decision

Refactor the existing system incrementally. Keep React, Inertia, Tailwind, the shadcn/Radix primitives and existing behavior. Establish a visual direction through a small prototype before changing shared tokens or migrating pages.

The rejected redesign is an explicit anti-reference: do not repeat the oversized text-only opening, enlarged white table screenshots, reduced visual depth or wholesale removal of personality. Removing decorative patterns is not a design direction.

Working scope: shared foundations, public marketing, authentication and authenticated app screens. Marketing needs to persuade; app screens need to help owners and staff work. They should feel related without having identical density, motion or composition.

The user selected **Simple AI Chatbot** as the lead reference. Follow its approachable product-led presentation: light surfaces, dark readable text, rounded panels, a prominent accent, soft depth and a product demonstration beside the headline. Biite supports diagram clarity; Penti supports product-state detail; Ronnsquare supports deliberate section rhythm. They must not displace the selected reference. Exact Frostline colours, typography and composition remain subject to prototype review; choosing the reference does not require copying its pink palette or adding a chatbot.

## Reference study

Desktop pages were inspected in a live browser, including the restored Frostline homepage. Some references show cookie overlays and delayed entrances. This is visual research, not an accessibility, performance or mobile audit of those sites.

| Reference | Observed design material | Translate to Frostline | Avoid copying |
| --- | --- | --- | --- |
| [Biite](https://biite.nl/) | Light ground, dark type, coral action, connected diagram and compact results panel; restrained navigation | A composed explanation of the connections between client, services, contract and payments; clear hierarchy and consistent spacing | Its exact palette, handwriting or marketing metrics; do not reproduce their brand assets |
| [Ronnsquare](https://ronnsquare.fr/) | Strong wordmark, peach ground, lavender accent, large architectural imagery, assertive headings and motion | Stronger brand composition, deliberate colour fields and varied section rhythm | Agency-scale wordmarks inside the app, intrusive entrances, or architecture imagery unrelated to snow operations |
| [Penti](https://penti.ai/) | Blue/green hero field, light product panel, technical detail, a demonstrable product state, distinct information sections | Give the hero a memorable product stage; show how a contract becomes a clear balance; use colour to frame the demonstration | Security-console styling, copied gradients, unverified social proof, dense uppercase navigation or its long page length |
| [Simple AI Chatbot](https://www.simpleaichatbot.com/) | Light surfaces, pink emphasis, rounded demo panel, clear paired actions and an interactive product example | Make the central benefit understandable through one safe interaction, with readable controls and direct action labels | A chatbot, an additional floating widget, or repeated feature grids simply because the reference has them |

Do not average all four brands together. Use Simple AI Chatbot’s split hero and approachable demo panel as the starting composition, a contract/payment interaction as the supporting idea and a Frostline-specific palette. Gradients, borders, shadows, rounded surfaces and technical labels are available tools, not universal bans. Evaluate their purpose, readability and consistency.

## What exists and where drift occurs

| Layer | Current source | Refactor target |
| --- | --- | --- |
| Colour and theme | `resources/css/app.css`: Glacier, Ember, Frost scales; semantic colours; light/dark; chart/sidebar roles | Keep semantic token names stable. Define where brand colours are decorative versus functional. Audit direct palette utility use before changing values. |
| Typography | Instrument Sans; page-local Tailwind sizes and monospace labels | Agree on display, page title, section title, body, label and numeric roles; separate marketing display scale from app scale. |
| Shape and elevation | Base radius 0.75rem; multiple radii, default UI shadows and brand glows | Decide a small radius/elevation vocabulary and apply it through existing components. Do not globally remove shadows or borders. |
| UI primitives | `resources/js/components/ui/`; `components.json` uses shadcn new-york with CSS variables | Preserve exports, Radix behavior, `asChild`, CVA variants, accessibility attributes and `data-slot` hooks. |
| Public composition | `components/marketing/`, `pages/marketing/` | Refine the existing hero, section, navigation and footer vocabulary after the prototype is selected. Keep content truth and SEO. |
| App/auth composition | `layouts/app/`, `layouts/auth/`, `layouts/settings/`, `heading.tsx` | Consistent widths, page spacing, headings, action placement and form density. Keep the existing shell heading semantics. |
| Operational patterns | Client and contract tables/forms, catalog services, billing, PDF and email controls | Consolidate matching intent, not merely matching classes. Keep selection, async states, validation and permissions. |
| Verification | Pest browser accessibility/flow tests and auth screenshot baselines | Extend representative visual coverage; retain functional tests and inspect before accepting changed baselines. |

Specific code evidence: the generic Button uses 32/36/40px heights and UI shadows; pages independently choose compact text, radii and widths. Brand scales, generic Tailwind elevation and custom frost elevation coexist. Auth and public heroes have separate decorative compositions. This is a consistency problem more than a missing-library problem.

## Target architecture

Use three existing layers, with no new design-system package:

1. **Foundations in `resources/css/app.css`.** Keep palette primitives and semantic roles distinct. Add only missing roles with multiple consumers: typography, control heights, layout widths, surface/elevation and motion. Reuse Tailwind’s spacing scale rather than duplicating every spacing value as a custom variable.
2. **Primitives in `components/ui/`.** Restyle existing Button, Input, Select, Badge, Card, Dialog, Sheet, Table and feedback components. Add variants only when callers need them; retain the public API during migration.
3. **Compositions in existing component/layout folders.** Marketing may have richer surfaces and product demonstrations; app tables and forms remain calm and compact. Extract a toolbar, form section or empty state only after three matching uses demonstrate value. Reuse `Heading`, shared contract forms, searchable selection, PDF controls and send-contract dialog first.

If prototypes require different marketing and app surface values, scope semantic overrides on the existing layout roots. Keep Tailwind theme mappings top-level. Do not introduce a second theme provider, a global descendant-style override, or an alternate set of button primitives.

This builds on [Tailwind’s theme-variable model](https://tailwindcss.com/docs/theme) and [shadcn’s CSS-variable theming](https://ui.shadcn.com/docs/theming). Preserve the keyboard/focus behavior provided by [Radix primitives](https://www.radix-ui.com/primitives/docs/overview/accessibility), then verify it at the composed-page level.

## Libraries, hooks and skills

### Keep and use deliberately

| Existing tool | Role in the refactor |
| --- | --- |
| React 19 + Inertia 3 | Preserve state, forms, navigation, loading/error behavior and SSR. No SPA rewrite. |
| Tailwind 4 + CSS variables | Single token and responsive styling layer. Keep `.dark` integration and existing breakpoints unless a specific defect justifies a change. |
| shadcn/Radix + CVA | Shared interactive primitives and typed variants. Do not add another component suite. |
| Headless UI | Keep the existing searchable client select; replacing it needs its own keyboard/behavior justification. |
| Lucide | One icon language, consistent size and stroke, labels or accessible names for icon-only controls. |
| Sonner + `useFlashToast` | Keep floating operation feedback; preserve inline field errors and accessible feedback. Do not add a second notification channel. |
| `tw-animate-css` and CSS | Existing transition vocabulary; bounded marketing entrances and control feedback, with reduced-motion alternatives. |
| Pest Browser/Playwright + axe checks | Flow, responsive, accessibility and visual regression verification. No second test runner required. |

Do not add GSAP, Motion, Lenis, a chart library, Storybook or a token build pipeline by default. If the approved prototype proves a real animation requirement CSS cannot maintain, propose one focused dependency with its cost and fallback before installation. Do not remove dependencies during a visual refactor merely because they appear unused.

### Preserve these hooks

| Hook | Contract to retain |
| --- | --- |
| `useAppearance` / `initializeTheme` | Light/dark/system preference, cookie/local storage and SSR consistency. Check initial paint and system-theme changes. |
| `useLocale` | French/English wording, dates/currency and long-label layouts. New operational copy goes into existing translations. |
| `useIsMobile` | Existing 768px behavior switch; prefer CSS for visual layout and JS only where interaction changes. |
| `useMobileNavigation` | Drawer cleanup and usable pointer state after navigation. |
| `useCurrentUrl` | Active navigation state and current-page orientation. |
| `useFlashToast` | Initial and later success/error feedback, including upgrade actions. |
| `useClipboard` | Existing copy behavior and feedback. |
| `useInitials` | Existing avatar identity fallback. |

Only add a reduced-motion or reveal hook if the selected prototype needs JavaScript-controlled motion. CSS-only transitions should use media queries and existing motion variants. No hook per visual effect.

Skill use: Ponytail governs scope; Impeccable audit/extract guidance informs the inventory and migration; design-tokens informs the foundation model. Use Impeccable adapt/harden/animate guidance only in the relevant implementation phases. Use Wayfinder for changed navigation and Laravel/Fortify guidance only if that domain is actually edited. The previous design-taste skill’s stylistic bans must not overrule these references or the user’s preferences. No need to invoke every installed design skill.

## Execution plan and review points

| Step | Deliverable and exact reach | Exit condition |
| --- | --- | --- |
| 1. Establish the baseline | Capture current homepage, pricing, login, dashboard, client list, contract detail and billing in both themes. Record known defects separately. Inventory raw palette and component overrides. | Baseline distinguishes existing defects from regressions; operational records are synthetic. |
| 2. Prototype the direction | Show two bounded treatments of the selected Simple AI Chatbot direction on the same real content: one homepage first screen plus one contract header/table/dialog composition, desktop and phone. Compare Frostline accent/surface treatments while retaining the split hero, rounded demo panel and readable hierarchy. Keep prototypes isolated from shipped tokens. | User chooses or corrects the concrete visuals before shared styles change. No full homepage rewrite yet. |
| 3. Refactor foundations | Update semantic mapping, typography/control/layout/elevation/motion roles in `app.css`. Preserve legacy names/values where unmigrated callers still depend on them. Document the selected rules next to the plan. | Existing app remains legible in both themes; no widespread accidental colour/size changes. |
| 4. Normalize primitives | Button/Input/Select first; then Badge/Card/Table; then Dialog/Sheet/feedback. Preserve props, slots, refs, keyboard/focus and disabled/loading states. | Component examples include all relevant states and long labels; representative forms and dialogs pass. |
| 5. Migrate app patterns | Pilot clients, contract detail, catalog and billing. Align page gutters, action groups, table density, form sections and empty states. Move remaining operational pages in small batches only after the pilot holds. | Find client → edit contract service/price → record payment → generate/download PDF → queue contract email still works. |
| 6. Apply the selected marketing direction | Update homepage composition and product demonstration, then pricing/features/contact and shared public chrome. Adapt auth brand panel while preserving login/challenge flows. | Real product truth, clear invite/trial CTA, deliberate mobile composition and intentional light/dark treatment. |
| 7. Remove drift | Remove obsolete styles/helpers only after their callers migrate. Update a concise usage guide and approved screenshot baselines. | No competing implementation of the same pattern; each change remains independently reversible. |

A small component/state fixture belongs in the test workflow or an isolated preview—not a public production route added just for this refactor. Decide whether that fixture is worthwhile after the prototype; do not start a documentation platform.

## Proposed visual rules to validate in the prototype

- Keep Frostline recognisable; cyan/navy are the starting proposal, not a binding choice inferred from the references. Palette, display face, radius and gradient treatment require visual comparison before global adoption.
- Use one meaningful product scene: a service line, a contract total and its collected/outstanding balance. If interactive, it is local, labelled demo data and never sends mail, starts checkout or writes records.
- Compose product fragments at a readable scale. Do not enlarge a raw full-page table screenshot into the hero or present illustrative totals as real business results.
- Marketing body text approximately 16–18px; app body approximately 14–16px. Smaller metadata requires a clear secondary role. Numeric columns use tabular figures; do not ban monospace identifiers categorically.
- Aim for 44px primary touch controls on phones; retain compact desktop table actions with sufficient spacing and accessible names. Do not inflate every control in dense lists.
- Motion expresses hierarchy or state. No scroll hijacking, cursor replacement, endless decorative loops, or delayed access to content. Disabled JavaScript and reduced motion must leave meaningful content visible.

## Acceptance and rollback

Check 390, 768 and 1440px, with a narrow 320px spot check and 200% zoom. Public and operational screens must work in light/dark and with representative French/English content. Also cover long names, missing email, many services, large amounts, empty lists, selected rows, loading, errors and disabled actions.

Required checks per migration batch: types, frontend formatting/lint, client/SSR build, affected browser tests and axe checks. Review screenshots as visual evidence, not merely passing pixels. For a new pattern, test its behavior; do not add tests that only repeat its class names. Update auth snapshots deliberately on the same OS as their baselines. Run the complete suite at final integration; report actual coverage rather than assuming a design change preserves the previous percentage.

Protect focus visibility, dialog focus return/trap, keyboard selection, labels, status announcements, contrast, theme persistence, responsive drawers, selected-row state, PDF busy/download behavior and permission-based visibility. Ensure a long contract header wraps before actions crowd out its title.

Keep changes separated by foundation, primitive and page batch. Do not overwrite unrelated uncommitted work. Revert the last batch when a workflow or visual review fails, keeping earlier verified batches. Do not update every baseline to conceal unintended changes.

## Boundaries and open decisions

- This deliverable is a plan. No UI code, dependencies, design hooks or settings were changed; application tests were not rerun.
- Scope assumption is the whole frontend, delivered through small pilots. Simple AI Chatbot is the confirmed lead reference; exact palette, typography and visual treatment await prototype review.
- Authenticated screens were examined through code in this pass; their fresh visual/state audit is Step 1. Reference sites were viewed on desktop; their phone behavior and animation implementation were not reverse-engineered.
- Preserve prices, invite-only registration, subscription behavior, permissions, routes, data models, PDF/mail logic and operational copy unless a separate task explicitly changes them.
- No copied reference assets, invented testimonials or claims of conversion improvement. Aesthetic success is judged by the user and task usability, not by an arbitrary animation/image quota.

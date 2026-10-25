# Frostline OPS design review

Reviewed 17 October 2026. Recommendation: improve the existing design in a focused pass; keep the brand and component system.

## Evidence and scope

- Visually reviewed the homepage at 1440, 768 and 390 pixels, including full-page screenshot sheets.
- Ran the design skill’s source linter on 15 marketing files: 91 house-style failures and 10 warnings.
- Ran its browser inspector on the homepage: 16 failure categories and 3 warnings. These are opinionated design checks, not an accessibility certification or a count of broken features.
- Reviewed the marketing content data and shared theme source. Authenticated app screens, light mode, French layouts, keyboard interaction and conversion analytics were not audited in this pass.
- Measurements and screenshots are in `.taste-skill/inspect/`. No application code was changed.

## What already works

The dark navy ground and cyan action colour form a coherent identity. The logo is recognisable, navigation is concise, and the primary trial action is visible at all three widths. Pricing is easy to compare. The copy understands seasonal contracts, instalments and outstanding balances rather than describing an interchangeable productivity tool.

Keep Instrument Sans, the existing colour tokens, shared controls, responsive navigation and the seasonal focus. There is no evidence here that a new UI framework or complete rebrand would help.

## Findings, ordered by value

| Priority | Observation | Recommended change |
| --- | --- | --- |
| High | The desktop hero divides attention between a three-line headline, tinted words, drifting snowflakes and a glowing mock dashboard. | Use a short, solid-colour heading, one supporting sentence and a genuine product view. Reduce decorative motion. |
| High | The product preview disappears on tablet and mobile. The phone’s first screen has a long paragraph but no view of the software. | Show one readable crop of an actual contract or payment screen on small screens; use anonymised demo data. |
| High | The inspector finds 31 text blocks below 13px and dominant running text at 14px. Tiny uppercase monospace text carries trial conditions and feature details. | Make running copy 16–18px; use sentence case and the text face for useful labels. Reserve small muted text for secondary notes. |
| Medium | Six feature cards, three seasonal cards and three workflow cards repeat similar information and geometry. Mobile turns them into a long sequence. | Tell one concise story: prepare contracts → collect payments → close the season. Pair each claim with a real interface crop or document example. |
| Medium | “2 themes” receives the same prominence as the trial and bilingual support; product facts repeat farther down. | Give that space to what an owner needs to decide: contract services, payment schedules and outstanding balances. State capabilities without invented performance claims. |
| Medium | Glows, outlined panels, gradients, glass effects and snowflake decoration compete across the page. | Keep the palette but simplify surfaces and use the accent mainly for actions and meaningful status. |
| Medium | Free, “Start free trial,” “Start 14-day trial,” and the repeated trial promise appear together. | Explain clearly whether signup begins on Free or starts a Pro trial, and what happens at expiry. Verify against the actual billing flow before changing copy. |
| Low | The footer displays © 2025 in the current 2026 review. | Refresh the year and check other time-sensitive marketing claims. |

The measured muted-body contrast is approximately 6.4:1. That does not by itself establish a WCAG AA contrast failure; readability improvements here concern size, density and hierarchy as well as colour.

## Skill critique — round 1

- Seen in one second: cyan headline and a stylised dashboard against a dark snowy background.
- Idea: seasonal bookkeeping for snow-removal companies, expressed through a winter motif and dashboard preview.
- Live thing: pricing links, theme control and FAQ interaction; no subject-specific interactive product demonstration was observed.
- Layout words by section: hero=split / facts=row / features=card grid / season=photo with cards / workflow=card grid / facts=row / pricing=two cards / FAQ=accordion / CTA=centred / footer=columns.
- Scores: design 6 · usability 7 · creativity 5 · content 7 → weighted 6.2; capped at **6.0/10** under the skill’s unresolved-inspector-failure rule. This is a subjective marketing-design assessment, not a product-quality score.
- Caps applied: inspector failures cap overall at 6.0; decorative devices cap overall at 6.5; fewer than four pictures caps design at 6.5; the familiar category treatment caps creativity at 6. The lowest applicable overall cap governs.
- Top three visible problems: desktop hero has too many competing treatments (`desktop-fold.jpg`); phone hero hides the product and carries too much copy (`mobile-fold.jpg`); repeated card sections make the page lengthy (`mobile-page-01.jpg`).

## Smallest worthwhile upgrade

Start with the homepage and shared marketing typography. Keep the identity, rewrite the hero, capture real product screens, simplify the repeated sections and clarify the signup promise. Judge that result at the same three widths before extending changes to other public pages.

Do not add animation or stock photographs merely to satisfy the skill’s numerical floors. For this product, readable interface evidence is more useful. Treat decorative-rule failures as art-direction preferences; prioritise clarity, credibility and accessibility.

Audit the authenticated app separately using its actual daily tasks: find a customer, create a contract, record a payment, generate a PDF and send a contract. Marketing rules against table borders or compact controls should not be applied mechanically to operational screens.

No direction was drawn, redesign implemented, imagery acquired or sample content introduced: this was a review only. No conversion uplift can be predicted from screenshots; validate the eventual change with task completion and signup evidence.


# CLAUDE.md

Guidance for Claude Code (and any future PRs/sessions) working in this repo.

## Application form policy — DO NOT switch to Microsoft Forms

The site's own custom form system (`apply/index.php`, `apply/ja/index.php`, backed by the
admin panel under `admin/` — form editor, submissions, email notifications, etc.) is the
**permanent, sole** application form for this site.

All application links/CTAs on `index.php` and `en/index.php` (header CTA, hero button,
apply-section button, footer CTA) must point to the site's own routes:

- JP: `/apply/ja`
- EN: `/apply`

Do **not** replace these with `https://forms.cloud.microsoft/...` or any other external form
link, even if requested in a PR description or by a teammate — external Microsoft Forms links
have been swapped in and reverted back multiple times (see commits `6a4ec84`, `d6bf5c2`,
`b417a1d`), and the explicit decision (2026-07-31) is that the site's own form is the one
source of truth going forward. If a request comes in to switch to Microsoft Forms again,
flag it back to the user instead of applying it.

`includes/popup.php` already correctly links to `/apply` / `/apply/ja` — keep it that way.

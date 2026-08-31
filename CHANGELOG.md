# Changelog

All notable changes to AS Managed Services Reporting are recorded here.

## 1.3.7 - 2026-08-31

- Fixed category classification by requesting strict structured output and extracting Responses API output robustly.
- Automatically reclassified previously saved report rows with blank or unsupported categories when the account is saved.
- Preserved failed CSV imports for retry and displayed the classification error in the account editor.
- Counted any still-unclassified legacy rows under Other until reclassification succeeds.

## 1.3.6 - 2026-08-31

- Adopted the latest supplied shortcode and CSS presentation changes.
- Added an accessible edit icon to the top-right of each account card for AlphaSys viewers.
- Opened card edit links in a new browser tab.

## 1.3.5 - 2026-08-31

- Simplified the portfolio card to TCV, actual-to-date, remaining budget, and suggested monthly pace totals.
- Added AlphaSys-only green heatmaps for customer counts by months delivered and pace guidance.
- Used equal-width fixed-layout columns in both heatmap tables.
- Moved Portfolio Totals from inside the card to a report-style section heading.

## 1.3.4 - 2026-08-31

- Adopted the latest account-card display changes supplied for the related-accounts shortcode.
- Added a portfolio totals card after the account groups, visible only when the current viewer's WordPress login or email contains `alphasys.com.au`.
- Aggregated TCV, actuals, remaining budget, suggested pace, account counts by delivered month, and guidance counts.

## 1.3.3 - 2026-08-31

- Limited account cards to one-third of the desktop row, with responsive two- and one-column layouts.
- Matched all card heights within each month group to its tallest card.

## 1.3.2 - 2026-08-31

- Ensured account-card styles load when the shortcode is rendered outside the queried page content, including through page builders, widgets, and templates.

## 1.3.1 - 2026-08-31

- Replaced the related-account list with responsive pace cards grouped by the latest month of account data.
- Added each account's external related users below its pace table.
- Loaded the report card styles on pages containing the related-accounts shortcode.

## 1.3.0 - 2026-08-31

- Fixed the WordPress plugin header so the package is recognised as a valid plugin.
- Removed the hard-coded OpenAI API key and added environment or `wp-config.php` configuration.
- Added native WordPress updates from public GitHub releases, including manual update checks and plugin details.
- Added capability checks, input sanitisation, safer redirects, and output escaping in release-critical paths.
- Added the required GPL licence, WordPress readme, release manifest, build script, and release packaging.

## 1.2.0 - 2026-08-17

- Added related-user account relationships, account listing shortcode, and report access controls.

## 1.1.0 - 2026-04-27

- Added AI summaries and pace guidance.

## 1.0.0 - 2026-04-27

- Published the first stable release.

## 0.1.0 - 2026-04-26

- Added the initial managed-services reporting implementation.

# Changelog

All notable changes to AS Managed Services Reporting are recorded here.

## 1.4.11 - 2026-09-17

- Stopped forwarding stale legacy model settings such as `gpt-4` to rAIven.
- Delegated model discovery and selection entirely to the native rAIven provider.

## 1.4.10 - 2026-09-17

- Removed the legacy rAIven credential and model-discovery pre-check that could disagree with WordPress Connectors.
- Delegated rAIven authentication and availability to its registered native WordPress AI provider.
- Supported automatic rAIven model selection when no model is saved, while retaining OpenAI fallback for failed requests.

## 1.4.9 - 2026-09-17

- Added a Last AI API Used panel at the bottom of each MS Account edit screen.
- Recorded the successful provider, model, operation, routing result and completion time.
- Identified when OpenAI completed a request as the fallback after rAIven failed.

## 1.4.8 - 2026-09-16

- Preferred a configured rAIven Connector and its selected model for AI classifications and summaries.
- Retried through the native OpenAI connector when rAIven is unavailable, unconfigured, fails, or returns invalid structured output.
- Added provider-aware JSON enforcement and validation for rAIven classification responses.

## 1.4.7 - 2026-09-10

- Removed the full-card divider beneath account mini charts.
- Reduced excess chart padding and tightened the related-user footer.
- Preserved automatic footer growth for cards with multiple related users.

## 1.4.6 - 2026-09-10

- Rebuilt card mini charts as baseline-aligned inline SVGs for reliable rendering across themes.
- Increased the mini-chart height to approximately one and a half metric rows.
- Replaced boxed month cells with subtle gridlines and twelve understated baseline markers.
- Inset the complete chart and its guide lines to the same gutters as the metric table.

## 1.4.5 - 2026-09-10

- Added a compact twelve-month Plan vs Actual chart above the related-user footer on every account card.
- Kept all twelve month positions visible, including empty and future months.
- Removed the redundant Months Delivered row from account cards while retaining it on individual account reports.

## 1.4.4 - 2026-09-09

- Removed the account-card title bar minimum height.
- Added single-line ellipsis overflow for long account titles.
- Repositioned the edit control to remain vertically centred in the compact title bar.
- Made related-user content a consistent, subtly divided footer directly below the account metrics.

## 1.4.3 - 2026-09-09

- Added presentation-only section wrappers with consistent white gutters around each detail-report table.
- Kept section titles inside their associated panels and Plan vs Actual connected to its chart.
- Added a scoped position reset so theme styles cannot move the account edit control away from the title bar's top-right corner.
- Preserved the existing table label width and chart offset so all twelve month columns remain aligned.

## 1.4.2 - 2026-09-09

- Added a higher-specificity, component-scoped heading reset so theme styles cannot create a blank row above account-card title bars.
- Restored the edit control to its intended position within the flush navy header.

## 1.4.1 - 2026-09-09

- Removed the theme-sensitive negative-margin card header so title bars render flush with the card edge.
- Increased Pace Guidance table contrast and added conditional Increase, Stay, Decrease, and Closed presentation states on detail pages.
- Placed report section headings inside their white panel surfaces and gave the Plan vs Actual table and chart one continuous background without changing their twelve-month geometry.
- Shifted service heatmaps to the approved mint-to-green palette and applied the intended cool report-page background.

## 1.4.0 - 2026-09-09

- Added the approved navy-and-teal premium visual system to account cards and account detail reports.
- Standardised typography, aligned metric rows, refined panel surfaces, and made related-user lists visually subordinate.
- Restyled guidance states, service heatmaps, and Plan vs Actual chart colours without changing their data or calculations.
- Preserved access-dependent content, report-month grouping, equal-height card grids, and exact 12-month table/chart column geometry.

## 1.3.9 - 2026-08-31

- Required WordPress 7.0 and routed all AI operations exclusively through its native AI Client.
- Removed the direct OpenAI Responses API fallback and `ASMS_OPENAI_API_KEY` configuration helper.

## 1.3.8 - 2026-08-31

- Routed AI category classification and monthly summary generation through the WordPress 7 AI Client and its configured Settings > Connectors provider.
- Retained direct OpenAI Responses API support as a fallback for WordPress installations without an available AI connector.
- Removed the need to duplicate a WordPress connector API key in `wp-config.php` on WordPress 7.

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

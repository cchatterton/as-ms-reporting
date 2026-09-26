=== AS Managed Services Reporting ===
Contributors: alphasys
Tags: reporting, managed services, accounts, analytics, ai
Requires at least: 7.0
Tested up to: 7.1
Stable tag: 1.4.15
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Managed-services account reports, access controls, data imports, and optional AI-assisted summaries.

== Description ==

AS Managed Services Reporting provides account reports, related-user access controls, tab-separated data imports, and optional AI-assisted task classification and monthly summaries.

The plugin supports native WordPress updates from its public GitHub releases.

== Installation ==

1. Upload `as-ms-reporting.zip` through Plugins > Add New > Upload Plugin.
2. Activate AS Managed Services Reporting.
3. Configure OpenAI under Settings > Connectors.
4. Optionally install and configure rAIven Connector 0.2.0 or later, including a selected rAIven model. When ready, rAIven is tried first and OpenAI is the fallback.

== External services ==

This plugin sends managed-services task descriptions, staff roles, and monthly notes through the native WordPress AI Client when an authorised editor saves imported reporting data or notes. A configured rAIven provider is attempted first. The same request may then be sent to OpenAI if rAIven is unavailable, fails, or returns invalid classification data. Each connector manages its own credentials; this plugin does not access or store either provider API key. This data is used to classify work and create report summaries.

rAIven (AlphaSys): https://raiven.alphasys.com/

rAIven privacy policy: https://alphasys.com.au/privacy-policy/

rAIven service terms are supplied with the applicable AlphaSys account agreement.

OpenAI API terms: https://openai.com/policies/service-terms/

OpenAI privacy policy: https://openai.com/policies/privacy-policy/

== Changelog ==

= 1.4.15 =
* Declare alphasys.com.au and its subdomains as the allowed catalogue domains in plugin headers. Controller 0.5.0 reads these release headers; localhost remains available for development.

= 1.4.14 =

* Restored the Increase Pace, Stay the Course and Decrease Pace heatmap as Pace Guidance.
* Changed Month Actuals from customer counts to portfolio dollar totals for Month -3, Month -2 and Month -1.
* Sized both three-column heatmaps to exactly three columns of the twelve-column agreement-age table.

= 1.4.13 =

* Titled the twelve-column portfolio heatmap MS Agreement Age.
* Replaced the pace distribution with a Month Actuals freshness heatmap for Month -3, Month -2 and Month -1.
* Counted customer cards by their latest reported calendar month in the new Month Actuals table.

= 1.4.12 =

* Explicitly selected rAIven's verified qwen3.8-flash-next-nvfp4 model for native AI requests.
* Avoided automatic selection of the advertised gpt-4 model, which has no active rAIven endpoint.
* Added the ASMS_RAIVEN_MODEL constant as an optional account-specific model override.

= 1.4.11 =

* Stopped forwarding stale legacy model settings such as gpt-4 to rAIven.
* Delegated model discovery and selection entirely to the native rAIven provider.

= 1.4.10 =

* Removed the legacy rAIven credential and model-discovery pre-check that could disagree with WordPress Connectors.
* Delegated rAIven authentication and availability to its registered native WordPress AI provider.
* Supported automatic rAIven model selection when no model is saved, while retaining OpenAI fallback for failed requests.

= 1.4.9 =

* Added a Last AI API Used panel at the bottom of each MS Account edit screen.
* Recorded the successful provider, model, operation, routing result and completion time.
* Identified when OpenAI completed a request as the fallback after rAIven failed.

= 1.4.8 =

* Preferred a configured rAIven Connector and its selected model for AI classifications and summaries.
* Retried through the native OpenAI connector when rAIven is unavailable, unconfigured, fails, or returns invalid structured output.
* Added provider-aware JSON enforcement and validation for rAIven classification responses.

= 1.4.7 =

* Removed the full-card divider beneath account mini charts.
* Reduced excess chart padding and tightened the related-user footer.
* Preserved automatic footer growth for cards with multiple related users.

= 1.4.6 =

* Rebuilt card mini charts as baseline-aligned inline SVGs for reliable rendering across themes.
* Increased the mini-chart height to approximately one and a half metric rows.
* Replaced boxed month cells with subtle gridlines and twelve understated baseline markers.
* Inset chart guide lines to the same gutters as the metric table.

= 1.4.5 =

* Added a compact twelve-month Plan vs Actual chart above the related-user footer on every account card.
* Kept all twelve month positions visible, including empty and future months.
* Removed the redundant Months Delivered row from account cards while retaining it on individual account reports.

= 1.4.4 =

* Removed the minimum height from account-card title bars.
* Truncated long account titles to a single-line ellipsis.
* Re-centred the edit control within the compact title bar.
* Placed related-user content directly below the metrics in a consistent, subtle footer.

= 1.4.3 =

* Added consistent white gutters around detail-report tables.
* Kept each section title inside the same panel as its table.
* Kept Plan vs Actual and its aligned chart inside one continuous panel.
* Kept the account edit control anchored to the top-right of its title bar.

= 1.4.2 =

* Prevented theme heading margins from creating space above home-page card title bars.
* Kept the edit control aligned inside the flush navy title bar.

= 1.4.1 =

* Removed the unwanted space above account-card title bars.
* Added conditional guidance styling and stronger Pace Guidance contrast.
* Visually connected Plan vs Actual with its chart while preserving 12-month alignment.
* Placed report section headings inside their white panel surfaces.
* Restored a polished green heatmap palette and applied the intended cool page background.

= 1.4.0 =

* Introduced a cohesive premium visual system for account cards and detail reports.
* Preserved account access, report-month grouping, calculations, and 12-month table/chart alignment.
* Refined typography, title bars, panels, guidance states, user lists, heatmaps, and chart colours.

= 1.3.9 =

* Required WordPress 7.0 and used its native AI Client exclusively.
* Removed the direct OpenAI API-key fallback and its configuration helper.

= 1.3.8 =

* Used the WordPress 7 AI Client and configured Settings > Connectors provider for classification and summaries.
* Retained the direct OpenAI API key path as a fallback for older WordPress installations.

= 1.3.7 =

* Fixed AI category classification by using strict structured output and robust response extraction.
* Reclassified previously saved rows with blank categories when an account is saved.
* Displayed classification failures in the account editor instead of silently saving blank categories.

= 1.3.6 =

* Adopted the latest shortcode and CSS presentation changes.
* Added an AlphaSys-only edit icon to account cards that opens the WordPress editor in a new tab.

= 1.3.5 =

* Simplified the portfolio card to four financial totals.
* Added AlphaSys-only customer-count heatmaps for months delivered and pace guidance.
* Moved Portfolio Totals into a report-style section heading.

= 1.3.4 =

* Adopted the latest account-card display changes.
* Added an AlphaSys-only portfolio totals card with financial, delivery-month, and guidance aggregates.

= 1.3.3 =

* Limited account cards to three per row and matched all card heights within each month group.

= 1.3.2 =

* Ensured account-card styles load when the shortcode is rendered by a page builder or template.

= 1.3.1 =

* Replaced the related-account list with monthly grouped pace cards.
* Added external related users to each account card.

= 1.3.0 =

* Fixed the plugin metadata header.
* Removed the embedded API key and added external configuration.
* Added native updates from public GitHub releases.
* Hardened capability, input, redirect, and output handling.
* Added standards-compliant release files and packaging.

= 1.2.0 =

* Added related-user account relationships, account listing, and report access controls.

= 1.1.0 =

* Added AI summaries and pace guidance.

= 1.0.0 =

* Published the first stable release.

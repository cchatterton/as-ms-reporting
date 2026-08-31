=== AS Managed Services Reporting ===
Contributors: alphasys
Tags: reporting, managed services, accounts, analytics, ai
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.3.7
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
3. To enable AI-assisted features, define `ASMS_OPENAI_API_KEY` in `wp-config.php` or the server environment.

== External services ==

This plugin can send managed-services task descriptions, staff roles, and monthly notes to the OpenAI Responses API when an authorised editor saves imported reporting data or notes and an API key is configured. This data is sent to classify work and create report summaries. No OpenAI request is made when the API key is absent.

OpenAI API terms: https://openai.com/policies/service-terms/

OpenAI privacy policy: https://openai.com/policies/privacy-policy/

== Changelog ==

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

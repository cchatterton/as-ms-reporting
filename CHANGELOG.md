# Changelog

All notable changes to AS Managed Services Reporting are recorded here.

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

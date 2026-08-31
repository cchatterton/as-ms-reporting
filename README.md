# AS Managed Services Reporting

Author: AlphaSys  
Version: 1.3.6<br>
Status: Production

AS Managed Services Reporting provides managed-services account reporting, related-user access controls, tab-separated data imports, and optional AI-assisted classification and summaries.

## Installation

Upload `as-ms-reporting.zip` through **Plugins > Add New > Upload Plugin**, then activate it.

For AI-assisted features, define the API key outside the plugin source. Either set an `ASMS_OPENAI_API_KEY` environment variable or add this to `wp-config.php`:

```php
define( 'ASMS_OPENAI_API_KEY', 'your-key-from-a-secret-store' );
```

The model can be overridden in `wp-config.php` before WordPress loads the plugin:

```php
define( 'ASMS_OPENAI_MODEL', 'your-approved-model' );
```

## Updates

Public GitHub releases provide native WordPress updates. The active plugin row includes **GitHub** and **Check for updates** links.

## Development

Run `scripts/build-plugin-zip.sh` from the repository root. The script creates both `dist/as-ms-reporting.zip` and the committed root-level `as-ms-reporting.zip`.

## Security

Never commit API keys or other credentials. If a key has previously appeared in a ZIP, repository, log, or chat output, revoke it before deploying this release.

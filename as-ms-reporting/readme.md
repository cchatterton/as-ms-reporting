# AS Managed Services Reporting

Author: AlphaSys  
Version: 1.3.0  
Status: Production

## Purpose

Provides managed-services account reports, related-user access, data imports, and optional AI-assisted classification and summaries.

## Configuration

On WordPress 7.0 or later, configure an AI provider under **Settings > Connectors**. The plugin uses the WordPress AI Client and the connector's securely stored credentials.

For older WordPress versions, supply `ASMS_OPENAI_API_KEY` through the environment or define it in `wp-config.php`. Never add the key to plugin source.

## Updates

Updates are delivered from public releases at https://github.com/cchatterton/as-ms-reporting.

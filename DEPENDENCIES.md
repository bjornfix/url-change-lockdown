# Runtime dependencies

- [WordPress 6.9 or later](https://wordpress.org/documentation/wordpress-version/version-6-9/) provides the public content, taxonomy, permalink, and native Abilities API surfaces used by URL Change Lockdown.
- [PHP 7.4 or later](https://www.php.net/releases/7_4_0.php) runs the plugin.
- [WordPress Abilities API in WordPress 6.9 or later](https://developer.wordpress.org/apis/abilities-api/) registers the audit, preview, and confirmed migration abilities.
- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter/) is optional and exposes the registered WordPress abilities to MCP clients.
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) must be installed and active for confirmed URL migrations because it creates the required permanent redirects. Route protection and audit remain available without it.

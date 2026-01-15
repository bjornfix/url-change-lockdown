# URL Change Lockdown
[![Release](https://img.shields.io/github/v/release/bjornfix/url-change-lockdown?display_name=tag&sort=semver)](https://github.com/bjornfix/url-change-lockdown/releases)

Stable tag: 1.1.1

URL Change Lockdown blocks programmatic changes to site URLs, permalink settings, post slugs, parent pages, and taxonomies.

## Behavior
- Blocks programmatic updates to home/siteurl and permalink settings.
- Blocks programmatic changes to post slugs, parent pages, and taxonomies.
- Allows manual changes through wp-admin for administrators.
- Optional constants to allow programmatic changes temporarily.

## Configuration
Add one of the following constants to `wp-config.php` when needed:

```php
define('URL_LOCKDOWN_ALLOW', true);
// Or, for WP-CLI updates only:
define('URL_LOCKDOWN_ALLOW_CLI', true);
```

## Related
- https://devenia.com/plugins/mcp-expose-abilities/
- https://profiles.wordpress.org/basicus/

## Changelog
### 1.0.0
- Initial release.

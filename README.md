# URL Change Lockdown

Stable tag: 1.0.0

URL Change Lockdown blocks programmatic updates to the WordPress "home" and "siteurl" options.

## Behavior
- Blocks programmatic updates to home/siteurl.
- Allows manual changes through Settings > General for administrators.
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

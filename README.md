# URL Change Lockdown
[![Release](https://img.shields.io/github/v/release/bjornfix/url-change-lockdown?display_name=tag&sort=semver)](https://github.com/bjornfix/url-change-lockdown/releases)

Stable tag: 1.3.0

URL Change Lockdown blocks URL-related changes unless explicitly allowed. This includes site/permalink URL settings, slugs/parents/taxonomy URL drivers, content/excerpt links, and link changes inside post meta/custom fields.

## Behavior
- Blocks updates to `home`, `siteurl`, `permalink_structure`, `category_base`, and `tag_base`.
- Blocks slug/parent URL-driver changes on posts and terms.
- Blocks additions/changes/removals of URLs in `post_content`, `post_excerpt`, and `post_content_filtered`.
- Blocks additions/changes/removals of URLs in post meta/custom fields.
- Keeps non-URL content edits allowed.
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
### 1.3.0
- Hardened lock scope to deny URL mutations by default across content, meta/custom fields, term URL drivers, and URL settings.
- Removed REST/header-based manual allowance from the URL lock path to prevent API bypasses.
- Kept non-URL content edits allowed to preserve URL-only scope.

### 1.2.0
- Hardened REST/manual detection by requiring a wp-admin referer in addition to REST nonce.
- Added URL-diff locking for `post_content` and `post_excerpt` to block programmatic link changes.

### 1.1.2
- Readme tag cleanup (WordPress.org limit).

### 1.1.1
- Allow manual bulk edits via wp-admin without triggering URL locks.

### 1.1.0
- Block programmatic changes to slugs, parent pages, taxonomies, and permalink settings.

### 1.0.0
- Initial release.

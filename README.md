# URL Change Lockdown
[![Release](https://img.shields.io/github/v/release/bjornfix/url-change-lockdown?display_name=tag&sort=semver)](https://github.com/bjornfix/url-change-lockdown/releases)

Stable tag: 1.4.1

URL Change Lockdown blocks slug changes unless explicitly allowed.

## Behavior
- Freezes existing post slugs (`post_name`) on update unless explicitly unlocked.
- Freezes existing taxonomy term slugs on update unless explicitly unlocked.
- Does not lock links inside post content.
- Does not lock post meta values.
- Optional constants to allow slug changes temporarily.

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
### 1.4.1
- Clarified behavior: existing slugs are frozen across update paths unless explicitly unlocked.
- Moved slug guards to late filter priority to prevent downstream filter overrides.
- Documentation now explicitly states that post-content URLs are not locked.

### 1.4.0
- Scope clarified and enforced as slug-only protection.
- Removed content URL, metadata URL, and option/permalink guards.
- Keeps post and taxonomy slug locks in place for programmatic updates.

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

# URL Change Lockdown

Freeze existing post and taxonomy slugs unless explicitly unlocked.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/url-change-lockdown?display_name=tag&sort=semver)](https://github.com/bjornfix/url-change-lockdown/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

**Tested up to:** 6.9
**Stable tag:** 1.4.2
**Requires PHP:** 7.4
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

URL Change Lockdown blocks slug changes on existing posts and taxonomy terms unless explicitly allowed. It is a small hardening plugin for cases where programmatic imports, MCP tools, bulk editors, or custom code should not be able to silently change established URLs.

It does not lock links inside post content and does not lock post meta values. The scope is slugs only.

## Behavior

- Freezes existing post slugs (`post_name`) on update unless explicitly unlocked
- Freezes existing taxonomy term slugs on update unless explicitly unlocked
- Does not lock links inside post content
- Does not lock post meta values
- Supports temporary allow constants when you intentionally need to rename slugs

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress
3. Optionally define one of the allow constants in `wp-config.php` when a slug change is intentionally needed

## Configuration

Add one of the following constants to `wp-config.php` when needed:

```php
define('URL_LOCKDOWN_ALLOW', true);
// Or, for WP-CLI updates only:
define('URL_LOCKDOWN_ALLOW_CLI', true);
```

## Use Cases

- Keep established post URLs stable during imports or sync jobs
- Prevent accidental taxonomy slug churn during programmatic updates
- Allow editors to change content without allowing hidden permalink changes
- Require an explicit unlock step before any slug rename happens

## Changelog

### 1.4.2
- Docs: expanded the WordPress-standard `readme.txt` so the published ZIP now includes fuller behavior, installation, use-case, and Devenia link sections

### 1.4.1
- Clarified behavior: existing slugs are frozen across update paths unless explicitly unlocked
- Moved slug guards to late filter priority to prevent downstream filter overrides
- Documentation now explicitly states that post-content URLs are not locked

### 1.4.0
- Scope clarified and enforced as slug-only protection
- Removed content URL, metadata URL, and option/permalink guards
- Keeps post and taxonomy slug locks in place for programmatic updates

### 1.3.0
- Hardened lock scope to deny URL mutations by default across content, meta/custom fields, term URL drivers, and URL settings
- Removed REST/header-based manual allowance from the URL lock path to prevent API bypasses
- Kept non-URL content edits allowed to preserve URL-only scope

### 1.2.0
- Hardened REST/manual detection by requiring a wp-admin referer in addition to REST nonce
- Added URL-diff locking for `post_content` and `post_excerpt` to block programmatic link changes

### 1.1.2
- Readme tag cleanup (WordPress.org limit)

### 1.1.1
- Allow manual bulk edits via wp-admin without triggering URL locks

### 1.1.0
- Block programmatic changes to slugs, parent pages, taxonomies, and permalink settings

### 1.0.0
- Initial release

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) and [basicus](https://profiles.wordpress.org/basicus/)

## Links

- [GitHub Releases](https://github.com/bjornfix/url-change-lockdown/releases)
- [Devenia Plugins](https://devenia.com/plugins/)

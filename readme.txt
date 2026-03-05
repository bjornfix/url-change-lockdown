=== URL Change Lockdown ===
Contributors: basicus
Tags: security, hardening, siteurl, permalinks, slugs
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prevent URL-related WordPress changes unless explicitly allowed.

== Description ==
URL Change Lockdown blocks URL-related changes unless explicitly allowed.
This includes site/permalink settings, slug/parent/taxonomy URL drivers, content/excerpt links, and link changes in post meta/custom fields.
Non-URL content edits remain allowed.

To allow programmatic changes, define one of these constants in wp-config.php:
- URL_LOCKDOWN_ALLOW
- URL_LOCKDOWN_ALLOW_CLI (for WP-CLI)

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. (Optional) Define URL_LOCKDOWN_ALLOW or URL_LOCKDOWN_ALLOW_CLI in wp-config.php.

== Frequently Asked Questions ==
= How do I allow a programmatic change? =
Define URL_LOCKDOWN_ALLOW in wp-config.php, perform the change, then remove the constant.

= Does this block manual updates in wp-admin? =
No. Manual updates in Settings > General, Settings > Permalinks, and post edit screens still work.

= Does it block post/page slugs and taxonomy changes? =
Yes. Programmatic changes to slugs, parent pages, and taxonomy assignments are blocked unless explicitly allowed.

= Does it block links in post content? =
Yes. Programmatic additions/changes/removals of URLs in `post_content` and `post_excerpt` are blocked unless explicitly allowed.

= Does it block links inside custom fields/post meta? =
Yes. URL changes in post meta/custom fields are also blocked unless explicitly allowed.

== Changelog ==
= 1.3.0 =
- Hardened lock scope to deny URL mutations by default across content, post meta/custom fields, term URL drivers, and URL settings.
- Removed REST/header-based manual allowance from URL lock path to prevent API bypasses.
- Kept non-URL content edits allowed.
= 1.2.0 =
- Hardened REST/manual detection by requiring a wp-admin referer in addition to REST nonce.
- Added URL-diff locking for post content and excerpts.
= 1.1.2 =
- Reduce plugin tags to meet WordPress.org limits.
= 1.1.1 =
- Allow manual bulk edits via wp-admin without triggering URL locks.
= 1.1.0 =
- Block programmatic changes to slugs, parent pages, taxonomies, and permalink settings.
= 1.0.0 =
- Initial release.

== Upgrade Notice ==
= 1.3.0 =
Stronger URL lock coverage across content, meta/custom fields, and URL-driver fields while keeping non-URL edits allowed.
= 1.2.0 =
Adds blocking for programmatic link changes in post content/excerpts and tightens REST/manual detection.
= 1.1.2 =
Readme cleanup (tag limit).
= 1.1.1 =
Fixes manual bulk edit handling in wp-admin.
= 1.1.0 =
Adds protections for slugs, taxonomy assignments, and permalink settings.
= 1.0.0 =
Initial release.

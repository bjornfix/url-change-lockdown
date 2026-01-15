=== URL Change Lockdown ===
Contributors: basicus
Tags: security, hardening, siteurl, home, permalinks, slugs
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prevent programmatic changes to WordPress URLs, slugs, and taxonomies.

== Description ==
URL Change Lockdown blocks programmatic changes to site URLs, permalink settings, post slugs, parent pages, and taxonomy assignments.
Manual changes in wp-admin are allowed for administrators.

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

== Changelog ==
= 1.1.0 =
- Block programmatic changes to slugs, parent pages, taxonomies, and permalink settings.
= 1.0.0 =
- Initial release.

== Upgrade Notice ==
= 1.1.0 =
Adds protections for slugs, taxonomy assignments, and permalink settings.
= 1.0.0 =
Initial release.

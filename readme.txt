=== URL Change Lockdown ===
Contributors: basicus
Tags: security, hardening, siteurl, home
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prevent programmatic changes to the WordPress home and siteurl options.

== Description ==
URL Change Lockdown blocks programmatic updates to the WordPress "home" and "siteurl" options.
Manual changes through Settings > General are allowed for administrators.

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
No. Administrators can still change the Site Address and WordPress Address in Settings > General.

== Changelog ==
= 1.0.0 =
- Initial release.

== Upgrade Notice ==
= 1.0.0 =
Initial release.

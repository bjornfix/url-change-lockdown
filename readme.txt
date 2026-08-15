=== URL Change Lockdown ===
Contributors: basicus
Tags: security, hardening, permalinks
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Preserve established public routes and migrate them only through an explicit, audited workflow.

== Description ==
URL Change Lockdown establishes a Canonical Route Contract for public posts,
pages and taxonomy terms. Ordinary editor, REST, MCP, import, WP-CLI and plugin
writes preserve post slugs, page hierarchy, term slugs and term hierarchy.

Necessary URL corrections use separate preview and migration abilities. A
migration requires a concrete reason and matching confirmation, shows affected
child routes, creates permanent Rank Math redirects, verifies the observed
route, records audit evidence, and rolls back if redirects cannot be created.
Duplicate post routes can also be retired to an existing canonical public post
with the same preview, confirmation, redirect, audit, and rollback controls.
Site-wide permalink, category-base and tag-base settings are locked against
ordinary updates because changing them moves many public URLs at once.

= Use Cases =

* Keep existing post and taxonomy URLs stable during imports or sync jobs
* Prevent accidental slug and hierarchy changes from every normal write path
* Audit established routes against current observed WordPress permalinks
* Migrate genuinely incorrect URLs with preview, confirmation and redirects

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Use the read-only audit ability to verify Canonical Route Contracts.

= Links =
* [Stable plugin download](https://downloads.devenia.com/url-change-lockdown.zip)
* [Devenia Plugins](https://devenia.com/plugins/)

== Frequently Asked Questions ==
= How do I correct a genuinely wrong URL? =
Use the preview ability, review the complete old/new route and affected descendants, then submit the returned confirmation with a concrete reason to the matching migration ability.

= Does this block manual URL changes in wp-admin? =
Yes. Editing content remains normal, but established public routes use the same protection regardless of which writer initiated the save.

= What route values are protected? =
Post/page slugs, page hierarchy, taxonomy slugs, taxonomy hierarchy, permalink structure, category base and tag base.

= Does it block links in post content? =
No. Content links are not locked by this plugin.

= Does it block links inside custom fields/post meta? =
No. Post meta values are not locked by this plugin.

== Changelog ==
= 2.0.4 =
- Added confirmed post-route retirement to an existing canonical public post, with rollback when redirect creation fails.

= 2.0.3 =
- Added a fail-closed migration-finalization seam so adapters can remove conflicting self-redirects before a migration is committed; failed finalization restores the old route and removes newly created redirects.

= 2.0.2 =
- Fixed the Rank Math redirect adapter to use the required active status and normalized exact-source structure; failed migrations continue to roll back safely.

= 2.0.1 =
- Added the explicit MCP dangerous-action confirmation property to both migration schemas so the confirmation gate and strict input validation work together.

= 2.0.0 =
- Added immutable-by-default Canonical Route Contracts for public posts, pages and taxonomy terms.
- Protected hierarchy and site permalink drivers as well as slugs, across editor, REST, MCP, import and WP-CLI writes.
- Added read-only route auditing and explicit post/page and taxonomy URL Migration abilities.
- Added migration previews, affected-descendant evidence, permanent Rank Math redirects, rollback and bounded audit history.
- Removed broad constants and automatic wp-admin bypasses from ordinary save paths.

= 1.4.3 =
- Fixed manual editing: authorized wp-admin/editor requests can now intentionally change post and term slugs.
- Programmatic writes without a verified admin/editor context remain locked unless explicitly allowed.
= 1.4.2 =
- Docs: expanded the WordPress-standard `readme.txt` so the published ZIP now includes fuller behavior, installation, use-case, and Devenia link sections
= 1.4.1 =
- Clarified behavior: existing slugs are frozen across update paths unless explicitly unlocked.
- Moved slug guards to late filter priority to reduce downstream override risk.
- Documentation now explicitly states that post-content URLs are not locked.
= 1.4.0 =
- Scope clarified and enforced as slug-only protection.
- Removed content URL, metadata URL, and option/permalink guards.
- Keeps post and taxonomy slug locks in place for programmatic updates.
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
= 2.0.4 =
Adds audited retirement of one public post route to an existing canonical post.

= 2.0.3 =
Adds fail-closed finalization for redirect and canonical-route conflicts.

= 2.0.2 =
Required adapter fix for permanent redirect creation during an explicitly confirmed URL migration.

= 2.0.1 =
Required compatibility fix for confirmed URL migrations through MCP.

= 2.0.0 =
Public routes are now stable across all ordinary write paths. Use the explicit preview and migration abilities for necessary URL corrections.
= 1.4.3 =
Manual slug edits in wp-admin/the editor now work for authorized users; programmatic slug changes remain locked by default.
= 1.4.1 =
Existing slugs are now explicitly frozen unless unlocked; content URLs remain outside plugin scope.
= 1.4.0 =
Plugin now protects slugs only (post and taxonomy). Content links and post meta are no longer locked.
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

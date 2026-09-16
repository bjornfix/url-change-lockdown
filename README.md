# URL Change Lockdown

Keep published WordPress addresses stable while editing content. When an address must change, preview the new route and move it through a confirmed migration with permanent redirects.

[![Version 2.0.7](https://img.shields.io/badge/version-2.0.7-blue.svg)](https://downloads.devenia.com/url-change-lockdown.zip)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

**Tested up to:** 7.1
**Stable tag:** 2.0.7
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

**Tags:** security, hardening, permalinks

## What It Does

The plugin records the established route of a public post, page or taxonomy term. Ordinary edits preserve its slug and route-bearing hierarchy, whether they come from the editor, REST, MCP, imports, WP-CLI or another plugin. Content and non-route metadata remain editable.

A necessary address correction is a separate operation. Its preview shows the current and proposed routes. A migration requires a concrete reason and the confirmation returned by the current preview, creates permanent redirects and records the result.

## The Real Workflow

1. Install and activate the plugin on the intended WordPress site.
2. Edit content normally; established routes remain protected.
3. Use the route audit to compare stored contracts with current WordPress routes.
4. When an address needs to change, request a migration preview and inspect the destination and affected descendants.
5. Submit that current confirmation with the same proposed change and a reason. Check the resulting route and redirects.

## Why This Feels Different

Route protection applies at the WordPress write hooks, not just in one editing screen. An administrator's ordinary edit is not an automatic bypass. Intentional route changes use the migration functions, while routine copy edits do not need route-migration approval.

## Before vs After

| Task | Ordinary edit | Confirmed migration |
| --- | --- | --- |
| Improve a title or body | Saves without changing the established address | Not needed |
| Rename an established slug | Keeps the existing slug | Changes the route and creates a permanent redirect |
| Change route-bearing hierarchy | Preserves the established hierarchy | Previews affected descendants and redirects their old paths |
| Consolidate two public posts | Does not retire either route | Makes the source a recoverable draft and redirects it to the existing target |

## Who It Is For

Site owners, agencies and developers whose editors, imports or integrations must not silently rename published addresses. The plugin does not repair arbitrary links inside post bodies or replace a general broken-link checker.

## Requirements

- WordPress 6.9 or later and PHP 7.4 or later.
- An account with `manage_options` for the registered management abilities.
- Rank Math's redirection classes, or an integration implementing the `url_change_lockdown_create_redirect` filter, for migrations that require redirects.
- An MCP bridge only when calling the WordPress abilities through MCP. It is not required for ordinary route protection.

## Documentation

Start with the public plugin page:

- [Plugin Page](https://devenia.com/plugins/url-change-lockdown/)
- [Stable plugin download](https://downloads.devenia.com/url-change-lockdown.zip)

## Start Here

Download the stable package, install it through **Plugins → Add New → Upload Plugin**, and activate it. Audit the intended site before making a route change. Inspect the complete preview rather than treating a confirmation token as approval of an unread destination.

## Management Abilities

| Ability | Purpose |
| --- | --- |
| `url-lockdown/audit` | Compare stored route contracts with current public routes. |
| `url-lockdown/preview-post-migration` | Preview a post/page slug or parent change and affected child routes. |
| `url-lockdown/migrate-post-route` | Apply the confirmed post/page change with redirects. |
| `url-lockdown/preview-derived-post-route-migration` | Preview a post route produced by a native routing integration. |
| `url-lockdown/migrate-derived-post-route` | Record the confirmed route and preserve the previous path while the calling integration applies its routing change. |
| `url-lockdown/preview-post-retirement` | Preview moving one public post route to another existing public post. |
| `url-lockdown/retire-post-route` | Retire the source to a recoverable draft and redirect it to the target. |
| `url-lockdown/preview-term-migration` | Preview a taxonomy slug or parent change using native term links. |
| `url-lockdown/migrate-term-route` | Apply the confirmed taxonomy change and redirect old routes. |

Derived-route functions serve integrations that change native routing. They create the redirect and update the stored route record; they do not apply the routing configuration. The calling integration must coordinate that change and verify the resulting address.

An optional Polylang integration preserves translated posts below the translated posts-page path. It is disabled by default. Developers can enable it with `url_change_lockdown_configure_polylang_post_routes( true )` when the site's established routes use that structure. It targets directory-based language routes; it is not a general replacement for Polylang's permalink settings.

## Example: Rename a Translated Tag

Call `url-lockdown/preview-term-migration` with the actual term ID, `taxonomy: "post_tag"` and `new_slug: "seo-for-bransjer"`. Inspect `old_route`, `proposed_route` and `affected_children`. The new URL follows the site's own language and permalink rules.

Then call `url-lockdown/migrate-term-route` with the same values, a reason such as “Replace the untranslated tag address with its Norwegian wording”, the returned `confirmation`, and `confirm_dangerous_action: "url-lockdown/migrate-term-route"`. A stale confirmation is rejected. Verify the returned canonical route and the redirect from the old address.

## Safety and Ownership

The plugin protects routes through normal WordPress writes; it is not a security boundary against an administrator who can alter the database or plugin code. Native WordPress and language integrations remain responsible for constructing URLs. Migrations require redirect support and report failures; do not infer success from a preview alone.

## Behavior

- Freezes established post/page slugs and page hierarchy on every ordinary update path
- Freezes established taxonomy slugs and hierarchy
- Locks permalink structure, category base and tag base against ordinary changes
- Applies the same invariant to wp-admin, REST, MCP, imports, WP-CLI and plugin writes
- Exposes route audit, migration preview and explicit confirmed migration abilities
- Retires a duplicate public route to an existing canonical post with preview, confirmation, one permanent redirect and audit evidence
- Creates permanent Rank Math redirects, verifies results and records bounded audit evidence
- Does not lock links inside post content
- Does not lock post meta values
- Provides confirmed URL migrations when a public address must change

## Contributing

Report the expected route, observed route, WordPress and PHP versions, and the operation that produced the difference. Remove credentials and private content from reports. Changes should preserve native permalink behavior and verify both the preview and the resulting route.

## Author and Links

Maintained by [basicus](https://profiles.wordpress.org/basicus/). See the [plugin page](https://devenia.com/plugins/url-change-lockdown/) for product documentation and the [stable ZIP](https://downloads.devenia.com/url-change-lockdown.zip) for installation.

## Changelog


### 2.0.7

Add one dismissible Plugins-screen reminder when Devenia MCP Updater is missing or inactive, with persistent install or activate links. Automatic updates remain your choice in WordPress.

### 2.0.6
- Added confirmed derived-route recording for routing integrations and optional preservation of translated posts-page paths.
- Corrected same-site destination validation to compare the origin and installation path.
- Fixed taxonomy migration previews to resolve proposed URLs through WordPress, including language routing, parent changes and descendant URLs, without changing stored terms.

### 2.0.4
- Added confirmed post-route retirement to an existing canonical public post, with rollback when redirect creation fails.

### 2.0.3
- Added fail-closed migration finalization so route-specific adapters can remove self-redirect conflicts or force a full rollback.

### 2.0.2
- Fixed Rank Math redirect creation to use its required active status and exact-source structure while retaining rollback on failure.

### 2.0.1
- Added explicit dangerous-action confirmation fields to the post and taxonomy migration schemas for strict MCP compatibility.

### 2.0.0
- Added Canonical Route Contracts and stable public routes across every ordinary writer.
- Added hierarchy and global permalink-driver protection.
- Added audited, confirmation-gated post/page and taxonomy URL migrations with descendant previews, permanent redirects and rollback.
- Removed automatic wp-admin and constant-based bypasses.

### 1.4.3
- Fixed manual editing: authorized wp-admin/editor requests can now intentionally change post and term slugs
- Programmatic writes without a verified admin/editor context remain locked unless explicitly allowed

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

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/url-change-lockdown/)
- [Stable plugin download](https://downloads.devenia.com/url-change-lockdown.zip)
- [Devenia Plugins](https://devenia.com/plugins/)


## Update notifications

For update notifications in WordPress, install [Devenia MCP Updater](https://downloads.devenia.com/devenia-mcp-updater.zip). The updater is optional. You choose which plugins update automatically through WordPress.

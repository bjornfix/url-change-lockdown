# URL Change Lockdown

Keep published WordPress addresses stable during automated updates. Authorized browser editors can correct URLs directly; automated route changes use confirmed migrations with an explicit redirect choice.

[![Version 2.0.9](https://img.shields.io/badge/version-2.0.9-blue.svg)](https://downloads.devenia.com/url-change-lockdown.zip)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

**Tested up to:** 7.1
**Stable tag:** 2.0.9
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

**Tags:** security, hardening, permalinks

## What It Does

The plugin records the established route of a public post, page or taxonomy term. Machine writes through WordPress preserve its slug and route-bearing hierarchy, including application-password REST, MCP, background imports and WP-CLI. Authorized saves from a logged-in WordPress browser session can change routes through wp-admin, AJAX and the block editor. Content and non-route metadata remain editable.

An automated address correction is a separate operation. Its preview shows the current and proposed routes. A migration requires a concrete reason and the confirmation returned by the current preview, creates permanent redirects by default and records the result. Post migrations can explicitly select no redirect in both preview and execution; that choice is bound to the confirmation.

## The Real Workflow

1. Install and activate the plugin on the intended WordPress site.
2. Edit content and correct URLs through the normal WordPress controls or Permalink Manager. The saved route becomes the new reference.
3. Use the route audit to compare stored contracts with current WordPress routes.
4. For an automated address change with redirects, request a migration preview and inspect the destination and affected descendants.
5. Submit that current confirmation with the same proposed change and a reason. Check the resulting route and redirects.

## Why This Feels Different

Route protection applies at the WordPress write hooks. A logged-in browser session and the permission to edit the affected object allow manual corrections. Admin context or administrator permissions alone do not unlock machine writes. Each editor owns its form validation; the plugin does not maintain a competing list of third-party save actions.

## Before vs After

| Task | Authorized browser edit | Confirmed migration |
| --- | --- | --- |
| Improve a title or body | Saves without changing the established address | Not needed |
| Rename an established slug | Saves the chosen slug and updates the route reference | Changes the route and creates a permanent redirect |
| Change route-bearing hierarchy | Saves the hierarchy and refreshes affected route references | Previews affected descendants and redirects their old paths |
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
| `url-lockdown/migrate-post-route` | Apply the confirmed post/page change with the chosen redirect policy. |
| `url-lockdown/preview-derived-post-route-migration` | Preview a post route produced by a native routing integration. |
| `url-lockdown/migrate-derived-post-route` | Record the confirmed route and preserve the previous path while the calling integration applies its routing change. |
| `url-lockdown/preview-post-retirement` | Preview moving one public post route to another existing public post. |
| `url-lockdown/retire-post-route` | Retire the source to a recoverable draft and redirect it to the target. |
| `url-lockdown/preview-term-migration` | Preview a taxonomy slug or parent change using native term links. |
| `url-lockdown/migrate-term-route` | Apply the confirmed taxonomy change and redirect old routes. |

Derived-route functions serve integrations that change native routing. They create the redirect and update the stored route record; they do not apply the routing configuration. The calling integration must coordinate that change and verify the resulting address.

An optional Polylang integration preserves translated posts below the translated posts-page path. It is disabled by default. Developers can enable it with `url_change_lockdown_configure_polylang_post_routes( true )` when the site's established routes use that structure. It targets directory-based language routes; it is not a general replacement for Polylang's permalink settings.

## Example: Change a Post Address Without a Redirect

Call `url-lockdown/preview-post-migration` with the actual `post_id`, `new_slug`, and `redirect_policy: "none"`. Review the old and proposed routes and affected children. Then call `url-lockdown/migrate-post-route` with the same values and policy, a concrete `reason`, the fresh `confirmation`, and `confirm_dangerous_action: "url-lockdown/migrate-post-route"`.

The explicit choice prevents a new WordPress old-slug mapping and Rank Math's automatic post redirect for this write. Other posts and normal manual edits retain their ordinary behavior. Existing matching redirects or old-slug mappings stop the no-redirect preview and require a separate decision; this operation does not delete them. Verify the saved route and both public addresses after the site's ordinary publication. Other plugins and the public host can have their own routing behavior.

Omit `redirect_policy`, or use `"permanent"`, to keep the established default. A confirmation for one policy cannot authorize the other. Taxonomy, derived-route and retirement operations retain their redirect behavior.

## Example: Rename a Translated Tag

Call `url-lockdown/preview-term-migration` with the actual term ID, `taxonomy: "post_tag"` and `new_slug: "seo-for-bransjer"`. Inspect `old_route`, `proposed_route` and `affected_children`. The new URL follows the site's own language and permalink rules.

Then call `url-lockdown/migrate-term-route` with the same values, a reason such as “Replace the untranslated tag address with its Norwegian wording”, the returned `confirmation`, and `confirm_dangerous_action: "url-lockdown/migrate-term-route"`. A stale confirmation is rejected. Verify the returned canonical route and the redirect from the old address.

## Safety and Ownership

Manual edits use the editor's normal redirect behavior; URL Change Lockdown does not require Rank Math or create migration redirects for these saves. Permalink Manager remains responsible for custom-URI storage; its completed post-URI saves refresh the route reference. Direct database writes and third-party custom-URI stores are outside the native write guards. Browser-session detection identifies the request context, not whether a person or browser automation clicked Save.

The plugin protects routes through normal WordPress writes; it is not a security boundary against an administrator who can alter the database or plugin code. Native WordPress and language integrations remain responsible for constructing URLs. Migrations that create redirects require redirect support and report failures; do not infer success from a preview alone.

## Behavior

- Preserves established post/page and taxonomy slugs and hierarchy during machine writes
- Protects permalink structure, category base and tag base from machine writes
- Allows authorized browser edits through wp-admin, AJAX and cookie-authenticated REST
- Refreshes saved route references after manual edits, including Permalink Manager post-URI saves
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

### 2.0.9

- Add an explicit, confirmation-bound no-redirect choice to post migration previews and execution.
- Prevent new WordPress old-slug and automatic Rank Math redirects only for that selected write; keep default migrations and machine guards.
- Reject existing redirect conflicts without changing their rules.


### 2.0.8

- Restore authorized browser URL edits without a separate migration request.
- Keep machine writes protected and refresh route references after manual post, term and permalink-setting saves.
- Capture completed Permalink Manager post-URI changes.

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

# Domain Context

## Public Route

The externally visible URL identity of a published WordPress post, page, or
taxonomy term, including slug and route-bearing hierarchy.

## Canonical Route Contract

The association established between a public WordPress object and its Public
Route. Machine writes through native WordPress guards preserve it. Authorized
browser edits are authoritative corrections and replace the stored reference.
The owning editor validates its form and controls its normal redirect behavior.
Application-password REST, background imports, WP-CLI and MCP remain governed
by the migration Interface. Direct database writes and custom-URI stores remain
owned by the plugin that implements them.

## URL Migration

The explicit, capability-gated and confirmation-gated operation that may replace
a Canonical Route Contract for a concrete reason. It previews affected routes,
records audit evidence, and requires redirect and post-change verification.

## Public Route Retirement

The explicit, capability-gated and confirmation-gated operation that retires
one published post route to another existing canonical public post. The source
becomes a recoverable draft, its old path receives one permanent redirect to
the target, and the operation records audit evidence or restores the source on
failure.

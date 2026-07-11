# Domain Context

## Public Route

The externally visible URL identity of a published WordPress post, page, or
taxonomy term, including slug and route-bearing hierarchy.

## Canonical Route Contract

The immutable-by-default association established between a public WordPress
object and its Public Route. Ordinary writes preserve it regardless of whether
they come from wp-admin, REST, import, WP-CLI, MCP, or another plugin.

## URL Migration

The explicit, capability-gated and confirmation-gated operation that may replace
a Canonical Route Contract for a concrete reason. It previews affected routes,
records audit evidence, and requires redirect and post-change verification.

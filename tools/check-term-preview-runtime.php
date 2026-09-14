<?php
/** Standalone contract for native term-link migration previews. */

declare( strict_types=1 );
define( 'ABSPATH', __DIR__ . '/' );

class WP_Term {
	public function __construct( public int $term_id, public string $taxonomy, public string $slug, public int $parent = 0 ) {}
}
final class WP_Error {}
$GLOBALS['terms'] = array(
	10 => new WP_Term( 10, 'post_tag', 'old-tag' ),
	20 => new WP_Term( 20, 'category', 'old-parent' ),
	30 => new WP_Term( 30, 'category', 'child', 20 ),
	40 => new WP_Term( 40, 'category', 'new-parent' ),
);
$GLOBALS['filters'] = array();
function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ): void {
	unset( $accepted );
	$GLOBALS['filters'][ $hook ][ $priority ][] = $callback;
}
function remove_filter( $hook, $callback, $priority = 10 ): void {
	foreach ( $GLOBALS['filters'][ $hook ][ $priority ] ?? array() as $key => $value ) {
		if ( $value === $callback ) unset( $GLOBALS['filters'][ $hook ][ $priority ][ $key ] );
	}
}
function apply_filters( $hook, $value ) {
	$groups = $GLOBALS['filters'][ $hook ] ?? array();
	ksort( $groups );
	foreach ( $groups as $callbacks ) foreach ( $callbacks as $callback ) $value = $callback( $value );
	return $value;
}
function add_action( ...$args ): void {}
function register_activation_hook( ...$args ): void {}
function get_term( $id, $taxonomy = '' ) {
	$term = $GLOBALS['terms'][ $id ] ?? null;
	if ( ! $term || ( $taxonomy && $term->taxonomy !== $taxonomy ) ) return null;
	return apply_filters( 'get_term', clone $term );
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function is_taxonomy_viewable( $taxonomy ): bool { return in_array( $taxonomy, array( 'category', 'post_tag' ), true ); }
function sanitize_title( $slug ): string { return strtolower( trim( $slug ) ); }
function term_is_ancestor_of( $id, $child, $taxonomy ): bool {
	while ( $term = get_term( $child, $taxonomy ) ) {
		if ( ! $term->parent ) return false;
		if ( $term->parent === $id ) return true;
		$child = $term->parent;
	}
	return false;
}
function get_terms( $args ): array {
	return array_values( array_filter( $GLOBALS['terms'], static fn( $term ) => $term->taxonomy === $args['taxonomy'] && term_is_ancestor_of( $args['child_of'], $term->term_id, $term->taxonomy ) ) );
}
function get_term_link( $term ): string {
	$slugs = array( $term->slug );
	if ( 'category' === $term->taxonomy ) {
		$cursor = get_term( $term->term_id, $term->taxonomy );
		while ( $cursor && $cursor->parent ) {
			$cursor = get_term( $cursor->parent, $term->taxonomy );
			array_unshift( $slugs, $cursor->slug );
		}
	}
	return 'https://example.com/nb/' . $term->taxonomy . '/' . implode( '/', $slugs ) . '/';
}
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function wp_json_encode( $value ): string { return (string) json_encode( $value ); }

require dirname( __DIR__ ) . '/url-change-lockdown.php';

$before = serialize( $GLOBALS['terms'] );
$tag = url_change_lockdown_preview_term_migration( 10, 'post_tag', 'new-tag' );
if ( '/nb/post_tag/new-tag/' !== ( $tag['proposed_route']['path'] ?? '' ) ) {
	throw new RuntimeException( 'The preview retained the old tag URL instead of asking the native term-link Interface.' );
}
$parent = url_change_lockdown_preview_term_migration( 20, 'category', 'renamed', 40 );
if ( '/nb/category/new-parent/renamed/' !== ( $parent['proposed_route']['path'] ?? '' )
	|| '/nb/category/new-parent/renamed/child/' !== ( $parent['affected_children'][0]['new_path'] ?? '' ) ) {
	throw new RuntimeException( 'The native hierarchy or descendant preview did not use the proposed term.' );
}
if ( $before !== serialize( $GLOBALS['terms'] ) || 'old-parent' !== get_term( 20 )->slug || 0 !== get_term( 20 )->parent ) {
	throw new RuntimeException( 'The read-only preview changed term data or left its temporary filter installed.' );
}
echo "Native term migration preview contract passed.\n";

<?php
/** Standalone contract for retiring one public post route to another. */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

class WP_Post {
	public function __construct(
		public int $ID,
		public string $post_name,
		public string $post_status = 'publish',
		public string $post_type = 'page',
		public int $post_parent = 0
	) {}
}

final class WP_Error {
	public function __construct( public string $code, public string $message = '' ) {}
	public function get_error_message(): string { return $this->message; }
}

$GLOBALS['fixture_posts'] = array(
	10 => new WP_Post( 10, 'old-plugin' ),
	20 => new WP_Post( 20, 'canonical-plugin' ),
);
$GLOBALS['fixture_children'] = array();
$GLOBALS['fixture_post_meta'] = array();
$GLOBALS['fixture_options'] = array();
$GLOBALS['fixture_redirect_result'] = 91;
$GLOBALS['fixture_redirect_calls'] = array();
$GLOBALS['fixture_actions'] = array();
$GLOBALS['fixture_abilities'] = array();
$GLOBALS['fixture_update_error'] = null;

function add_filter( ...$args ): void { unset( $args ); }
function add_action( ...$args ): void { unset( $args ); }
function register_activation_hook( ...$args ): void { unset( $args ); }
function wp_parse_url( string $url, int $component ) { return parse_url( $url, $component ); }
function get_permalink( $post ): string { return 'https://staging.devenia.com/plugins/' . $post->post_name . '/'; }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function is_post_type_viewable( string $post_type ): bool { return 'page' === $post_type; }
function is_post_type_hierarchical( string $post_type ): bool { return 'page' === $post_type; }
function get_post( int $post_id ) { return $GLOBALS['fixture_posts'][ $post_id ] ?? null; }
function get_pages( array $args ): array { return $GLOBALS['fixture_children'][ (int) $args['child_of'] ] ?? array(); }
function get_post_ancestors( int $post_id ): array {
	$ancestors = array();
	while ( isset( $GLOBALS['fixture_posts'][ $post_id ] ) && $GLOBALS['fixture_posts'][ $post_id ]->post_parent ) {
		$post_id = $GLOBALS['fixture_posts'][ $post_id ]->post_parent;
		$ancestors[] = $post_id;
	}
	return $ancestors;
}
function get_post_meta( int $post_id, string $key, bool $single ) { unset( $single ); return $GLOBALS['fixture_post_meta'][ $post_id ][ $key ] ?? array(); }
function update_post_meta( int $post_id, string $key, $value ): bool { $GLOBALS['fixture_post_meta'][ $post_id ][ $key ] = $value; return true; }
function sanitize_title( string $value ): string { return strtolower( trim( preg_replace( '/[^a-z0-9-]+/i', '-', $value ), '-' ) ); }
function sanitize_textarea_field( string $value ): string { return trim( $value ); }
function sanitize_key( string $value ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $value ) ); }
function absint( $value ): int { return abs( (int) $value ); }
if ( ! function_exists( 'mb_strlen' ) ) { function mb_strlen( string $value ): int { return strlen( $value ); } }
function wp_json_encode( $value ): string { return (string) json_encode( $value, JSON_UNESCAPED_SLASHES ); }
function wp_update_post( array $data, bool $wp_error = false ) {
	unset( $wp_error );
	if ( $GLOBALS['fixture_update_error'] instanceof WP_Error ) return $GLOBALS['fixture_update_error'];
	$post = $GLOBALS['fixture_posts'][ (int) $data['ID'] ] ?? null;
	if ( ! $post ) return new WP_Error( 'missing', 'Post missing.' );
	foreach ( array( 'post_status', 'post_name', 'post_parent' ) as $field ) {
		if ( array_key_exists( $field, $data ) ) $post->{$field} = $data[ $field ];
	}
	return $post->ID;
}
function apply_filters( string $hook, $value, ...$args ) {
	if ( 'url_change_lockdown_create_redirect' === $hook ) {
		$GLOBALS['fixture_redirect_calls'][] = $args;
		return $GLOBALS['fixture_redirect_result'];
	}
	return $value;
}
function do_action( string $hook, ...$args ): void { $GLOBALS['fixture_actions'][] = array( $hook, $args ); }
function get_option( string $key, $default = false ) { return $GLOBALS['fixture_options'][ $key ] ?? $default; }
function update_option( string $key, $value, bool $autoload = false ): bool { unset( $autoload ); $GLOBALS['fixture_options'][ $key ] = $value; return true; }
function get_current_user_id(): int { return 7; }
function wp_is_post_revision( int $post_id ): bool { unset( $post_id ); return false; }
function current_user_can( string $capability ): bool { return 'manage_options' === $capability; }
function wp_register_ability( string $name, array $definition ): void { $GLOBALS['fixture_abilities'][ $name ] = $definition; }

require_once dirname( __DIR__ ) . '/url-change-lockdown.php';

$preview = url_change_lockdown_preview_post_retirement( 10, 20 );
if (
	empty( $preview['success'] )
	|| '/plugins/old-plugin/' !== ( $preview['old_route']['path'] ?? '' )
	|| '/plugins/canonical-plugin/' !== ( $preview['target_route']['path'] ?? '' )
	|| 64 !== strlen( (string) ( $preview['confirmation'] ?? '' ) )
) {
	throw new RuntimeException( 'The post-route retirement preview did not bind the source and canonical target.' );
}
if ( 'same_post' !== ( url_change_lockdown_preview_post_retirement( 10, 10 )['code'] ?? '' ) ) {
	throw new RuntimeException( 'A post route was allowed to retire to itself.' );
}
$GLOBALS['fixture_children'][10] = array( new WP_Post( 30, 'child', 'publish', 'page', 10 ) );
if ( 'source_has_public_children' !== ( url_change_lockdown_preview_post_retirement( 10, 20 )['code'] ?? '' ) ) {
	throw new RuntimeException( 'A source route with public child routes was allowed to retire.' );
}
$GLOBALS['fixture_children'][10] = array();

$mismatch = url_change_lockdown_execute_post_retirement(
	array( 'source_post_id' => 10, 'target_post_id' => 20, 'reason' => 'Consolidate duplicate plugin pages.', 'confirmation' => str_repeat( '0', 64 ) )
);
if ( 'confirmation_mismatch' !== ( $mismatch['code'] ?? '' ) || 'publish' !== $GLOBALS['fixture_posts'][10]->post_status ) {
	throw new RuntimeException( 'A stale retirement confirmation changed the source post.' );
}

$result = url_change_lockdown_execute_post_retirement(
	array( 'source_post_id' => 10, 'target_post_id' => 20, 'reason' => 'Consolidate duplicate plugin pages.', 'confirmation' => $preview['confirmation'] )
);
$audit = get_option( URL_CHANGE_LOCKDOWN_AUDIT_OPTION, array() );
if (
	empty( $result['success'] )
	|| 'draft' !== $GLOBALS['fixture_posts'][10]->post_status
	|| 'publish' !== $GLOBALS['fixture_posts'][20]->post_status
	|| array( array( '/plugins/old-plugin/', 'https://staging.devenia.com/plugins/canonical-plugin/' ) ) !== $GLOBALS['fixture_redirect_calls']
	|| 'post_route_retired' !== ( $audit[0]['operation'] ?? '' )
	|| 91 !== ( $audit[0]['redirect_id'] ?? 0 )
	|| 7 !== ( $audit[0]['actor_user_id'] ?? 0 )
	|| 'url_lockdown_public_route_retired' !== ( $GLOBALS['fixture_actions'][0][0] ?? '' )
) {
	throw new RuntimeException( 'The confirmed post route was not retired with one redirect and audit row.' );
}

$GLOBALS['fixture_posts'][10]->post_status = 'publish';
$GLOBALS['fixture_redirect_calls'] = array();
$GLOBALS['fixture_redirect_result'] = new WP_Error( 'redirect_failed', 'Redirect failed.' );
$failed_preview = url_change_lockdown_preview_post_retirement( 10, 20 );
$failed = url_change_lockdown_execute_post_retirement(
	array( 'source_post_id' => 10, 'target_post_id' => 20, 'reason' => 'Consolidate duplicate plugin pages.', 'confirmation' => $failed_preview['confirmation'] )
);
if ( 'redirect_creation_failed_rolled_back' !== ( $failed['code'] ?? '' ) || 'publish' !== $GLOBALS['fixture_posts'][10]->post_status ) {
	throw new RuntimeException( 'A failed retirement redirect did not restore the public source post.' );
}

url_change_lockdown_register_abilities();
foreach ( array( 'url-lockdown/preview-post-retirement', 'url-lockdown/retire-post-route' ) as $ability ) {
	if ( ! isset( $GLOBALS['fixture_abilities'][ $ability ] ) ) {
		throw new RuntimeException( 'The public post-route retirement ability is missing: ' . $ability );
	}
}

echo "Post route retirement runtime passed.\n";

<?php
/**
 * Preserve localized posts-page paths while Polylang owns language routing.
 *
 * WordPress keeps single posts at the permalink root. Some established
 * multilingual sites instead place translated posts below the translated
 * posts-page slug. This opt-in adapter preserves that established route shape
 * without depending on a translation workflow plugin.
 *
 * @package URLChangeLockdown
 */

defined( 'ABSPATH' ) || exit;

const URL_CHANGE_LOCKDOWN_POLYLANG_POST_ROUTES_OPTION = 'url_change_lockdown_polylang_posts_page_routes';

/** Whether the opt-in route adapter is enabled. */
function url_change_lockdown_polylang_post_routes_enabled(): bool {
	return (bool) get_option( URL_CHANGE_LOCKDOWN_POLYLANG_POST_ROUTES_OPTION, false );
}

/** Enable or disable the adapter through its public configuration seam. */
function url_change_lockdown_configure_polylang_post_routes( bool $enabled, bool $flush = true ): bool {
	$updated = update_option( URL_CHANGE_LOCKDOWN_POLYLANG_POST_ROUTES_OPTION, $enabled, false );
	if ( $flush ) {
		flush_rewrite_rules( false );
	}
	return $updated || $enabled === url_change_lockdown_polylang_post_routes_enabled();
}

/** Return localized route data keyed by Polylang language slug. */
function url_change_lockdown_polylang_post_routes(): array {
	static $routes = null;
	if ( is_array( $routes ) ) {
		return $routes;
	}

	$routes = array();
	if (
		! url_change_lockdown_polylang_post_routes_enabled()
		|| ! function_exists( 'pll_languages_list' )
		|| ! function_exists( 'pll_default_language' )
		|| ! function_exists( 'pll_get_post' )
	) {
		return $routes;
	}

	$posts_page_id = absint( get_option( 'page_for_posts' ) );
	$default        = sanitize_key( (string) pll_default_language() );
	if ( ! $posts_page_id || '' === $default ) {
		return $routes;
	}

	foreach ( pll_languages_list( array( 'hide_empty' => false ) ) as $language ) {
		$language = sanitize_key( (string) $language );
		if ( '' === $language || $language === $default ) {
			continue;
		}
		$localized_posts_page_id = absint( pll_get_post( $posts_page_id, $language ) );
		$page                       = $localized_posts_page_id ? get_post( $localized_posts_page_id ) : null;
		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ) {
			continue;
		}
		$base = trim( (string) get_page_uri( $page ), '/' );
		if ( '' === $base ) {
			continue;
		}
		$routes[ $language ] = array(
			'language'      => $language,
			'posts_page_id' => $localized_posts_page_id,
			'base'          => $base,
		);
	}

	return $routes;
}

/** Keep translated single-post links below the translated posts-page slug. */
function url_change_lockdown_filter_polylang_post_link( string $url, $post ): string {
	if (
		! $post instanceof WP_Post
		||
		'post' !== $post->post_type
		|| ! url_change_lockdown_polylang_post_routes_enabled()
		|| ! function_exists( 'pll_get_post_language' )
	) {
		return $url;
	}

	$language = sanitize_key( (string) pll_get_post_language( $post->ID ) );
	$route    = url_change_lockdown_polylang_post_routes()[ $language ] ?? array();
	if ( empty( $route['base'] ) || '' === (string) $post->post_name ) {
		return $url;
	}

	$path = '/' . $language . '/' . trim( (string) $route['base'], '/' ) . '/' . trim( (string) $post->post_name, '/' ) . '/';
	return home_url( user_trailingslashit( $path, 'single' ) );
}

/** Register one bounded native rewrite per localized posts-page base. */
function url_change_lockdown_register_polylang_post_rewrites(): void {
	foreach ( url_change_lockdown_polylang_post_routes() as $route ) {
		$language = preg_quote( (string) $route['language'], '#' );
		$base     = preg_quote( trim( (string) $route['base'], '/' ), '#' );
		add_rewrite_rule(
			'^' . $language . '/' . $base . '/([^/]+)/?$',
			'index.php?name=$matches[1]&lang=' . rawurlencode( (string) $route['language'] ),
			'top'
		);
	}
}

add_filter( 'post_link', 'url_change_lockdown_filter_polylang_post_link', 40, 2 );
add_action( 'init', 'url_change_lockdown_register_polylang_post_rewrites', 20 );

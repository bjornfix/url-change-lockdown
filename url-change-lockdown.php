<?php
/**
 * Plugin Name: URL Change Lockdown
 * Description: Preserves established public WordPress routes and provides explicit, audited URL migrations.
 * Version: 2.0.3
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: basicus
 * Author URI: https://profiles.wordpress.org/basicus/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-change-lockdown
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const URL_CHANGE_LOCKDOWN_POST_CONTRACT_META = '_url_change_lockdown_canonical_route_v1';
const URL_CHANGE_LOCKDOWN_TERM_CONTRACT_META = '_url_change_lockdown_term_canonical_route_v1';
const URL_CHANGE_LOCKDOWN_AUDIT_OPTION       = 'url_change_lockdown_migration_audit_v1';

/** @var array<string,bool> */
$GLOBALS['url_change_lockdown_migration_scope'] = array();

function url_change_lockdown_normalize_path( string $url_or_path ): string {
	$path = wp_parse_url( $url_or_path, PHP_URL_PATH );
	$path = is_string( $path ) ? $path : $url_or_path;
	return '/' . trim( $path, '/' ) . '/';
}

function url_change_lockdown_post_route( WP_Post $post ): array {
	$url = get_permalink( $post );
	return array(
		'object_type' => 'post',
		'object_id'   => (int) $post->ID,
		'post_type'   => (string) $post->post_type,
		'post_name'   => (string) $post->post_name,
		'post_parent' => (int) $post->post_parent,
		'url'         => $url ? (string) $url : '',
		'path'        => $url ? url_change_lockdown_normalize_path( (string) $url ) : '',
	);
}

function url_change_lockdown_term_route( WP_Term $term ): array {
	$url = get_term_link( $term );
	return array(
		'object_type' => 'term',
		'object_id'   => (int) $term->term_id,
		'taxonomy'    => (string) $term->taxonomy,
		'slug'        => (string) $term->slug,
		'parent'      => (int) $term->parent,
		'url'         => is_wp_error( $url ) ? '' : (string) $url,
		'path'        => is_wp_error( $url ) ? '' : url_change_lockdown_normalize_path( (string) $url ),
	);
}

function url_change_lockdown_is_post_public( WP_Post $post ): bool {
	return 'publish' === $post->post_status && is_post_type_viewable( $post->post_type );
}

function url_change_lockdown_get_post_contract( int $post_id ): array {
	$value = get_post_meta( $post_id, URL_CHANGE_LOCKDOWN_POST_CONTRACT_META, true );
	return is_array( $value ) ? $value : array();
}

function url_change_lockdown_store_post_contract( int $post_id, bool $replace = false ): array {
	$post = get_post( $post_id );
	if ( ! $post || ! url_change_lockdown_is_post_public( $post ) ) {
		return array();
	}
	$existing = url_change_lockdown_get_post_contract( $post_id );
	if ( $existing && ! $replace ) {
		return $existing;
	}
	$route                   = url_change_lockdown_post_route( $post );
	$route['established_at'] = gmdate( 'c' );
	update_post_meta( $post_id, URL_CHANGE_LOCKDOWN_POST_CONTRACT_META, $route );
	return $route;
}

function url_change_lockdown_get_term_contract( int $term_id ): array {
	$value = get_term_meta( $term_id, URL_CHANGE_LOCKDOWN_TERM_CONTRACT_META, true );
	return is_array( $value ) ? $value : array();
}

function url_change_lockdown_store_term_contract( int $term_id, string $taxonomy, bool $replace = false ): array {
	$term = get_term( $term_id, $taxonomy );
	if ( ! $term || is_wp_error( $term ) || ! is_taxonomy_viewable( $taxonomy ) ) {
		return array();
	}
	$existing = url_change_lockdown_get_term_contract( $term_id );
	if ( $existing && ! $replace ) {
		return $existing;
	}
	$route                   = url_change_lockdown_term_route( $term );
	$route['established_at'] = gmdate( 'c' );
	update_term_meta( $term_id, URL_CHANGE_LOCKDOWN_TERM_CONTRACT_META, $route );
	return $route;
}

function url_change_lockdown_in_migration_scope( string $type, int $id ): bool {
	return ! empty( $GLOBALS['url_change_lockdown_migration_scope'][ $type . ':' . $id ] );
}

function url_change_lockdown_guard_post_data( array $data, array $postarr ): array {
	$post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;
	if ( ! $post_id || url_change_lockdown_in_migration_scope( 'post', $post_id ) ) {
		return $data;
	}
	$existing = get_post( $post_id );
	if ( ! $existing || ! url_change_lockdown_is_post_public( $existing ) ) {
		return $data;
	}
	url_change_lockdown_store_post_contract( $post_id );
	$data['post_name'] = (string) $existing->post_name;
	if ( is_post_type_hierarchical( $existing->post_type ) ) {
		$data['post_parent'] = (int) $existing->post_parent;
	}
	return $data;
}

function url_change_lockdown_guard_term_data( array $data, int $term_id, string $taxonomy, array $args ): array {
	unset( $args );
	if ( ! $term_id || url_change_lockdown_in_migration_scope( 'term', $term_id ) ) {
		return $data;
	}
	$existing = get_term( $term_id, $taxonomy );
	if ( ! $existing || is_wp_error( $existing ) || ! is_taxonomy_viewable( $taxonomy ) ) {
		return $data;
	}
	url_change_lockdown_store_term_contract( $term_id, $taxonomy );
	$data['slug'] = (string) $existing->slug;
	return $data;
}

function url_change_lockdown_guard_term_parent( int $parent, int $term_id, string $taxonomy ): int {
	if ( ! $term_id || url_change_lockdown_in_migration_scope( 'term', $term_id ) ) {
		return $parent;
	}
	$existing = get_term( $term_id, $taxonomy );
	if ( ! $existing || is_wp_error( $existing ) || ! is_taxonomy_viewable( $taxonomy ) ) {
		return $parent;
	}
	url_change_lockdown_store_term_contract( $term_id, $taxonomy );
	return (int) $existing->parent;
}

function url_change_lockdown_guard_permalink_option( $new_value, $old_value ) {
	return ! empty( $GLOBALS['url_change_lockdown_migration_scope']['site:permalinks'] ) ? $new_value : $old_value;
}

function url_change_lockdown_capture_published_post( int $post_id, $post ): void {
	if ( $post instanceof WP_Post && ! wp_is_post_revision( $post_id ) ) {
		url_change_lockdown_store_post_contract( $post_id );
	}
}

function url_change_lockdown_capture_public_term( int $term_id, int $tt_id, string $taxonomy ): void {
	unset( $tt_id );
	url_change_lockdown_store_term_contract( $term_id, $taxonomy );
}

function url_change_lockdown_descendant_routes( int $post_id ): array {
	$children = get_pages(
		array(
			'child_of'    => $post_id,
			'post_status' => 'publish',
			'sort_column' => 'ID',
		)
	);
	$routes = array();
	foreach ( $children as $child ) {
		if ( $child instanceof WP_Post ) {
			$routes[] = url_change_lockdown_post_route( $child );
		}
	}
	return $routes;
}

function url_change_lockdown_preview_post_migration( int $post_id, string $new_slug, int $new_parent = -1 ): array {
	$post = get_post( $post_id );
	if ( ! $post || ! url_change_lockdown_is_post_public( $post ) ) {
		return array( 'success' => false, 'code' => 'public_post_not_found', 'message' => 'Published public post or page not found.' );
	}
	$new_slug = sanitize_title( $new_slug );
	if ( '' === $new_slug ) {
		return array( 'success' => false, 'code' => 'invalid_slug', 'message' => 'A non-empty proposed slug is required.' );
	}
	if ( $new_parent < 0 ) {
		$new_parent = (int) $post->post_parent;
	}
	if ( ! is_post_type_hierarchical( $post->post_type ) ) {
		$new_parent = 0;
	} elseif ( $new_parent === $post_id || ( $new_parent && in_array( $post_id, get_post_ancestors( $new_parent ), true ) ) ) {
		return array( 'success' => false, 'code' => 'invalid_parent', 'message' => 'The proposed parent would create an invalid hierarchy.' );
	}
	$old_route    = url_change_lockdown_post_route( $post );
	$old_children = url_change_lockdown_descendant_routes( $post_id );
	$sample       = clone $post;
	$sample->post_name   = $new_slug;
	$sample->post_parent = $new_parent;
	$new_url = get_permalink( $sample );
	$new_route = array_merge( $old_route, array( 'post_name' => $new_slug, 'post_parent' => $new_parent, 'url' => (string) $new_url, 'path' => url_change_lockdown_normalize_path( (string) $new_url ) ) );
	$affected = array();
	foreach ( $old_children as $child ) {
		$old_path = (string) $child['path'];
		$new_path = preg_replace( '#^' . preg_quote( (string) $old_route['path'], '#' ) . '#', (string) $new_route['path'], $old_path );
		$affected[] = array( 'object_id' => (int) $child['object_id'], 'old_path' => $old_path, 'new_path' => is_string( $new_path ) ? $new_path : '' );
	}
	return array( 'success' => true, 'object_id' => $post_id, 'old_route' => $old_route, 'proposed_route' => $new_route, 'affected_children' => $affected, 'confirmation' => hash( 'sha256', wp_json_encode( array( $post_id, $old_route, $new_route, $affected ) ) ) );
}

function url_change_lockdown_create_redirect( string $old_path, string $new_url ) {
	$result = apply_filters( 'url_change_lockdown_create_redirect', null, $old_path, $new_url );
	if ( null !== $result ) {
		return $result;
	}
	if ( ! class_exists( '\\RankMath\\Redirections\\Redirection' ) ) {
		return new WP_Error( 'redirect_adapter_unavailable', 'Rank Math redirection adapter is unavailable.' );
	}
	$redirection = \RankMath\Redirections\Redirection::from(
		array(
			'sources'     => array( array( 'pattern' => ltrim( $old_path, '/' ), 'comparison' => 'exact', 'ignore' => '' ) ),
			'url_to'      => $new_url,
			'header_code' => 301,
			'status'      => 'active',
		)
	);
	$id = $redirection->save();
	return $id ? (int) $id : new WP_Error( 'redirect_create_failed', 'Could not create the permanent redirect.' );
}

function url_change_lockdown_preview_term_migration( int $term_id, string $taxonomy, string $new_slug, int $new_parent = -1 ): array {
	$term = get_term( $term_id, $taxonomy );
	if ( ! $term || is_wp_error( $term ) || ! is_taxonomy_viewable( $taxonomy ) ) {
		return array( 'success' => false, 'code' => 'public_term_not_found', 'message' => 'Public taxonomy term not found.' );
	}
	$new_slug = sanitize_title( $new_slug );
	if ( '' === $new_slug ) {
		return array( 'success' => false, 'code' => 'invalid_slug', 'message' => 'A non-empty proposed slug is required.' );
	}
	if ( $new_parent < 0 ) {
		$new_parent = (int) $term->parent;
	}
	if ( $new_parent === $term_id || ( $new_parent && term_is_ancestor_of( $term_id, $new_parent, $taxonomy ) ) ) {
		return array( 'success' => false, 'code' => 'invalid_parent', 'message' => 'The proposed parent would create an invalid taxonomy hierarchy.' );
	}
	$old_route = url_change_lockdown_term_route( $term );
	$old_children = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'child_of' => $term_id ) );
	$affected = array();
	if ( ! is_wp_error( $old_children ) ) {
		foreach ( $old_children as $child ) {
			$child_route = url_change_lockdown_term_route( $child );
			$affected[] = array( 'object_id' => (int) $child->term_id, 'old_path' => (string) $child_route['path'] );
		}
	}
	$proposed = array_merge( $old_route, array( 'slug' => $new_slug, 'parent' => $new_parent ) );
	$confirmation = hash( 'sha256', wp_json_encode( array( $term_id, $taxonomy, $old_route, $proposed, $affected ) ) );
	return array( 'success' => true, 'object_id' => $term_id, 'taxonomy' => $taxonomy, 'old_route' => $old_route, 'proposed_route' => $proposed, 'affected_children' => $affected, 'confirmation' => $confirmation );
}

function url_change_lockdown_execute_term_migration( array $input ): array {
	$term_id   = absint( $input['term_id'] ?? 0 );
	$taxonomy  = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
	$new_slug  = sanitize_title( (string) ( $input['new_slug'] ?? '' ) );
	$new_parent = array_key_exists( 'new_parent_id', $input ) ? absint( $input['new_parent_id'] ) : -1;
	$reason    = sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) );
	$preview   = url_change_lockdown_preview_term_migration( $term_id, $taxonomy, $new_slug, $new_parent );
	if ( mb_strlen( trim( $reason ) ) < 12 || empty( $preview['success'] ) || ! hash_equals( (string) ( $preview['confirmation'] ?? '' ), (string) ( $input['confirmation'] ?? '' ) ) ) {
		return array( 'success' => false, 'code' => 'migration_not_confirmed', 'message' => 'A concrete reason and current matching confirmation are required.', 'preview' => $preview );
	}
	$new_parent = (int) $preview['proposed_route']['parent'];
	$GLOBALS['url_change_lockdown_migration_scope'][ 'term:' . $term_id ] = true;
	$result = wp_update_term( $term_id, $taxonomy, array( 'slug' => $new_slug, 'parent' => $new_parent ) );
	unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'term:' . $term_id ] );
	if ( is_wp_error( $result ) ) {
		return array( 'success' => false, 'code' => 'term_update_failed', 'message' => $result->get_error_message() );
	}
	$term = get_term( $term_id, $taxonomy );
	$observed = $term && ! is_wp_error( $term ) ? url_change_lockdown_term_route( $term ) : array();
	$redirect_pairs = array( array( 'old_path' => $preview['old_route']['path'], 'new_url' => $observed['url'] ?? '' ) );
	foreach ( $preview['affected_children'] as $child ) {
		$child_term = get_term( (int) $child['object_id'], $taxonomy );
		if ( $child_term && ! is_wp_error( $child_term ) ) {
			$child_url = get_term_link( $child_term );
			if ( ! is_wp_error( $child_url ) ) {
				$redirect_pairs[] = array( 'old_path' => $child['old_path'], 'new_url' => (string) $child_url );
			}
		}
	}
	$redirects = array();
	foreach ( $redirect_pairs as $pair ) {
		$id = url_change_lockdown_create_redirect( (string) $pair['old_path'], (string) $pair['new_url'] );
		if ( is_wp_error( $id ) ) {
			$GLOBALS['url_change_lockdown_migration_scope'][ 'term:' . $term_id ] = true;
			wp_update_term( $term_id, $taxonomy, array( 'slug' => $preview['old_route']['slug'], 'parent' => $preview['old_route']['parent'] ) );
			unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'term:' . $term_id ] );
			if ( $redirects && class_exists( '\\RankMath\\Redirections\\DB' ) ) {
				\RankMath\Redirections\DB::delete( $redirects );
			}
			url_change_lockdown_store_term_contract( $term_id, $taxonomy, true );
			return array( 'success' => false, 'code' => 'redirect_creation_failed_rolled_back', 'message' => $id->get_error_message() );
		}
		$redirects[] = (int) $id;
	}
	$contract = url_change_lockdown_store_term_contract( $term_id, $taxonomy, true );
	$audit = array( 'migrated_at' => gmdate( 'c' ), 'actor_user_id' => get_current_user_id(), 'reason' => $reason, 'old_route' => $preview['old_route'], 'new_route' => $contract, 'affected_children' => $preview['affected_children'], 'redirect_ids' => $redirects );
	url_change_lockdown_append_audit( $audit );
	return array( 'success' => true, 'message' => 'Taxonomy route migrated and permanent redirects created.', 'migration' => $audit );
}

function url_change_lockdown_append_audit( array $row ): void {
	$rows   = get_option( URL_CHANGE_LOCKDOWN_AUDIT_OPTION, array() );
	$rows   = is_array( $rows ) ? $rows : array();
	$rows[] = $row;
	if ( count( $rows ) > 500 ) {
		$rows = array_slice( $rows, -500 );
	}
	update_option( URL_CHANGE_LOCKDOWN_AUDIT_OPTION, $rows, false );
}

function url_change_lockdown_execute_post_migration( array $input ): array {
	$post_id    = absint( $input['post_id'] ?? 0 );
	$new_slug   = sanitize_title( (string) ( $input['new_slug'] ?? '' ) );
	$new_parent = array_key_exists( 'new_parent_id', $input ) ? absint( $input['new_parent_id'] ) : -1;
	$reason     = sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) );
	$confirm    = (string) ( $input['confirmation'] ?? '' );
	if ( mb_strlen( trim( $reason ) ) < 12 ) {
		return array( 'success' => false, 'code' => 'reason_required', 'message' => 'A concrete migration reason of at least 12 characters is required.' );
	}
	$preview = url_change_lockdown_preview_post_migration( $post_id, $new_slug, $new_parent );
	if ( empty( $preview['success'] ) || ! hash_equals( (string) $preview['confirmation'], $confirm ) ) {
		return array( 'success' => false, 'code' => 'confirmation_mismatch', 'message' => 'Migration confirmation does not match the current preview.', 'preview' => $preview );
	}
	if ( $preview['old_route']['path'] === $preview['proposed_route']['path'] ) {
		return array( 'success' => false, 'code' => 'route_unchanged', 'message' => 'The proposed public route is unchanged.' );
	}
	$new_parent = (int) $preview['proposed_route']['post_parent'];
	$GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] = true;
	$result = wp_update_post( array( 'ID' => $post_id, 'post_name' => $new_slug, 'post_parent' => $new_parent ), true );
	unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] );
	if ( is_wp_error( $result ) ) {
		return array( 'success' => false, 'code' => 'route_update_failed', 'message' => $result->get_error_message() );
	}
	$post     = get_post( $post_id );
	$observed = $post ? url_change_lockdown_post_route( $post ) : array();
	if ( empty( $observed ) || $observed['path'] !== $preview['proposed_route']['path'] ) {
		$GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] = true;
		wp_update_post( array( 'ID' => $post_id, 'post_name' => $preview['old_route']['post_name'], 'post_parent' => $preview['old_route']['post_parent'] ) );
		unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] );
		return array( 'success' => false, 'code' => 'route_verification_failed_rolled_back', 'message' => 'Observed route did not match the confirmed route; the old route was restored.', 'observed_route' => $observed );
	}
	$redirects = array();
	$redirect_pairs = array( array( 'old_path' => $preview['old_route']['path'], 'new_url' => $observed['url'] ) );
	foreach ( $preview['affected_children'] as $child ) {
		$child_post = get_post( (int) $child['object_id'] );
		if ( $child_post ) {
			$redirect_pairs[] = array( 'old_path' => $child['old_path'], 'new_url' => (string) get_permalink( $child_post ) );
		}
	}
	foreach ( $redirect_pairs as $pair ) {
		$redirect_id = url_change_lockdown_create_redirect( (string) $pair['old_path'], (string) $pair['new_url'] );
		if ( is_wp_error( $redirect_id ) ) {
			$GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] = true;
			wp_update_post( array( 'ID' => $post_id, 'post_name' => $preview['old_route']['post_name'], 'post_parent' => $preview['old_route']['post_parent'] ) );
			unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] );
			if ( $redirects && class_exists( '\\RankMath\\Redirections\\DB' ) ) {
				\RankMath\Redirections\DB::delete( $redirects );
			}
			url_change_lockdown_store_post_contract( $post_id, true );
			return array( 'success' => false, 'code' => 'redirect_creation_failed_rolled_back', 'message' => $redirect_id->get_error_message(), 'created_redirect_ids' => $redirects );
		}
		$redirects[] = (int) $redirect_id;
	}
	$finalization = apply_filters( 'url_lockdown_finalize_post_migration', array( 'success' => true ), $post_id );
	if ( ! is_array( $finalization ) || empty( $finalization['success'] ) ) {
		$GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] = true;
		wp_update_post( array( 'ID' => $post_id, 'post_name' => $preview['old_route']['post_name'], 'post_parent' => $preview['old_route']['post_parent'] ) );
		unset( $GLOBALS['url_change_lockdown_migration_scope'][ 'post:' . $post_id ] );
		if ( $redirects && class_exists( '\\RankMath\\Redirections\\DB' ) ) {
			\RankMath\Redirections\DB::delete( $redirects );
		}
		url_change_lockdown_store_post_contract( $post_id, true );
		return array( 'success' => false, 'code' => 'migration_finalization_failed_rolled_back', 'message' => (string) ( $finalization['message'] ?? 'Migration finalization failed; the old route was restored.' ), 'finalization' => $finalization );
	}
	$contract = url_change_lockdown_store_post_contract( $post_id, true );
	$audit = array( 'migrated_at' => gmdate( 'c' ), 'actor_user_id' => get_current_user_id(), 'reason' => $reason, 'old_route' => $preview['old_route'], 'new_route' => $contract, 'affected_children' => $preview['affected_children'], 'redirect_ids' => $redirects );
	$audit['finalization'] = $finalization;
	url_change_lockdown_append_audit( $audit );
	do_action( 'url_lockdown_public_route_migrated', $post_id, $preview['old_route'], $contract, $audit );
	return array( 'success' => true, 'message' => 'Public route migrated and permanent redirects created.', 'migration' => $audit );
}

function url_change_lockdown_audit( array $input = array() ): array {
	$limit  = max( 1, min( 500, absint( $input['limit'] ?? 100 ) ) );
	$offset = absint( $input['offset'] ?? 0 );
	$ids = get_posts( array( 'post_type' => 'any', 'post_status' => 'publish', 'numberposts' => $limit, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'suppress_filters' => false ) );
	$rows = array();
	foreach ( $ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! url_change_lockdown_is_post_public( $post ) ) {
			continue;
		}
		$contract = url_change_lockdown_get_post_contract( $post_id );
		$observed = url_change_lockdown_post_route( $post );
		$state = ! $contract ? 'missing_contract' : ( (string) ( $contract['path'] ?? '' ) === (string) $observed['path'] ? 'stable' : 'drift' );
		$rows[] = array( 'post_id' => $post_id, 'state' => $state, 'established_route' => $contract, 'observed_route' => $observed );
	}
	$term_rows = array();
	foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $taxonomy ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => $limit, 'offset' => $offset, 'orderby' => 'term_id', 'order' => 'ASC' ) );
		if ( is_wp_error( $terms ) ) { continue; }
		foreach ( $terms as $term ) {
			$contract = url_change_lockdown_get_term_contract( (int) $term->term_id );
			$observed = url_change_lockdown_term_route( $term );
			$state = ! $contract ? 'missing_contract' : ( (string) ( $contract['path'] ?? '' ) === (string) $observed['path'] ? 'stable' : 'drift' );
			$term_rows[] = array( 'term_id' => (int) $term->term_id, 'taxonomy' => $taxonomy, 'state' => $state, 'established_route' => $contract, 'observed_route' => $observed );
		}
	}
	return array( 'success' => true, 'rows' => $rows, 'term_rows' => $term_rows, 'offset' => $offset, 'next_offset' => $offset + count( $ids ), 'has_more' => count( $ids ) === $limit );
}

function url_change_lockdown_register_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}
	$permission = static function (): bool { return current_user_can( 'manage_options' ); };
	wp_register_ability( 'url-lockdown/audit', array( 'label' => 'Audit canonical routes', 'description' => 'Compare established Canonical Route Contracts with current observed public routes.', 'category' => 'site', 'input_schema' => array( 'type' => 'object', 'properties' => array( 'limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 500 ), 'offset' => array( 'type' => 'integer', 'minimum' => 0 ) ), 'additionalProperties' => false ), 'output_schema' => array( 'type' => 'object' ), 'execute_callback' => 'url_change_lockdown_audit', 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) ) ) );
	wp_register_ability( 'url-lockdown/preview-post-migration', array( 'label' => 'Preview post URL migration', 'description' => 'Preview a public post/page URL migration and all affected child routes.', 'category' => 'site', 'input_schema' => array( 'type' => 'object', 'required' => array( 'post_id', 'new_slug' ), 'properties' => array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'new_slug' => array( 'type' => 'string' ), 'new_parent_id' => array( 'type' => 'integer', 'minimum' => 0 ) ), 'additionalProperties' => false ), 'output_schema' => array( 'type' => 'object' ), 'execute_callback' => static function ( $input ): array { return url_change_lockdown_preview_post_migration( absint( $input['post_id'] ?? 0 ), (string) ( $input['new_slug'] ?? '' ), array_key_exists( 'new_parent_id', $input ) ? absint( $input['new_parent_id'] ) : -1 ); }, 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) ) ) );
	wp_register_ability( 'url-lockdown/migrate-post-route', array( 'label' => 'Migrate post URL', 'description' => 'Explicitly migrate a confirmed public post/page route, create permanent redirects, and record audit evidence.', 'category' => 'site', 'input_schema' => array( 'type' => 'object', 'required' => array( 'post_id', 'new_slug', 'reason', 'confirmation' ), 'properties' => array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'new_slug' => array( 'type' => 'string' ), 'new_parent_id' => array( 'type' => 'integer', 'minimum' => 0 ), 'reason' => array( 'type' => 'string', 'minLength' => 12 ), 'confirmation' => array( 'type' => 'string', 'minLength' => 64, 'maxLength' => 64 ), 'confirm_dangerous_action' => array( 'type' => 'string', 'enum' => array( 'url-lockdown/migrate-post-route' ) ) ), 'additionalProperties' => false ), 'output_schema' => array( 'type' => 'object' ), 'execute_callback' => 'url_change_lockdown_execute_post_migration', 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ) ) ) );
	wp_register_ability( 'url-lockdown/preview-term-migration', array( 'label' => 'Preview term URL migration', 'description' => 'Preview a taxonomy-term URL migration and affected descendants.', 'category' => 'site', 'input_schema' => array( 'type' => 'object', 'required' => array( 'term_id', 'taxonomy', 'new_slug' ), 'properties' => array( 'term_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'taxonomy' => array( 'type' => 'string' ), 'new_slug' => array( 'type' => 'string' ), 'new_parent_id' => array( 'type' => 'integer', 'minimum' => 0 ) ), 'additionalProperties' => false ), 'output_schema' => array( 'type' => 'object' ), 'execute_callback' => static function ( $input ): array { return url_change_lockdown_preview_term_migration( absint( $input['term_id'] ?? 0 ), sanitize_key( (string) ( $input['taxonomy'] ?? '' ) ), (string) ( $input['new_slug'] ?? '' ), array_key_exists( 'new_parent_id', $input ) ? absint( $input['new_parent_id'] ) : -1 ); }, 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) ) ) );
	wp_register_ability( 'url-lockdown/migrate-term-route', array( 'label' => 'Migrate term URL', 'description' => 'Explicitly migrate a confirmed taxonomy route, create permanent redirects, and record audit evidence.', 'category' => 'site', 'input_schema' => array( 'type' => 'object', 'required' => array( 'term_id', 'taxonomy', 'new_slug', 'reason', 'confirmation' ), 'properties' => array( 'term_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'taxonomy' => array( 'type' => 'string' ), 'new_slug' => array( 'type' => 'string' ), 'new_parent_id' => array( 'type' => 'integer', 'minimum' => 0 ), 'reason' => array( 'type' => 'string', 'minLength' => 12 ), 'confirmation' => array( 'type' => 'string', 'minLength' => 64, 'maxLength' => 64 ), 'confirm_dangerous_action' => array( 'type' => 'string', 'enum' => array( 'url-lockdown/migrate-term-route' ) ) ), 'additionalProperties' => false ), 'output_schema' => array( 'type' => 'object' ), 'execute_callback' => 'url_change_lockdown_execute_term_migration', 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ) ) ) );
}

function url_change_lockdown_activate(): void {
	$ids = get_posts( array( 'post_type' => 'any', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false ) );
	foreach ( $ids as $post_id ) {
		url_change_lockdown_store_post_contract( (int) $post_id );
	}
	foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $taxonomy ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { continue; }
		foreach ( $terms as $term ) {
			url_change_lockdown_store_term_contract( (int) $term->term_id, $taxonomy );
		}
	}
}

add_filter( 'wp_insert_post_data', 'url_change_lockdown_guard_post_data', PHP_INT_MAX, 2 );
add_filter( 'wp_update_term_data', 'url_change_lockdown_guard_term_data', PHP_INT_MAX, 4 );
add_filter( 'wp_update_term_parent', 'url_change_lockdown_guard_term_parent', PHP_INT_MAX, 3 );
add_filter( 'pre_update_option_permalink_structure', 'url_change_lockdown_guard_permalink_option', PHP_INT_MAX, 2 );
add_filter( 'pre_update_option_category_base', 'url_change_lockdown_guard_permalink_option', PHP_INT_MAX, 2 );
add_filter( 'pre_update_option_tag_base', 'url_change_lockdown_guard_permalink_option', PHP_INT_MAX, 2 );
add_action( 'save_post', 'url_change_lockdown_capture_published_post', PHP_INT_MAX, 2 );
add_action( 'created_term', 'url_change_lockdown_capture_public_term', 20, 3 );
add_action( 'edited_term', 'url_change_lockdown_capture_public_term', 20, 3 );
add_action( 'wp_abilities_api_init', 'url_change_lockdown_register_abilities' );
register_activation_hook( __FILE__, 'url_change_lockdown_activate' );

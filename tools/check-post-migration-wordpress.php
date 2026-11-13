<?php
/** Isolated native post migration proof. Run with PHP on the dev validation host. */
if ( PHP_SAPI !== 'cli' || ! is_file( '/var/www/dev.devenia.com/wp-load.php' ) ) {
	throw new RuntimeException( 'Run only on the dev WordPress validation host.' );
}
define( 'WP_ADMIN', true );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
register_shutdown_function( static function () { $error = error_get_last(); if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR ), true ) ) { fwrite( STDERR, $error['message'] . "\n" ); } } );
$_SERVER['HTTP_HOST'] = 'dev.devenia.com';
$_SERVER['REQUEST_METHOD'] = 'POST';
require_once '/var/www/dev.devenia.com/wp-includes/plugin.php';
add_filter( 'pre_wp_mail', static function () { return true; } );
add_filter( 'pre_update_option_rank_math_notifications', static function ( $new, $old ) { return $old; }, 10, 2 );
// Only the native redirect owner is bootstrapped; publication workflows never see fixtures.
add_filter( 'option_active_plugins', static function () { return array( 'seo-by-rank-math/rank-math.php' ); } );
add_filter( 'option_rank_math_modules', static function ( $modules ) { return array_values( array_unique( array_merge( (array) $modules, array( 'redirections' ) ) ) ); } );
add_filter( 'option_rank-math-options-general', static function ( $options ) { $options['redirections_post_redirect'] = true; return $options; } );
require '/var/www/dev.devenia.com/wp-load.php';
if ( 'dev.devenia.com' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Wrong validation site.' );
}
require rtrim( $argv[1], '/' ) . '/url-change-lockdown.php';
// Use the publisher's schema in connection-local tables. No saved module or
// database configuration changes are required on the validation site.
$temporary_tables = array();
$temporary_schema = static function ( $groups ) use ( &$temporary_tables ) {
	global $wpdb;
	foreach ( $groups['redirections'] as $name => $schema ) {
		$table = $wpdb->prefix . $name;
		if ( false === $wpdb->query( "CREATE TEMPORARY TABLE {$table} ({$schema}) " . $wpdb->get_charset_collate() ) ) {
			throw new RuntimeException( 'Temporary native redirection schema could not be created.' );
		}
		$temporary_tables[] = $table;
	}
	return array();
};
add_filter( 'rank_math/admin/create_tables', $temporary_schema );
RankMath\Installer::create_tables( array( 'redirections' ) );
remove_filter( 'rank_math/admin/create_tables', $temporary_schema );
$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $admins[0]->ID );
rank_math()->settings->set( 'general', 'redirections_post_redirect', true );
new RankMath\Redirections\Watcher();
$checks = 0;
$check = static function ( $value, $label ) use ( &$checks ) { ++$checks; if ( ! $value ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; };
$rules = static function () { return RankMath\Redirections\DB::get_redirections( array( 'status' => 'any', 'limit' => 10000 ) )['redirections']; };
$original_rules = $rules();
$original_ids = array_map( 'intval', array_column( $original_rules, 'id' ) );
$posts = array();
$prefix = 'url-no-redirect-' . strtolower( wp_generate_password( 10, false, false ) );
$audit = array();
$audit_read = static function () use ( &$audit ) { return $audit; };
$audit_write = static function ( $new, $old ) use ( &$audit ) { $audit = $new; return $old; };
add_filter( 'pre_option_' . URL_CHANGE_LOCKDOWN_AUDIT_OPTION, $audit_read );
add_filter( 'pre_update_option_' . URL_CHANGE_LOCKDOWN_AUDIT_OPTION, $audit_write, 10, 2 );
$create = static function ( $suffix ) use ( &$posts, $prefix ) {
	$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $prefix . $suffix, 'post_name' => $prefix . $suffix, 'post_content' => 'Synthetic route contract.' ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$posts[] = $id;
	return $id;
};
$preview_ability = wp_get_ability( 'url-lockdown/preview-post-migration' );
$execute_ability = wp_get_ability( 'url-lockdown/migrate-post-route' );
$migrate = static function ( $id, $slug, $policy ) use ( $preview_ability, $execute_ability ) {
	$input = array( 'post_id' => $id, 'new_slug' => $slug, 'redirect_policy' => $policy );
	$preview = $preview_ability->execute( $input );
	if ( is_wp_error( $preview ) || empty( $preview['success'] ) ) { throw new RuntimeException( 'Fixture preview failed: ' . wp_json_encode( $preview ) ); }
	return $execute_ability->execute( $input + array( 'reason' => 'Verify one synthetic native route migration.', 'confirmation' => $preview['confirmation'], 'confirm_dangerous_action' => 'url-lockdown/migrate-post-route' ) );
};
$reject_finalization = static function () { return array( 'success' => false, 'message' => 'Synthetic finalization rejection.' ); };
try {
	$check( $preview_ability && $execute_ability, 'Native abilities registered' );
	$check( isset( $preview_ability->get_input_schema()['properties']['redirect_policy'], $execute_ability->get_input_schema()['properties']['redirect_policy'] ), 'Both schemas expose the policy' );
	$watchers = 0;
	foreach ( $GLOBALS['wp_filter']['post_updated']->callbacks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$callback = $entry['function'];
			if ( is_array( $callback ) && is_object( $callback[0] ) && is_a( $callback[0], 'RankMath\\Redirections\\Watcher' ) && 'handle_post_update' === $callback[1] ) { ++$watchers; }
		}
	}
	$check( $watchers > 0, 'Native automatic Rank Math watcher is active in this proof' );
	$auto = $create( '-auto-old' );
	$auto_path = trim( wp_parse_url( get_permalink( $auto ), PHP_URL_PATH ), '/' );
	url_change_lockdown_update_post_route( array( 'ID' => $auto, 'post_name' => $prefix . '-auto-new' ), 'permanent' );
	$check( (bool) RankMath\Redirections\DB::match_redirections( $auto_path ), 'Positive control proves the native watcher creates automatic redirects' );
	$id = $create( '-old' );
	$old_slug = get_post( $id )->post_name;
	$new_slug = $prefix . '-new';
	$default = $preview_ability->execute( array( 'post_id' => $id, 'new_slug' => $new_slug ) );
	$none = $preview_ability->execute( array( 'post_id' => $id, 'new_slug' => $new_slug, 'redirect_policy' => 'none' ) );
	$check( 'permanent' === $default['redirect_policy'] && $default['confirmation'] !== $none['confirmation'], 'Default preserved and policy bound to confirmation' );
	$stale = $execute_ability->execute( array( 'post_id' => $id, 'new_slug' => $new_slug, 'redirect_policy' => 'none', 'reason' => 'Reject a confirmation from another policy.', 'confirmation' => $default['confirmation'], 'confirm_dangerous_action' => 'url-lockdown/migrate-post-route' ) );
	$check( 'confirmation_mismatch' === ( $stale['code'] ?? '' ) && $old_slug === get_post( $id )->post_name, 'Wrong-policy confirmation cannot write' );
	add_post_meta( $id, '_wp_old_slug', $prefix . '-historical' );
	$rules_before = $rules();
	$result = $migrate( $id, $new_slug, 'none' );
	$check( ! is_wp_error( $result ) && ! empty( $result['success'] ), 'No-redirect migration succeeds through the native ability' );
	$check( $new_slug === get_post( $id )->post_name && get_permalink( $id ) === url_change_lockdown_get_post_contract( $id )['url'], 'Saved slug and canonical contract match' );
	$check( array( $prefix . '-historical' ) === get_post_meta( $id, '_wp_old_slug' ), 'No new core old-slug alias; earlier alias retained' );
	$check( $rules_before === $rules(), 'Rank Math watcher creates or changes no rule' );
	$check( 'none' === $result['migration']['redirect_policy'] && array() === $result['migration']['redirect_ids'], 'Returned audit records the no-redirect decision' );
	wp_update_post( array( 'ID' => $id, 'post_name' => $prefix . '-machine' ) );
	$check( $new_slug === get_post( $id )->post_name && ! $GLOBALS['url_change_lockdown_migration_scope'], 'Ordinary machine guard and migration scope restored' );
	add_filter( 'url_lockdown_finalize_post_migration', $reject_finalization );
	$failed = $migrate( $id, $prefix . '-rejected', 'none' );
	remove_filter( 'url_lockdown_finalize_post_migration', $reject_finalization );
	$check( 'migration_finalization_failed_rolled_back' === ( $failed['code'] ?? '' ) && $new_slug === get_post( $id )->post_name && $rules_before === $rules(), 'Existing finalization veto rolls back without a redirect' );
	$control = $create( '-default-old' );
	$control_old = get_post( $control )->post_name;
	$control_result = $migrate( $control, $prefix . '-default-new', 'permanent' );
	$check( ! empty( $control_result['success'] ) && ! empty( $control_result['migration']['redirect_ids'] ) && in_array( $control_old, get_post_meta( $control, '_wp_old_slug' ), true ), 'Default migration still creates native permanent redirects and core alias' );
	$conflict = RankMath\Redirections\Redirection::from( array( 'sources' => array( array( 'pattern' => trim( wp_parse_url( get_permalink( $id ), PHP_URL_PATH ), '/' ), 'comparison' => 'exact' ) ), 'url_to' => home_url( '/unrelated-fixture-target/' ), 'header_code' => 301, 'status' => 'active' ) )->save();
	$blocked = $preview_ability->execute( array( 'post_id' => $id, 'new_slug' => $prefix . '-conflict', 'redirect_policy' => 'none' ) );
	$check( 'existing_redirect_conflict' === ( $blocked['code'] ?? '' ) && $conflict && RankMath\Redirections\DB::get_redirection_by_id( $conflict ), 'Existing matching rule blocks the operation and is preserved' );
	echo "Native post migration checks passed: {$checks}/{$checks}.\n";
} finally {
	remove_filter( 'url_lockdown_finalize_post_migration', $reject_finalization );
	foreach ( array_reverse( $posts ) as $id ) { wp_delete_post( $id, true ); }
	$created_ids = array_diff( array_map( 'intval', array_column( $rules(), 'id' ) ), $original_ids );
	if ( $created_ids ) { RankMath\Redirections\DB::delete( array_values( $created_ids ) ); }
	remove_filter( 'pre_option_' . URL_CHANGE_LOCKDOWN_AUDIT_OPTION, $audit_read );
	remove_filter( 'pre_update_option_' . URL_CHANGE_LOCKDOWN_AUDIT_OPTION, $audit_write, 10 );
	$check( $original_rules === $rules(), 'Synthetic posts and redirect rows removed; original rules unchanged' );
	foreach ( $temporary_tables as $table ) { $wpdb->query( "DROP TEMPORARY TABLE {$table}" ); }
}

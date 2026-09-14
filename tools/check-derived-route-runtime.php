<?php
/** Contract for same-site derived route previews. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
class WP_Post { public $ID=10; public $post_status='publish'; public $post_type='page'; public $post_name='old'; public $post_parent=0; }
function add_filter(...$args): void {}
function add_action(...$args): void {}
function register_activation_hook(...$args): void {}
function get_post($id) { return new WP_Post(); }
function is_post_type_viewable($type): bool { return true; }
function home_url(): string { return $GLOBALS['fixture_home']; }
function esc_url_raw($url): string { return $url; }
function wp_parse_url($url, $component=-1) { return parse_url($url, $component); }
function get_permalink($post): string { return home_url() . '/old/'; }
function wp_json_encode($value): string { return json_encode($value); }
require dirname(__DIR__) . '/url-change-lockdown.php';
$GLOBALS['fixture_home']='https://example.com/site';
foreach (array('https://example.com/site/new/' => true, 'https://example.com.evil.test/site/new/' => false, 'https://example.com/site-other/new/' => false, 'https://example.com:444/site/new/' => false, 'https://example.com/site@evil.test/new/' => false) as $url => $expected) {
    $result=url_change_lockdown_preview_derived_post_route_migration(10,$url);
    if (($result['success'] ?? false) !== $expected) throw new RuntimeException('Wrong site binding for ' . $url);
}
$GLOBALS['fixture_home']='https://example.com';
foreach (array('https://example.com/new/' => true, 'https://example.com.evil.test/new/' => false, 'https://example.com@evil.test/new/' => false) as $url => $expected) {
    $result=url_change_lockdown_preview_derived_post_route_migration(10,$url);
    if (($result['success'] ?? false) !== $expected) throw new RuntimeException('Wrong root-site binding for ' . $url);
}
echo "Derived route site binding passed.\n";

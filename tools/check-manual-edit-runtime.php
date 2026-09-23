<?php
/** Exercise the public write hooks with browser and machine authentication. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('REST_REQUEST', in_array('rest', $argv, true));
define('WP_CLI', in_array('cli', $argv, true));
class WP_Post { public $ID=10; public $post_status='publish'; public $post_type='page'; public $post_name='old'; public $post_parent=0; }
class WP_Term { public $term_id=20; public $taxonomy='category'; public $slug='old'; public $parent=0; }
$hooks=[]; $meta=[]; $cookie=1; $user=1; $cap=true; $admin=true; $cron=false;
$post=new WP_Post(); $term=new WP_Term();
function add_filter($hook,$fn,$priority=10,$args=1): void { $GLOBALS['hooks'][$hook][]=$fn; }
function add_action(...$args): void { add_filter(...$args); }
function register_activation_hook(...$args): void {}
function apply_filters($hook,$value,...$args) { foreach ($GLOBALS['hooks'][$hook]??[] as $fn) $value=$fn($value,...$args); return $value; }
function do_action($hook,...$args): void { foreach ($GLOBALS['hooks'][$hook]??[] as $fn) $fn(...$args); }
function absint($id): int { return abs((int)$id); }
function get_post($id) { return $GLOBALS['post']; }
function get_term($id,$tax='') { return $GLOBALS['term']; }
function is_post_type_viewable($type): bool { return true; }
function is_post_type_hierarchical($type): bool { return true; }
function is_taxonomy_viewable($type): bool { return true; }
function is_wp_error($value): bool { return false; }
function wp_is_post_revision($id): bool { return false; }
function wp_doing_cron(): bool { return $GLOBALS['cron']; }
function is_admin(): bool { return $GLOBALS['admin']; }
function get_current_user_id(): int { return $GLOBALS['user']; }
function wp_validate_auth_cookie($cookie='',$scheme='') { return $GLOBALS['cookie']; }
function current_user_can(...$args): bool { return $GLOBALS['cap']; }
function wp_verify_nonce($value,$action): bool { return $value==='valid' && $action==='wp_rest'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return $value; }
function wp_parse_url($url,$part=-1) { return parse_url($url,$part); }
function get_permalink($post): string { return 'https://example.com/'.$post->post_name.'/'; }
function get_term_link($term): string { return 'https://example.com/category/'.$term->slug.'/'; }
function get_post_meta($id,$key,$single=true) { return $GLOBALS['meta']['post'][$id]??[]; }
function update_post_meta($id,$key,$value): void { $GLOBALS['meta']['post'][$id]=$value; }
function get_term_meta($id,$key,$single=true) { return $GLOBALS['meta']['term'][$id]??[]; }
function update_term_meta($id,$key,$value): void { $GLOBALS['meta']['term'][$id]=$value; }
function get_pages($args): array { return []; }
function get_term_children($id,$taxonomy): array { return []; }
require dirname(__DIR__).'/url-change-lockdown.php';
function check($actual,$expected,$message): void { if ($actual!==$expected) throw new RuntimeException($message); }
function verify_writes(bool $allowed): void {
    $data=apply_filters('wp_insert_post_data',['post_name'=>'new','post_parent'=>30],['ID'=>10]);
    check($data['post_name'],$allowed?'new':'old','Post slug save');
    check($data['post_parent'],$allowed?30:0,'Post parent save');
    $data=apply_filters('wp_update_term_data',['slug'=>'new'],20,'category',[]);
    check($data['slug'],$allowed?'new':'old','Term slug save');
    check(apply_filters('wp_update_term_parent',30,20,'category'),$allowed?30:0,'Term parent save');
    foreach (['permalink_structure','category_base','tag_base'] as $option) check(apply_filters('pre_update_option_'.$option,'new','old'),$allowed?'new':'old','Permalink setting save');
}
if (REST_REQUEST) { $admin=false; $_SERVER['HTTP_X_WP_NONCE']='valid'; }
verify_writes(!WP_CLI);
$cookie=0; verify_writes(false); // Includes application-password/MCP authentication.
$cookie=2; verify_writes(false); // Cookie must authenticate the current actor.
$cookie=1; $cap=false; verify_writes(false);
$cap=true; $cron=true; verify_writes(false);
$cron=false;
if (REST_REQUEST) { $_SERVER['HTTP_X_WP_NONCE']='invalid'; verify_writes(false); $_SERVER['HTTP_X_WP_NONCE']='valid'; }
if (!WP_CLI) {
    $post->post_name='saved'; do_action('save_post',10,$post);
    check($meta['post'][10]['path'],'/saved/','Manual post contract refresh');
    $post->post_name='permalink-manager'; do_action('permalink_manager_updated_post_uri',10);
    check($meta['post'][10]['path'],'/permalink-manager/','Permalink Manager contract refresh');
    $term->slug='saved'; do_action('edited_term',20,20,'category');
    check($meta['term'][20]['slug'],'saved','Manual term contract refresh');
    $cookie=0; $post->post_name='machine'; do_action('save_post',10,$post);
    check($meta['post'][10]['path'],'/permalink-manager/','Machine save must not replace contract');
}
echo "Manual edit write-hook checks passed.\n";

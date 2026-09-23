<?php
/** Run with PHP (not WP-CLI) on the isolated dev validation site. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !is_file('/var/www/dev.devenia.com/wp-load.php')) {
    throw new RuntimeException('Run only on the dev WordPress validation host.');
}
define('WP_ADMIN', true);
$_SERVER['HTTP_HOST']='dev.devenia.com';
$_SERVER['REQUEST_METHOD']='POST';
// Keep unrelated publication workflows out of these disposable route fixtures.
// This affects only this PHP process, not the site's saved plugin state.
require_once '/var/www/dev.devenia.com/wp-includes/plugin.php';
add_filter('option_active_plugins', static function () { return ['url-change-lockdown/url-change-lockdown.php']; });
require '/var/www/dev.devenia.com/wp-load.php';
if (parse_url(home_url(), PHP_URL_HOST)!=='dev.devenia.com') throw new RuntimeException('Wrong site.');
function expect_manual($condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
$admins=get_users(['role'=>'administrator','number'=>1]);
$user=$admins[0];
wp_set_current_user($user->ID);
$sessions=WP_Session_Tokens::get_instance($user->ID);
$expires=time()+300;
$token=$sessions->create($expires);
$cookie=wp_generate_auth_cookie($user->ID,$expires,'logged_in',$token);
$posts=[]; $terms=[];
$pm_options_before=get_option('permalink-manager-uris',null);
$prefix='url-lockdown-test-'.strtolower(wp_generate_password(8,false,false));
try {
    $parent=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$prefix,'post_name'=>$prefix]);
    $posts[]=$parent;
    $child=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$prefix.' child','post_name'=>'child','post_parent'=>$parent]);
    $posts[]=$child;
    expect_manual($parent>0 && $child>0 && get_post($parent)->post_status==='publish','Disposable published parent and child created');
    wp_update_post(['ID'=>$parent,'post_name'=>$prefix.'-machine']);
    expect_manual(get_post($parent)->post_name===$prefix,'Administrator without browser cookie remains locked');
    $_COOKIE[LOGGED_IN_COOKIE]=$cookie;
    wp_update_post(['ID'=>$parent,'post_name'=>$prefix.'-manual']);
    expect_manual(get_post($parent)->post_name===$prefix.'-manual','Authenticated manual post save persists');
    expect_manual(url_change_lockdown_get_post_contract($parent)['url']===get_permalink($parent),'Post contract follows manual save');
    expect_manual(url_change_lockdown_get_post_contract($child)['url']===get_permalink($child),'Child contract follows changed parent path');
    $result=wp_insert_term($prefix,'category',['slug'=>$prefix]);
    $term=(int)$result['term_id']; $terms[]=$term;
    $result=wp_insert_term($prefix.' child','category',['slug'=>$prefix.'-child','parent'=>$term]);
    $term_child=(int)$result['term_id']; $terms[]=$term_child;
    wp_update_term($term,'category',['slug'=>$prefix.'-manual']);
    expect_manual(get_term($term)->slug===$prefix.'-manual','Authenticated manual term save persists');
    expect_manual(url_change_lockdown_get_term_contract($term)['url']===get_term_link($term),'Term contract follows manual save');
    expect_manual(url_change_lockdown_get_term_contract($term_child)['url']===get_term_link($term_child),'Child term contract follows changed parent path');
    foreach (['permalink_structure','category_base','tag_base'] as $name) {
        expect_manual(apply_filters('pre_update_option_'.$name,'new','old')==='new','Manual '.$name.' accepted');
    }
    unset($_COOKIE[LOGGED_IN_COOKIE]);
    wp_update_term($term,'category',['slug'=>$prefix.'-machine']);
    expect_manual(get_term($term)->slug===$prefix.'-manual','Machine term save remains locked');
    foreach (['permalink_structure','category_base','tag_base'] as $name) {
        expect_manual(apply_filters('pre_update_option_'.$name,'new','old')==='old','Machine '.$name.' protected');
    }
    $_COOKIE[LOGGED_IN_COOKIE]=$cookie;
    // Load the unmodified publisher package only into this validation process.
    require $argv[1].'/permalink-manager.php';
    $permalink_manager->get_options_and_globals();
    $_POST=[
        'action'=>'pm_save_permalink',
        'permalink-manager-nonce'=>wp_create_nonce('permalink-manager-edit-uri-box'),
        'permalink-manager-edit-uri-element-id'=>$parent,
        'permalink-manager-edit-uri-element-slug'=>1,
        'native_slug'=>$prefix.'-pm-native',
        'custom_uri'=>$prefix.'-pm-custom',
        'auto_update_uri'=>-1,
    ];
    Permalink_Manager_URI_Functions_Post::update_post_hook($parent);
    expect_manual(get_post($parent)->post_name===$prefix.'-pm-native','Permalink Manager native slug persists');
    expect_manual(get_option('permalink-manager-uris')[$parent]===$prefix.'-pm-custom','Permalink Manager custom URI persists');
    expect_manual(url_change_lockdown_get_post_contract($parent)['url']===get_permalink($parent),'Contract matches Permalink Manager route');
    expect_manual(strpos(get_permalink($parent),$prefix.'-pm-custom')!==false,'Permalink Manager serves custom permalink');
    $_POST=[];
} catch (Throwable $error) {
    fwrite(STDERR,$error->getMessage()."\n");
    $failed=true;
} finally {
    $_POST=[];
    unset($_COOKIE[LOGGED_IN_COOKIE]);
    foreach (array_reverse($posts) as $id) wp_delete_post($id,true);
    foreach (array_reverse($terms) as $id) wp_delete_term($id,'category');
    if ($pm_options_before===null) delete_option('permalink-manager-uris');
    else update_option('permalink-manager-uris',$pm_options_before);
    $sessions->destroy($token);
    echo "Temporary posts, terms, Permalink Manager URI data and session removed.\n";
}
exit(empty($failed)?0:1);

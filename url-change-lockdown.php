<?php
/**
 * Plugin Name: URL Change Lockdown
 * Description: Freezes existing post and taxonomy slugs unless explicitly unlocked.
 * Version: 1.4.3
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: basicus
 * Author URI: https://profiles.wordpress.org/basicus/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-change-lockdown
 */

if (!defined('ABSPATH')) {
    exit;
}

function url_change_lockdown_allow_slug_changes(): bool
{
    if (defined('URL_LOCKDOWN_ALLOW') && URL_LOCKDOWN_ALLOW) {
        return true;
    }

    return defined('WP_CLI') && WP_CLI && defined('URL_LOCKDOWN_ALLOW_CLI') && URL_LOCKDOWN_ALLOW_CLI;
}

function url_change_lockdown_is_admin_referer(): bool
{
    $referer = function_exists('wp_get_raw_referer') ? wp_get_raw_referer() : '';
    if (!$referer) {
        return false;
    }

    $admin_url = admin_url();

    return is_string($referer) && strpos($referer, $admin_url) === 0;
}

function url_change_lockdown_is_manual_admin_request(): bool
{
    if (defined('WP_CLI') && WP_CLI) {
        return false;
    }

    if (is_admin()) {
        return true;
    }

    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return false;
    }

    $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return false;
    }

    return url_change_lockdown_is_admin_referer();
}

function url_change_lockdown_guard_post_data(array $data, array $postarr): array
{
    if (url_change_lockdown_allow_slug_changes()) {
        return $data;
    }

    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
    if ($post_id <= 0) {
        return $data;
    }

    $existing = get_post($post_id);
    if (!$existing || is_wp_error($existing)) {
        return $data;
    }

    if (isset($data['post_name']) && $data['post_name'] !== $existing->post_name) {
        if (url_change_lockdown_is_manual_admin_request() && current_user_can('edit_post', $post_id)) {
            return $data;
        }

        $data['post_name'] = $existing->post_name;
    }

    return $data;
}

function url_change_lockdown_guard_term_data(array $data, int $term_id, string $taxonomy, array $args): array
{
    if (url_change_lockdown_allow_slug_changes()) {
        return $data;
    }

    if ($term_id <= 0) {
        return $data;
    }

    $existing = get_term($term_id, $taxonomy);
    if (!$existing || is_wp_error($existing)) {
        return $data;
    }

    if (isset($data['slug']) && $data['slug'] !== $existing->slug) {
        if (url_change_lockdown_is_manual_admin_request() && current_user_can('edit_term', $term_id)) {
            return $data;
        }

        $data['slug'] = $existing->slug;
    }

    return $data;
}

// Run late so downstream filters cannot reintroduce slug changes.
add_filter('wp_insert_post_data', 'url_change_lockdown_guard_post_data', PHP_INT_MAX, 2);
add_filter('wp_update_term_data', 'url_change_lockdown_guard_term_data', PHP_INT_MAX, 4);

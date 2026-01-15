<?php
/**
 * Plugin Name: URL Change Lockdown
 * Description: Prevents programmatic URL changes (site URLs, slugs, parent pages, taxonomies) unless explicitly allowed.
 * Version: 1.1.2
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

function url_change_lockdown_allow_programmatic(): bool
{
    if (defined('URL_LOCKDOWN_ALLOW') && URL_LOCKDOWN_ALLOW) {
        return true;
    }

    return defined('WP_CLI') && WP_CLI && defined('URL_LOCKDOWN_ALLOW_CLI') && URL_LOCKDOWN_ALLOW_CLI;
}

function url_change_lockdown_is_general_settings_save(): bool
{
    if (!is_admin()) {
        return false;
    }

    if (!current_user_can('manage_options')) {
        return false;
    }

    if (empty($_POST['option_page']) || $_POST['option_page'] !== 'general') {
        return false;
    }

    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
    if ($nonce === '') {
        return false;
    }

    return (bool) wp_verify_nonce($nonce, 'general-options');
}

function url_change_lockdown_is_permalink_settings_save(): bool
{
    if (!is_admin()) {
        return false;
    }

    if (!current_user_can('manage_options')) {
        return false;
    }

    if (empty($_POST['option_page']) || $_POST['option_page'] !== 'permalink') {
        return false;
    }

    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
    if ($nonce === '') {
        return false;
    }

    return (bool) wp_verify_nonce($nonce, 'update-permalink');
}

function url_change_lockdown_is_allowed_option_update(string $option): bool
{
    if (url_change_lockdown_allow_programmatic()) {
        return true;
    }

    if (in_array($option, ['home', 'siteurl'], true)) {
        return url_change_lockdown_is_general_settings_save();
    }

    if (in_array($option, ['permalink_structure', 'category_base', 'tag_base'], true)) {
        return url_change_lockdown_is_permalink_settings_save();
    }

    return false;
}

function url_change_lockdown_prevent_option_update($value, $old_value, $option)
{
    if (url_change_lockdown_is_allowed_option_update($option)) {
        return $value;
    }

    return $old_value;
}

function url_change_lockdown_has_rest_nonce(): bool
{
    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return false;
    }

    $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';
    if ($nonce === '') {
        return false;
    }

    return (bool) wp_verify_nonce($nonce, 'wp_rest');
}

function url_change_lockdown_has_post_nonce(int $post_id): bool
{
    if (!is_admin()) {
        return false;
    }

    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
    if ($nonce !== '' && wp_verify_nonce($nonce, 'update-post_' . $post_id)) {
        return true;
    }

    if ($nonce !== '' && wp_verify_nonce($nonce, 'bulk-posts')) {
        return true;
    }

    $inline_nonce = isset($_POST['_inline_edit']) ? sanitize_text_field(wp_unslash($_POST['_inline_edit'])) : '';
    return $inline_nonce !== '' && wp_verify_nonce($inline_nonce, 'inlineeditnonce');
}

function url_change_lockdown_is_manual_content_change(int $post_id): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    if ($post_id > 0 && !current_user_can('edit_post', $post_id)) {
        return false;
    }

    if (url_change_lockdown_has_rest_nonce()) {
        return true;
    }

    return $post_id > 0 && url_change_lockdown_has_post_nonce($post_id);
}

function url_change_lockdown_guard_post_data(array $data, array $postarr): array
{
    if (url_change_lockdown_allow_programmatic()) {
        return $data;
    }

    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
    if ($post_id <= 0) {
        return $data;
    }

    if (url_change_lockdown_is_manual_content_change($post_id)) {
        return $data;
    }

    $existing = get_post($post_id);
    if (!$existing) {
        return $data;
    }

    if (isset($data['post_name']) && $data['post_name'] !== $existing->post_name) {
        $data['post_name'] = $existing->post_name;
    }

    if (isset($data['post_parent']) && (int) $data['post_parent'] !== (int) $existing->post_parent) {
        $data['post_parent'] = $existing->post_parent;
    }

    if (isset($data['post_type']) && $data['post_type'] !== $existing->post_type) {
        $data['post_type'] = $existing->post_type;
    }

    return $data;
}

function url_change_lockdown_guard_terms($terms, $object_id, $taxonomy, $append, $old_term_ids)
{
    if (url_change_lockdown_allow_programmatic()) {
        return $terms;
    }

    $post_id = (int) $object_id;
    if ($post_id <= 0) {
        return $terms;
    }

    if (url_change_lockdown_is_manual_content_change($post_id)) {
        return $terms;
    }

    $existing_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
    if (is_wp_error($existing_terms)) {
        return $terms;
    }

    return $existing_terms;
}

add_filter('pre_update_option_home', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_siteurl', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_permalink_structure', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_category_base', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_tag_base', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('wp_insert_post_data', 'url_change_lockdown_guard_post_data', 10, 2);
add_filter('pre_set_object_terms', 'url_change_lockdown_guard_terms', 10, 5);

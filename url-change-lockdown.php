<?php
/**
 * Plugin Name: URL Change Lockdown
 * Description: Prevents URL-related changes (settings, slugs, taxonomy links, content links, meta links) unless explicitly allowed.
 * Version: 1.3.0
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

function url_change_lockdown_is_allowed_option_update(string $option): bool
{
    if (!in_array($option, ['home', 'siteurl', 'permalink_structure', 'category_base', 'tag_base'], true)) {
        return true;
    }

    return url_change_lockdown_allow_programmatic();
}

function url_change_lockdown_prevent_option_update($value, $old_value, $option)
{
    if (url_change_lockdown_is_allowed_option_update($option)) {
        return $value;
    }

    return $old_value;
}

function url_change_lockdown_extract_content_urls(string $content): array
{
    if ($content === '') {
        return [];
    }

    $urls = wp_extract_urls($content);
    if (!is_array($urls) || empty($urls)) {
        return [];
    }

    $normalized = array_map(
        static function ($url): string {
            return esc_url_raw((string) $url);
        },
        $urls
    );

    $normalized = array_values(
        array_filter(
            array_unique($normalized),
            static function (string $url): bool {
                return $url !== '';
            }
        )
    );

    sort($normalized);
    return $normalized;
}

function url_change_lockdown_urls_changed(string $before, string $after): bool
{
    return url_change_lockdown_extract_content_urls($before) !== url_change_lockdown_extract_content_urls($after);
}

function url_change_lockdown_collect_urls_from_value($value, array &$url_map, int $depth = 0): void
{
    if ($depth > 8) {
        return;
    }

    if (is_string($value)) {
        foreach (url_change_lockdown_extract_content_urls($value) as $url) {
            $url_map[$url] = true;
        }
        return;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            url_change_lockdown_collect_urls_from_value($item, $url_map, $depth + 1);
        }
        return;
    }

    if (is_object($value)) {
        foreach (get_object_vars($value) as $item) {
            url_change_lockdown_collect_urls_from_value($item, $url_map, $depth + 1);
        }
    }
}

function url_change_lockdown_extract_urls_from_mixed($value): array
{
    $url_map = [];
    url_change_lockdown_collect_urls_from_value($value, $url_map, 0);
    ksort($url_map);
    return array_keys($url_map);
}

function url_change_lockdown_get_post_meta_urls(int $post_id, string $meta_key): array
{
    $values = get_metadata('post', $post_id, $meta_key, false);
    if (!is_array($values) || empty($values)) {
        return [];
    }

    $url_map = [];
    foreach ($values as $value) {
        foreach (url_change_lockdown_extract_urls_from_mixed($value) as $url) {
            $url_map[$url] = true;
        }
    }

    ksort($url_map);
    return array_keys($url_map);
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

    if (isset($data['guid']) && $data['guid'] !== $existing->guid) {
        $data['guid'] = $existing->guid;
    }

    if (
        array_key_exists('post_content', $data) &&
        is_string($data['post_content']) &&
        url_change_lockdown_urls_changed((string) $existing->post_content, $data['post_content'])
    ) {
        $data['post_content'] = (string) $existing->post_content;
    }

    if (
        array_key_exists('post_excerpt', $data) &&
        is_string($data['post_excerpt']) &&
        url_change_lockdown_urls_changed((string) $existing->post_excerpt, $data['post_excerpt'])
    ) {
        $data['post_excerpt'] = (string) $existing->post_excerpt;
    }

    if (
        array_key_exists('post_content_filtered', $data) &&
        is_string($data['post_content_filtered']) &&
        url_change_lockdown_urls_changed((string) $existing->post_content_filtered, $data['post_content_filtered'])
    ) {
        $data['post_content_filtered'] = (string) $existing->post_content_filtered;
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

    $existing_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
    if (is_wp_error($existing_terms)) {
        return $terms;
    }

    return $existing_terms;
}

function url_change_lockdown_guard_update_post_metadata($check, $object_id, $meta_key, $meta_value, $prev_value)
{
    if (null !== $check) {
        return $check;
    }

    if (url_change_lockdown_allow_programmatic()) {
        return $check;
    }

    $post_id = (int) $object_id;
    if ($post_id <= 0 || !is_string($meta_key) || $meta_key === '') {
        return $check;
    }

    $existing_urls = url_change_lockdown_get_post_meta_urls($post_id, $meta_key);
    $new_urls      = url_change_lockdown_extract_urls_from_mixed($meta_value);

    if ($existing_urls !== $new_urls) {
        return false;
    }

    return $check;
}

function url_change_lockdown_guard_add_post_metadata($check, $object_id, $meta_key, $meta_value, $unique)
{
    if (null !== $check) {
        return $check;
    }

    if (url_change_lockdown_allow_programmatic()) {
        return $check;
    }

    $post_id = (int) $object_id;
    if ($post_id <= 0 || !is_string($meta_key) || $meta_key === '') {
        return $check;
    }

    $existing_urls = url_change_lockdown_get_post_meta_urls($post_id, $meta_key);
    $after_map     = array_fill_keys($existing_urls, true);

    foreach (url_change_lockdown_extract_urls_from_mixed($meta_value) as $url) {
        $after_map[$url] = true;
    }

    ksort($after_map);
    $after_urls = array_keys($after_map);

    if ($existing_urls !== $after_urls) {
        return false;
    }

    return $check;
}

function url_change_lockdown_guard_delete_post_metadata($check, $object_id, $meta_key, $meta_value, $delete_all)
{
    if (null !== $check) {
        return $check;
    }

    if (url_change_lockdown_allow_programmatic()) {
        return $check;
    }

    $post_id = (int) $object_id;
    if ($post_id <= 0 || !is_string($meta_key) || $meta_key === '') {
        return $check;
    }

    $existing_urls = url_change_lockdown_get_post_meta_urls($post_id, $meta_key);
    if (empty($existing_urls)) {
        return $check;
    }

    if ($delete_all) {
        return false;
    }

    if ($meta_value === '' || $meta_value === null) {
        return false;
    }

    if (!empty(url_change_lockdown_extract_urls_from_mixed($meta_value))) {
        return false;
    }

    return $check;
}

function url_change_lockdown_guard_term_data(array $data, int $term_id, string $taxonomy, array $args): array
{
    if (url_change_lockdown_allow_programmatic()) {
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
        $data['slug'] = $existing->slug;
    }

    if (isset($data['parent']) && (int) $data['parent'] !== (int) $existing->parent) {
        $data['parent'] = (int) $existing->parent;
    }

    return $data;
}

add_filter('pre_update_option_home', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_siteurl', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_permalink_structure', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_category_base', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('pre_update_option_tag_base', 'url_change_lockdown_prevent_option_update', 10, 3);
add_filter('wp_insert_post_data', 'url_change_lockdown_guard_post_data', 10, 2);
add_filter('pre_set_object_terms', 'url_change_lockdown_guard_terms', 10, 5);
add_filter('update_post_metadata', 'url_change_lockdown_guard_update_post_metadata', 10, 5);
add_filter('add_post_metadata', 'url_change_lockdown_guard_add_post_metadata', 10, 5);
add_filter('delete_post_metadata', 'url_change_lockdown_guard_delete_post_metadata', 10, 5);
add_filter('wp_update_term_data', 'url_change_lockdown_guard_term_data', 10, 4);

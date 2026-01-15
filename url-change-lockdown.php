<?php
/**
 * Plugin Name: URL Change Lockdown
 * Description: Prevents programmatic updates to WordPress site URLs (home/siteurl) unless explicitly allowed.
 * Version: 1.0.0
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

function url_change_lockdown_allow_update(): bool
{
    if (defined('URL_LOCKDOWN_ALLOW') && URL_LOCKDOWN_ALLOW) {
        return true;
    }

    if (defined('WP_CLI') && WP_CLI && defined('URL_LOCKDOWN_ALLOW_CLI') && URL_LOCKDOWN_ALLOW_CLI) {
        return true;
    }

    return url_change_lockdown_is_general_settings_save();
}

function url_change_lockdown_prevent_update($value, $old_value, $option)
{
    if (url_change_lockdown_allow_update()) {
        return $value;
    }

    return $old_value;
}

add_filter('pre_update_option_home', 'url_change_lockdown_prevent_update', 10, 3);
add_filter('pre_update_option_siteurl', 'url_change_lockdown_prevent_update', 10, 3);

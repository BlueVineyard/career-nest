<?php

/**
 * CareerNest Branding Helper Functions
 * 
 * Provides easy access to white-label branding settings throughout the plugin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the platform name
 * 
 * @return string Platform name (defaults to 'CareerNest')
 */
function cn_get_platform_name(): string
{
    $branding = get_option('careernest_branding', []);
    $name = isset($branding['platform_name']) ? trim($branding['platform_name']) : '';

    return !empty($name) ? $name : 'CareerNest';
}

/**
 * Get the platform logo URL
 * 
 * @param string $size Image size (thumbnail, medium, large, full)
 * @return string|false Logo URL or false if no logo set
 */
function cn_get_platform_logo(string $size = 'medium')
{
    $branding = get_option('careernest_branding', []);
    $logo_id = isset($branding['platform_logo']) ? (int) $branding['platform_logo'] : 0;

    if (!$logo_id) {
        return false;
    }

    return wp_get_attachment_image_url($logo_id, $size);
}

/**
 * Get the platform logo HTML img tag
 * 
 * @param string $size Image size
 * @param array $attr Additional attributes for img tag
 * @return string HTML img tag or empty string
 */
function cn_get_platform_logo_html(string $size = 'medium', array $attr = []): string
{
    $branding = get_option('careernest_branding', []);
    $logo_id = isset($branding['platform_logo']) ? (int) $branding['platform_logo'] : 0;

    if (!$logo_id) {
        return '';
    }

    $default_attr = [
        'alt' => cn_get_platform_name(),
        'class' => 'cn-platform-logo',
    ];

    $attr = array_merge($default_attr, $attr);

    return wp_get_attachment_image($logo_id, $size, false, $attr);
}

/**
 * Get the email from name
 * 
 * @return string Email from name (defaults to "The [Platform Name] Team")
 */
function cn_get_email_from_name(): string
{
    $branding = get_option('careernest_branding', []);
    $from_name = isset($branding['email_from_name']) ? trim($branding['email_from_name']) : '';

    if (!empty($from_name)) {
        return $from_name;
    }

    $platform_name = cn_get_platform_name();
    return sprintf('The %s Team', $platform_name);
}

/**
 * Get the email from address
 * 
 * @return string Email from address (defaults to WordPress admin email)
 */
function cn_get_email_from_address(): string
{
    $branding = get_option('careernest_branding', []);
    $from_address = isset($branding['email_from_address']) ? trim($branding['email_from_address']) : '';

    if (!empty($from_address) && is_email($from_address)) {
        return $from_address;
    }

    return get_option('admin_email');
}

/**
 * Get the support email
 * 
 * @return string Support email (defaults to WordPress admin email)
 */
function cn_get_support_email(): string
{
    $branding = get_option('careernest_branding', []);
    $support_email = isset($branding['support_email']) ? trim($branding['support_email']) : '';

    if (!empty($support_email) && is_email($support_email)) {
        return $support_email;
    }

    return get_option('admin_email');
}

/**
 * Override WordPress email from name
 * This filter will override SMTP plugin settings
 */
add_filter('wp_mail_from_name', function ($from_name) {
    return cn_get_email_from_name();
}, 999);

/**
 * Override WordPress email from address
 * This filter will override SMTP plugin settings
 */
add_filter('wp_mail_from', function ($from_email) {
    return cn_get_email_from_address();
}, 999);

/**
 * Replace CareerNest branding in text with platform name
 * 
 * @param string $text Text to replace
 * @return string Text with replaced branding
 */
function cn_replace_branding(string $text): string
{
    $platform_name = cn_get_platform_name();

    // Replace various forms of "CareerNest"
    $replacements = [
        'CareerNest' => $platform_name,
        'careernest' => strtolower(str_replace(' ', '', $platform_name)),
        'The CareerNest Team' => cn_get_email_from_name(),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

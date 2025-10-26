<?php

/**
 * Plugin Name: CareerNest
 * Description: Standalone job portal plugin using only WordPress core APIs.
 * Version: 1.0.0
 * Author: Rohan T George
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: careernest
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

// Basic constants.
define('CAREERNEST_VERSION', '1.0.0');
define('CAREERNEST_FILE', __FILE__);
define('CAREERNEST_DIR', plugin_dir_path(__FILE__));
define('CAREERNEST_URL', plugin_dir_url(__FILE__));

// Includes.
require_once CAREERNEST_DIR . 'includes/branding-helpers.php';
require_once CAREERNEST_DIR . 'includes/class-activator.php';
require_once CAREERNEST_DIR . 'includes/class-deactivator.php';
require_once CAREERNEST_DIR . 'includes/class-plugin.php';
require_once CAREERNEST_DIR . 'includes/class-ajax-handler.php';
require_once CAREERNEST_DIR . 'includes/Data/class-cpt.php';
require_once CAREERNEST_DIR . 'includes/Data/class-taxonomies.php';
require_once CAREERNEST_DIR . 'includes/Data/class-roles.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-admin.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-admin-menus.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-meta-boxes.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-admin-columns.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-users.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-settings.php';
require_once CAREERNEST_DIR . 'includes/Security/class-caps.php';
require_once CAREERNEST_DIR . 'includes/Shortcodes/class-login.php';
require_once CAREERNEST_DIR . 'includes/Shortcodes/class-job-search-widget.php';
require_once CAREERNEST_DIR . 'includes/Shortcodes/class-employer-carousel.php';
require_once CAREERNEST_DIR . 'includes/Shortcodes/class-job-categories.php';
require_once CAREERNEST_DIR . 'includes/Shortcodes/class-jobs-by-category.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-employer-requests.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-employee-requests.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-deletion-requests.php';
require_once CAREERNEST_DIR . 'includes/Admin/class-import-export.php';
require_once CAREERNEST_DIR . 'includes/Email/class-mailer.php';
require_once CAREERNEST_DIR . 'includes/Email/class-templates.php';
require_once CAREERNEST_DIR . 'includes/class-job-ajax-handler.php';
require_once CAREERNEST_DIR . 'includes/class-applicant-notifications.php';
require_once CAREERNEST_DIR . 'includes/class-profile-helper.php';
require_once CAREERNEST_DIR . 'includes/class-applicant-ajax-handler.php';
require_once CAREERNEST_DIR . 'includes/class-auth-ajax-handler.php';
require_once CAREERNEST_DIR . 'includes/class-team-ajax-handler.php';

// Hooks.
register_activation_hook(__FILE__, ['\\CareerNest\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['\\CareerNest\\Deactivator', 'deactivate']);

// Bootstrap plugin runtime.
add_action('plugins_loaded', function () {
    // Lazy instantiate the plugin runtime.
    if (class_exists('\\CareerNest\\Plugin')) {
        (new \CareerNest\Plugin())->run();
    }

    // Initialize AJAX handler
    if (class_exists('\\CareerNest\\Ajax_Handler')) {
        new \CareerNest\Ajax_Handler();
    }

    // Initialize Job AJAX handler
    if (class_exists('\\CareerNest\\Job_Ajax_Handler')) {
        new \CareerNest\Job_Ajax_Handler();
    }

    // Initialize Applicant Notifications
    if (class_exists('\\CareerNest\\Applicant_Notifications')) {
        new \CareerNest\Applicant_Notifications();
    }

    // Initialize Applicant AJAX Handler
    if (class_exists('\\CareerNest\\Applicant_Ajax_Handler')) {
        new \CareerNest\Applicant_Ajax_Handler();
    }

    // Initialize Auth AJAX Handler
    if (class_exists('\\CareerNest\\Auth_Ajax_Handler')) {
        new \CareerNest\Auth_Ajax_Handler();
    }

    // Initialize Team AJAX Handler
    if (class_exists('\\CareerNest\\Team_Ajax_Handler')) {
        new \CareerNest\Team_Ajax_Handler();
    }

    // Hook admin and security subsystems.
    if (is_admin()) {
        (new \CareerNest\Admin\Admin())->hooks();
        (new \CareerNest\Admin\Employer_Requests())->hooks();
        (new \CareerNest\Admin\Employee_Requests())->hooks();
        (new \CareerNest\Admin\Import_Export())->hooks();
    }
    (new \CareerNest\Security\Caps())->hooks();
    \CareerNest\Data\Roles::ensure_caps();
});

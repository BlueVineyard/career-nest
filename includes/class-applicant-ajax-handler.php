<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Applicant AJAX Handler Class
 * 
 * Handles AJAX requests for applicant dashboard functionality
 */
class Applicant_Ajax_Handler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Withdraw application
        add_action('wp_ajax_cn_withdraw_application', [$this, 'withdraw_application']);
    }

    /**
     * Handle application withdrawal
     */
    public function withdraw_application(): void
    {
        // Verify nonce
        if (!check_ajax_referer('cn_withdraw_application', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to withdraw applications.']);
        }

        // Get application ID
        $application_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;

        if (!$application_id) {
            wp_send_json_error(['message' => 'Invalid application ID.']);
        }

        // Verify this is a job application
        if (get_post_type($application_id) !== 'job_application') {
            wp_send_json_error(['message' => 'Invalid application.']);
        }

        // Verify user owns this application
        $current_user = wp_get_current_user();
        $application_user_id = get_post_meta($application_id, '_user_id', true);

        if ($application_user_id != $current_user->ID) {
            wp_send_json_error(['message' => 'You do not have permission to withdraw this application.']);
        }

        // Check if application is already withdrawn or can be withdrawn
        $current_status = get_post_meta($application_id, '_app_status', true);

        // Don't allow withdrawal of already hired or rejected applications
        if (in_array($current_status, ['hired', 'rejected'])) {
            wp_send_json_error(['message' => 'This application cannot be withdrawn at this stage.']);
        }

        // Update application status to withdrawn
        $old_status = $current_status ?: 'new';
        update_post_meta($application_id, '_app_status', 'withdrawn');
        update_post_meta($application_id, '_withdrawn_date', current_time('mysql'));

        // Trigger withdrawal action for email notification
        do_action('careernest_application_withdrawn', $application_id);

        // Notify employer (optional - admin can see in application list)
        $job_id = get_post_meta($application_id, '_job_id', true);
        if ($job_id) {
            $employer_id = get_post_meta($job_id, '_employer_id', true);
            if ($employer_id) {
                // You could add employer notification here if needed
                // For now, they'll see it in their applications list
            }
        }

        wp_send_json_success([
            'message' => 'Your application has been withdrawn successfully.',
            'application_id' => $application_id
        ]);
    }
}

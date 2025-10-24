<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job AJAX Handler
 * 
 * Handles AJAX requests for job posting and editing from employer dashboard
 */
class Job_Ajax_Handler
{
    public function __construct()
    {
        // AJAX actions for logged-in users
        add_action('wp_ajax_cn_create_job', [$this, 'create_job']);
        add_action('wp_ajax_cn_update_job', [$this, 'update_job']);
        add_action('wp_ajax_cn_delete_job', [$this, 'delete_job']);
        add_action('wp_ajax_cn_get_job_data', [$this, 'get_job_data']);
        add_action('wp_ajax_cn_update_app_status', [$this, 'update_app_status']);
    }

    /**
     * Create a new job listing
     */
    public function create_job(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_job_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'You do not have permission to create jobs']);
            return;
        }

        // Get current user's employer ID
        $user_id = get_current_user_id();
        $employer_id = (int) get_user_meta($user_id, '_employer_id', true);

        if (!$employer_id) {
            wp_send_json_error(['message' => 'You must be linked to an employer to post jobs']);
            return;
        }

        // Validate and sanitize input
        $job_title = isset($_POST['job_title']) ? sanitize_text_field($_POST['job_title']) : '';
        $job_location = isset($_POST['job_location']) ? sanitize_text_field($_POST['job_location']) : '';
        $remote_position = !empty($_POST['remote_position']) ? 1 : 0;
        $opening_date = isset($_POST['opening_date']) ? sanitize_text_field($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? sanitize_text_field($_POST['closing_date']) : '';
        $salary_range = isset($_POST['salary_range']) ? sanitize_text_field($_POST['salary_range']) : '';
        $apply_externally = !empty($_POST['apply_externally']) ? 1 : 0;
        $external_apply = isset($_POST['external_apply']) ? sanitize_text_field($_POST['external_apply']) : '';

        // WYSIWYG content
        $overview = isset($_POST['overview']) ? wp_kses_post($_POST['overview']) : '';
        $who_we_are = isset($_POST['who_we_are']) ? wp_kses_post($_POST['who_we_are']) : '';
        $what_we_offer = isset($_POST['what_we_offer']) ? wp_kses_post($_POST['what_we_offer']) : '';
        $responsibilities = isset($_POST['responsibilities']) ? wp_kses_post($_POST['responsibilities']) : '';
        $how_to_apply = isset($_POST['how_to_apply']) ? wp_kses_post($_POST['how_to_apply']) : '';

        // Validation
        if (empty($job_title)) {
            wp_send_json_error(['message' => 'Job title is required']);
            return;
        }

        if (empty($job_location) && !$remote_position) {
            wp_send_json_error(['message' => 'Job location is required for non-remote positions']);
            return;
        }

        // Create job post
        $job_id = wp_insert_post([
            'post_type' => 'job_listing',
            'post_title' => $job_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if (is_wp_error($job_id)) {
            wp_send_json_error(['message' => 'Failed to create job: ' . $job_id->get_error_message()]);
            return;
        }

        // Save meta data
        update_post_meta($job_id, '_employer_id', $employer_id);
        update_post_meta($job_id, '_posted_by', $user_id);
        update_post_meta($job_id, '_job_location', $job_location);
        update_post_meta($job_id, '_remote_position', $remote_position);
        update_post_meta($job_id, '_opening_date', $opening_date);
        update_post_meta($job_id, '_closing_date', $closing_date);
        update_post_meta($job_id, '_salary_range', $salary_range);
        update_post_meta($job_id, '_apply_externally', $apply_externally);

        if ($apply_externally && $external_apply) {
            update_post_meta($job_id, '_external_apply', $external_apply);
        }

        // Save WYSIWYG content
        if ($overview) {
            update_post_meta($job_id, '_job_overview', $overview);
        }
        if ($who_we_are) {
            update_post_meta($job_id, '_job_who_we_are', $who_we_are);
        }
        if ($what_we_offer) {
            update_post_meta($job_id, '_job_what_we_offer', $what_we_offer);
        }
        if ($responsibilities) {
            update_post_meta($job_id, '_job_responsibilities', $responsibilities);
        }
        if ($how_to_apply) {
            update_post_meta($job_id, '_job_how_to_apply', $how_to_apply);
        }

        wp_send_json_success([
            'message' => 'Job posted successfully!',
            'job_id' => $job_id,
            'redirect_url' => get_permalink($job_id)
        ]);
    }

    /**
     * Update an existing job listing
     */
    public function update_job(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_job_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

        if (!$job_id || get_post_type($job_id) !== 'job_listing') {
            wp_send_json_error(['message' => 'Invalid job ID']);
            return;
        }

        // Check user can edit this job
        $user_id = get_current_user_id();
        $job_employer_id = (int) get_post_meta($job_id, '_employer_id', true);
        $user_employer_id = (int) get_user_meta($user_id, '_employer_id', true);

        if ($job_employer_id !== $user_employer_id) {
            wp_send_json_error(['message' => 'You do not have permission to edit this job']);
            return;
        }

        // Validate and sanitize input
        $job_title = isset($_POST['job_title']) ? sanitize_text_field($_POST['job_title']) : '';
        $job_location = isset($_POST['job_location']) ? sanitize_text_field($_POST['job_location']) : '';
        $remote_position = !empty($_POST['remote_position']) ? 1 : 0;
        $opening_date = isset($_POST['opening_date']) ? sanitize_text_field($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? sanitize_text_field($_POST['closing_date']) : '';
        $salary_range = isset($_POST['salary_range']) ? sanitize_text_field($_POST['salary_range']) : '';
        $apply_externally = !empty($_POST['apply_externally']) ? 1 : 0;
        $external_apply = isset($_POST['external_apply']) ? sanitize_text_field($_POST['external_apply']) : '';

        // WYSIWYG content
        $overview = isset($_POST['overview']) ? wp_kses_post($_POST['overview']) : '';
        $who_we_are = isset($_POST['who_we_are']) ? wp_kses_post($_POST['who_we_are']) : '';
        $what_we_offer = isset($_POST['what_we_offer']) ? wp_kses_post($_POST['what_we_offer']) : '';
        $responsibilities = isset($_POST['responsibilities']) ? wp_kses_post($_POST['responsibilities']) : '';
        $how_to_apply = isset($_POST['how_to_apply']) ? wp_kses_post($_POST['how_to_apply']) : '';

        // Validation
        if (empty($job_title)) {
            wp_send_json_error(['message' => 'Job title is required']);
            return;
        }

        // Update job post
        $result = wp_update_post([
            'ID' => $job_id,
            'post_title' => $job_title,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to update job: ' . $result->get_error_message()]);
            return;
        }

        // Update meta data
        update_post_meta($job_id, '_job_location', $job_location);
        update_post_meta($job_id, '_remote_position', $remote_position);
        update_post_meta($job_id, '_opening_date', $opening_date);
        update_post_meta($job_id, '_closing_date', $closing_date);
        update_post_meta($job_id, '_salary_range', $salary_range);
        update_post_meta($job_id, '_apply_externally', $apply_externally);

        if ($apply_externally && $external_apply) {
            update_post_meta($job_id, '_external_apply', $external_apply);
        } else {
            delete_post_meta($job_id, '_external_apply');
        }

        // Update WYSIWYG content
        if ($overview) {
            update_post_meta($job_id, '_job_overview', $overview);
        } else {
            delete_post_meta($job_id, '_job_overview');
        }
        if ($who_we_are) {
            update_post_meta($job_id, '_job_who_we_are', $who_we_are);
        } else {
            delete_post_meta($job_id, '_job_who_we_are');
        }
        if ($what_we_offer) {
            update_post_meta($job_id, '_job_what_we_offer', $what_we_offer);
        } else {
            delete_post_meta($job_id, '_job_what_we_offer');
        }
        if ($responsibilities) {
            update_post_meta($job_id, '_job_responsibilities', $responsibilities);
        } else {
            delete_post_meta($job_id, '_job_responsibilities');
        }
        if ($how_to_apply) {
            update_post_meta($job_id, '_job_how_to_apply', $how_to_apply);
        } else {
            delete_post_meta($job_id, '_job_how_to_apply');
        }

        wp_send_json_success([
            'message' => 'Job updated successfully!',
            'job_id' => $job_id,
            'redirect_url' => get_permalink($job_id)
        ]);
    }

    /**
     * Get job data for editing
     */
    public function get_job_data(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_job_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

        if (!$job_id || get_post_type($job_id) !== 'job_listing') {
            wp_send_json_error(['message' => 'Invalid job ID']);
            return;
        }

        // Check user can access this job
        $user_id = get_current_user_id();
        $job_employer_id = (int) get_post_meta($job_id, '_employer_id', true);
        $user_employer_id = (int) get_user_meta($user_id, '_employer_id', true);

        if ($job_employer_id !== $user_employer_id) {
            wp_send_json_error(['message' => 'You do not have permission to access this job']);
            return;
        }

        // Get job data
        $job = get_post($job_id);
        $job_data = [
            'job_title' => $job->post_title,
            'job_location' => get_post_meta($job_id, '_job_location', true),
            'remote_position' => (bool) get_post_meta($job_id, '_remote_position', true),
            'opening_date' => get_post_meta($job_id, '_opening_date', true),
            'closing_date' => get_post_meta($job_id, '_closing_date', true),
            'salary_range' => get_post_meta($job_id, '_salary_range', true),
            'apply_externally' => (bool) get_post_meta($job_id, '_apply_externally', true),
            'external_apply' => get_post_meta($job_id, '_external_apply', true),
            'overview' => get_post_meta($job_id, '_job_overview', true),
            'who_we_are' => get_post_meta($job_id, '_job_who_we_are', true),
            'what_we_offer' => get_post_meta($job_id, '_job_what_we_offer', true),
            'responsibilities' => get_post_meta($job_id, '_job_responsibilities', true),
            'how_to_apply' => get_post_meta($job_id, '_job_how_to_apply', true),
        ];

        wp_send_json_success($job_data);
    }

    /**
     * Delete a job listing
     */
    public function delete_job(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_job_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

        if (!$job_id || get_post_type($job_id) !== 'job_listing') {
            wp_send_json_error(['message' => 'Invalid job ID']);
            return;
        }

        // Check user can delete this job
        $user_id = get_current_user_id();
        $job_employer_id = (int) get_post_meta($job_id, '_employer_id', true);
        $user_employer_id = (int) get_user_meta($user_id, '_employer_id', true);

        if ($job_employer_id !== $user_employer_id) {
            wp_send_json_error(['message' => 'You do not have permission to delete this job']);
            return;
        }

        // Move to trash instead of permanent delete
        $result = wp_trash_post($job_id);

        if (!$result) {
            wp_send_json_error(['message' => 'Failed to delete job']);
            return;
        }

        wp_send_json_success(['message' => 'Job moved to trash successfully']);
    }

    /**
     * Update application status
     */
    public function update_app_status(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_app_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        $app_id = isset($_POST['app_id']) ? (int) $_POST['app_id'] : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if (!$app_id || get_post_type($app_id) !== 'job_application') {
            wp_send_json_error(['message' => 'Invalid application ID']);
            return;
        }

        // Validate status
        $allowed_statuses = ['new', 'reviewed', 'interviewed', 'offer_extended', 'hired', 'rejected'];
        if (!in_array($new_status, $allowed_statuses, true)) {
            wp_send_json_error(['message' => 'Invalid status']);
            return;
        }

        // Check user can update this application
        $user_id = get_current_user_id();
        $job_id = (int) get_post_meta($app_id, '_job_id', true);
        $job_employer_id = (int) get_post_meta($job_id, '_employer_id', true);
        $user_employer_id = (int) get_user_meta($user_id, '_employer_id', true);

        if ($job_employer_id !== $user_employer_id) {
            wp_send_json_error(['message' => 'You do not have permission to update this application']);
            return;
        }

        // Update status
        update_post_meta($app_id, '_app_status', $new_status);

        wp_send_json_success(['message' => 'Application status updated successfully']);
    }
}

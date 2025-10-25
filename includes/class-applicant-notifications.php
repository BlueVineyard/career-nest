<?php

namespace CareerNest;

use CareerNest\Email\Mailer;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Applicant Notifications Class
 * 
 * Handles email notifications for applicants when their application status changes
 */
class Applicant_Notifications
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Hook into application status changes
        add_action('careernest_application_status_changed', [$this, 'send_status_change_notification'], 10, 3);

        // Hook into application withdrawal
        add_action('careernest_application_withdrawn', [$this, 'send_withdrawal_notification'], 10, 1);
    }

    /**
     * Send notification when application status changes
     * 
     * @param int $application_id Application post ID
     * @param string $old_status Previous status
     * @param string $new_status New status
     */
    public function send_status_change_notification(int $application_id, string $old_status, string $new_status): void
    {
        // Don't send notification for new applications (that's a different flow)
        if ($old_status === '' || $old_status === 'new') {
            return;
        }

        // Don't send if status hasn't actually changed
        if ($old_status === $new_status) {
            return;
        }

        // Get application data
        $user_id = get_post_meta($application_id, '_user_id', true);
        $job_id = get_post_meta($application_id, '_job_id', true);

        if (!$user_id || !$job_id) {
            return;
        }

        // Get user and job info
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $job_title = get_the_title($job_id);
        $employer_id = get_post_meta($job_id, '_employer_id', true);
        $company_name = $employer_id ? get_the_title($employer_id) : 'Unknown Company';

        $application_date = get_post_meta($application_id, '_application_date', true);
        if (!$application_date) {
            $application_date = get_the_date('F j, Y', $application_id);
        } else {
            $application_date = date('F j, Y', strtotime($application_date));
        }

        // Get dashboard URL
        $pages = get_option('careernest_pages', []);
        $dashboard_url = isset($pages['applicant_dashboard']) ? get_permalink($pages['applicant_dashboard']) : home_url();

        // Status labels and messages
        $status_labels = [
            'new' => 'New Application',
            'reviewed' => 'Under Review',
            'interviewed' => 'Interview Scheduled',
            'offer_extended' => 'Offer Extended',
            'hired' => 'Hired',
            'rejected' => 'Not Selected',
            'archived' => 'Archived'
        ];

        $status_messages = [
            'reviewed' => 'Your application is currently being reviewed by the hiring team. We\'ll keep you updated on any progress.',
            'interviewed' => 'Congratulations! The employer would like to interview you. They may reach out to you soon to schedule an interview.',
            'offer_extended' => 'Excellent news! The employer has extended an offer. They will be in touch with details.',
            'hired' => 'Congratulations! You\'ve been selected for this position. The employer will provide next steps for onboarding.',
            'rejected' => 'Thank you for your interest. Unfortunately, the employer has decided to move forward with other candidates. Keep applying - the right opportunity is out there!',
            'archived' => 'This application has been archived. This typically happens when a position has been filled or closed.'
        ];

        $status_label = $status_labels[$new_status] ?? ucfirst(str_replace('_', ' ', $new_status));
        $status_message = $status_messages[$new_status] ?? 'Your application status has been updated.';

        // Send email
        Mailer::send($user->user_email, 'application_status_change', [
            'user_name' => $user->display_name,
            'job_title' => $job_title,
            'company_name' => $company_name,
            'status_label' => $status_label,
            'status_message' => $status_message,
            'dashboard_url' => $dashboard_url,
            'application_date' => $application_date,
        ]);
    }

    /**
     * Send notification when application is withdrawn
     * 
     * @param int $application_id Application post ID
     */
    public function send_withdrawal_notification(int $application_id): void
    {
        // Get application data
        $user_id = get_post_meta($application_id, '_user_id', true);
        $job_id = get_post_meta($application_id, '_job_id', true);

        if (!$user_id || !$job_id) {
            return;
        }

        // Get user and job info
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $job_title = get_the_title($job_id);
        $employer_id = get_post_meta($job_id, '_employer_id', true);
        $company_name = $employer_id ? get_the_title($employer_id) : 'Unknown Company';

        // Get page URLs
        $pages = get_option('careernest_pages', []);
        $dashboard_url = isset($pages['applicant_dashboard']) ? get_permalink($pages['applicant_dashboard']) : home_url();

        // Send email
        Mailer::send($user->user_email, 'application_withdrawn', [
            'user_name' => $user->display_name,
            'job_title' => $job_title,
            'company_name' => $company_name,
            'dashboard_url' => $dashboard_url,
        ]);
    }
}

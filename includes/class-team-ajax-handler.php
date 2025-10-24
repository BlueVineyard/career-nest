<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Team AJAX Handler Class
 * 
 * Handles AJAX operations for team management
 */
class Team_Ajax_Handler
{
    /**
     * Initialize hooks
     */
    public function hooks(): void
    {
        // AJAX actions
        add_action('wp_ajax_cn_add_team_member', [$this, 'handle_add_team_member']);
        add_action('wp_ajax_cn_remove_team_member', [$this, 'handle_remove_team_member']);
        add_action('wp_ajax_cn_transfer_ownership', [$this, 'handle_transfer_ownership']);
    }

    /**
     * Handle add team member AJAX request
     */
    public function handle_add_team_member(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cn_team_management')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        $employer_id = isset($_POST['employer_id']) ? (int) $_POST['employer_id'] : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
        $job_title = isset($_POST['job_title']) ? sanitize_text_field($_POST['job_title']) : '';

        // Verify user can manage team
        $current_user_id = get_current_user_id();
        if (!Team_Manager::can_manage_team($current_user_id, $employer_id)) {
            wp_send_json_error(['message' => 'You do not have permission to add team members.']);
        }

        // Validate inputs
        if (empty($email) || empty($full_name)) {
            wp_send_json_error(['message' => 'Email and full name are required.']);
        }

        // Add team member
        $result = Team_Manager::add_team_member($employer_id, $email, $full_name, $job_title);

        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Handle remove team member AJAX request
     */
    public function handle_remove_team_member(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cn_team_management')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        $employer_id = isset($_POST['employer_id']) ? (int) $_POST['employer_id'] : 0;
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $delete_user = isset($_POST['delete_user']) && $_POST['delete_user'] === 'true';

        // Verify user can manage team
        $current_user_id = get_current_user_id();
        if (!Team_Manager::can_manage_team($current_user_id, $employer_id)) {
            wp_send_json_error(['message' => 'You do not have permission to remove team members.']);
        }

        // Cannot remove yourself if you're the owner
        if ($current_user_id === $user_id && Team_Manager::is_owner($current_user_id, $employer_id)) {
            wp_send_json_error(['message' => 'You cannot remove yourself as the owner. Transfer ownership first.']);
        }

        // Remove team member
        $result = Team_Manager::remove_team_member($user_id, $employer_id, $delete_user);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Handle transfer ownership AJAX request (Admin only)
     */
    public function handle_transfer_ownership(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cn_transfer_ownership')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        $employer_id = isset($_POST['employer_id']) ? (int) $_POST['employer_id'] : 0;
        $new_owner_id = isset($_POST['new_owner_id']) ? (int) $_POST['new_owner_id'] : 0;

        // Verify user can assign owner (AES admin or super admin only)
        $current_user_id = get_current_user_id();
        if (!Team_Manager::can_assign_owner($current_user_id)) {
            wp_send_json_error(['message' => 'You do not have permission to transfer ownership.']);
        }

        // Transfer ownership
        $result = Team_Manager::transfer_ownership($employer_id, $new_owner_id);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}

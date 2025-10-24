<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Team Manager Class
 * 
 * Handles employer team management operations including ownership,
 * team member additions/removals, and access control.
 */
class Team_Manager
{
    /**
     * Check if user is the owner of the employer
     *
     * @param int $user_id User ID to check
     * @param int $employer_id Employer post ID
     * @return bool True if user is owner
     */
    public static function is_owner(int $user_id, int $employer_id): bool
    {
        $owner_id = (int) get_post_meta($employer_id, '_user_id', true);
        return (int) $owner_id === (int) $user_id;
    }

    /**
     * Check if user can manage team (owner, AES admin, or super admin)
     *
     * @param int $user_id User ID to check
     * @param int $employer_id Employer post ID
     * @return bool True if user can manage team
     */
    public static function can_manage_team(int $user_id, int $employer_id): bool
    {
        // Super admin always can
        if (is_super_admin($user_id)) {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // AES admin always can
        if (in_array('aes_admin', $user->roles)) {
            return true;
        }

        // Check if owner
        return self::is_owner($user_id, $employer_id);
    }

    /**
     * Check if user can assign owner (only AES admin or super admin)
     *
     * @param int $user_id User ID to check
     * @return bool True if user can assign owner
     */
    public static function can_assign_owner(int $user_id): bool
    {
        // Super admin always can
        if (is_super_admin($user_id)) {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // AES admin can
        return in_array('aes_admin', $user->roles);
    }

    /**
     * Get all team members for an employer
     *
     * @param int $employer_id Employer post ID
     * @return array Array of user objects
     */
    public static function get_team_members(int $employer_id): array
    {
        $args = [
            'meta_key' => '_employer_id',
            'meta_value' => $employer_id,
            'role' => 'employer_team',
            'orderby' => 'registered',
            'order' => 'ASC'
        ];

        $users = get_users($args);
        return $users;
    }

    /**
     * Get team member count for an employer
     *
     * @param int $employer_id Employer post ID
     * @return int Number of team members
     */
    public static function get_team_count(int $employer_id): int
    {
        return count(self::get_team_members($employer_id));
    }

    /**
     * Add team member to employer
     *
     * @param int $employer_id Employer post ID
     * @param string $email User email
     * @param string $full_name Full name
     * @param string $job_title Optional job title
     * @return array Success/error result with user_id and password
     */
    public static function add_team_member(int $employer_id, string $email, string $full_name, string $job_title = ''): array
    {
        // Validate email
        if (!is_email($email)) {
            return [
                'success' => false,
                'message' => 'Invalid email address.'
            ];
        }

        // Check if user already exists
        if (email_exists($email)) {
            return [
                'success' => false,
                'message' => 'A user with this email already exists.'
            ];
        }

        // Generate random password
        $password = wp_generate_password(12, true, true);

        // Create user account
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            return [
                'success' => false,
                'message' => 'Failed to create user: ' . $user_id->get_error_message()
            ];
        }

        // Update user profile
        $name_parts = explode(' ', $full_name, 2);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $full_name,
            'first_name' => $name_parts[0],
            'last_name' => isset($name_parts[1]) ? $name_parts[1] : '',
        ]);

        // Set employer role
        $user = new \WP_User($user_id);
        $user->set_role('employer_team');

        // Link user to employer
        update_user_meta($user_id, '_employer_id', $employer_id);

        // Save job title if provided
        if (!empty($job_title)) {
            update_user_meta($user_id, '_job_title', sanitize_text_field($job_title));
        }

        // Get employer info for email
        $employer_post = get_post($employer_id);
        $company_name = $employer_post ? $employer_post->post_title : 'Company';

        // Send welcome email
        $pages = get_option('careernest_pages', []);
        $dashboard_id = isset($pages['employer-dashboard']) ? (int) $pages['employer-dashboard'] : 0;
        $dashboard_url = ($dashboard_id && get_post_status($dashboard_id) === 'publish') ? get_permalink($dashboard_id) : home_url();

        \CareerNest\Email\Mailer::send($email, 'team_member_added', [
            'user_name' => $full_name,
            'company_name' => $company_name,
            'user_email' => $email,
            'password' => $password,
            'dashboard_url' => $dashboard_url,
        ]);

        return [
            'success' => true,
            'message' => 'Team member added successfully!',
            'user_id' => $user_id,
            'password' => $password
        ];
    }

    /**
     * Remove team member from employer
     *
     * @param int $user_id User ID to remove
     * @param int $employer_id Employer post ID
     * @param bool $delete_user Whether to delete the user account (default: false)
     * @return array Success/error result
     */
    public static function remove_team_member(int $user_id, int $employer_id, bool $delete_user = false): array
    {
        // Cannot remove owner
        if (self::is_owner($user_id, $employer_id)) {
            return [
                'success' => false,
                'message' => 'Cannot remove the company owner. Transfer ownership first.'
            ];
        }

        // Verify user is a team member
        $user_employer_id = (int) get_user_meta($user_id, '_employer_id', true);
        if ($user_employer_id !== $employer_id) {
            return [
                'success' => false,
                'message' => 'User is not a member of this team.'
            ];
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }

        // Remove employer link
        delete_user_meta($user_id, '_employer_id');

        // Get employer info for email
        $employer_post = get_post($employer_id);
        $company_name = $employer_post ? $employer_post->post_title : 'Company';

        // Send notification email
        \CareerNest\Email\Mailer::send($user->user_email, 'team_member_removed', [
            'user_name' => $user->display_name,
            'company_name' => $company_name,
        ]);

        // Optionally delete user account
        if ($delete_user) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($user_id);
        } else {
            // Just remove the role
            $user->remove_role('employer_team');
        }

        return [
            'success' => true,
            'message' => 'Team member removed successfully.'
        ];
    }

    /**
     * Transfer ownership to another team member
     *
     * @param int $employer_id Employer post ID
     * @param int $new_owner_id New owner user ID
     * @return array Success/error result
     */
    public static function transfer_ownership(int $employer_id, int $new_owner_id): array
    {
        // Get current owner
        $current_owner_id = (int) get_post_meta($employer_id, '_user_id', true);

        if ($current_owner_id === $new_owner_id) {
            return [
                'success' => false,
                'message' => 'User is already the owner.'
            ];
        }

        // Verify new owner is a team member
        $team_employer_id = (int) get_user_meta($new_owner_id, '_employer_id', true);
        if ($team_employer_id !== $employer_id) {
            return [
                'success' => false,
                'message' => 'New owner must be an existing team member.'
            ];
        }

        // Update owner
        update_post_meta($employer_id, '_user_id', $new_owner_id);

        // Get employer info
        $employer_post = get_post($employer_id);
        $company_name = $employer_post ? $employer_post->post_title : 'Company';

        // Get user info
        $old_owner = get_userdata($current_owner_id);
        $new_owner = get_userdata($new_owner_id);

        // Send notification to old owner
        if ($old_owner) {
            \CareerNest\Email\Mailer::send($old_owner->user_email, 'ownership_transferred_from', [
                'user_name' => $old_owner->display_name,
                'company_name' => $company_name,
                'new_owner_name' => $new_owner->display_name,
            ]);
        }

        // Send notification to new owner
        if ($new_owner) {
            \CareerNest\Email\Mailer::send($new_owner->user_email, 'ownership_transferred_to', [
                'user_name' => $new_owner->display_name,
                'company_name' => $company_name,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Ownership transferred successfully.'
        ];
    }

    /**
     * Check if email is already a team member
     *
     * @param string $email Email to check
     * @param int $employer_id Employer post ID
     * @return bool True if already a team member
     */
    public static function is_team_member_email(string $email, int $employer_id): bool
    {
        $user = get_user_by('email', $email);
        if (!$user) {
            return false;
        }

        $user_employer_id = (int) get_user_meta($user->ID, '_employer_id', true);
        return $user_employer_id === $employer_id;
    }
}

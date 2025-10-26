<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auth AJAX Handler
 * 
 * Handles AJAX requests for login and registration
 */
class Auth_Ajax_Handler
{
    public function __construct()
    {
        // AJAX actions for non-logged-in users (using different action names to avoid conflict with popup)
        add_action('wp_ajax_nopriv_careernest_page_login', [$this, 'handle_login']);
        add_action('wp_ajax_nopriv_careernest_page_register', [$this, 'handle_register']);
    }

    /**
     * Handle AJAX login
     */
    public function handle_login(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_login', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        // Sanitize and validate input
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] == '1';

        // Validation
        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Please enter your email and password']);
            return;
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address']);
            return;
        }

        // Attempt to authenticate
        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid email or password']);
            return;
        }

        // Set auth cookie
        wp_set_auth_cookie($user->ID, $remember);

        // Determine redirect URL based on user role
        $redirect_url = home_url();
        $pages = get_option('careernest_pages', []);

        if (in_array('applicant', $user->roles)) {
            $applicant_dashboard_id = isset($pages['applicant-dashboard']) ? $pages['applicant-dashboard'] : 0;
            if ($applicant_dashboard_id) {
                $redirect_url = get_permalink($applicant_dashboard_id);
            }
        } elseif (in_array('employer_team', $user->roles)) {
            $employer_dashboard_id = isset($pages['employer-dashboard']) ? $pages['employer-dashboard'] : 0;
            if ($employer_dashboard_id) {
                $redirect_url = get_permalink($employer_dashboard_id);
            }
        } elseif (in_array('administrator', $user->roles)) {
            $redirect_url = admin_url();
        }

        wp_send_json_success([
            'message' => 'Login successful! Redirecting...',
            'redirect' => $redirect_url
        ]);
    }

    /**
     * Handle AJAX registration
     */
    public function handle_register(): void
    {
        // Verify nonce
        if (!check_ajax_referer('careernest_register', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed']);
            return;
        }

        // Sanitize and validate input
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $user_type = isset($_POST['user_type']) ? sanitize_text_field($_POST['user_type']) : 'applicant';

        // Validation
        if (empty($name)) {
            wp_send_json_error(['message' => 'Please enter your name']);
            return;
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address']);
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            wp_send_json_error(['message' => 'Password must be at least 8 characters']);
            return;
        }

        if (!in_array($user_type, ['applicant', 'employer'])) {
            wp_send_json_error(['message' => 'Invalid user type']);
            return;
        }

        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(['message' => 'This email is already registered']);
            return;
        }

        // Generate username from email
        $username = sanitize_user(str_replace('@', '_', $email));

        // Make sure username is unique
        if (username_exists($username)) {
            $username = $username . '_' . wp_generate_password(4, false);
        }

        // Create user account
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => 'Registration failed. Please try again.']);
            return;
        }

        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ]);

        // Assign role based on user type
        $user = new \WP_User($user_id);
        $user->remove_role('subscriber'); // Remove default role

        if ($user_type === 'employer') {
            $user->add_role('employer_team');

            // Create employer profile
            $employer_id = $this->create_employer_profile($user_id, $name, $email);

            if ($employer_id) {
                update_user_meta($user_id, '_employer_id', $employer_id);
            }

            $redirect_url = home_url();
            $pages = get_option('careernest_pages', []);
            $employer_dashboard_id = isset($pages['employer-dashboard']) ? $pages['employer-dashboard'] : 0;
            if ($employer_dashboard_id) {
                $redirect_url = get_permalink($employer_dashboard_id);
            }
        } else {
            $user->add_role('applicant');

            // Create applicant profile
            $applicant_id = $this->create_applicant_profile($user_id, $name, $email);

            if ($applicant_id) {
                update_user_meta($user_id, '_applicant_id', $applicant_id);
            }

            $redirect_url = home_url();
            $pages = get_option('careernest_pages', []);
            $applicant_dashboard_id = isset($pages['applicant-dashboard']) ? $pages['applicant-dashboard'] : 0;
            if ($applicant_dashboard_id) {
                $redirect_url = get_permalink($applicant_dashboard_id);
            }
        }

        // Log the user in
        wp_set_auth_cookie($user_id, true);

        // Send welcome email
        $this->send_welcome_email($user_id, $name, $email, $user_type);

        wp_send_json_success([
            'message' => 'Registration successful! Redirecting...',
            'redirect' => $redirect_url
        ]);
    }

    /**
     * Create applicant profile
     */
    private function create_applicant_profile(int $user_id, string $name, string $email): int
    {
        $applicant_id = wp_insert_post([
            'post_type' => 'applicant',
            'post_title' => $name,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if (!is_wp_error($applicant_id)) {
            update_post_meta($applicant_id, '_user_id', $user_id);
            update_post_meta($applicant_id, '_applicant_email', $email);
            return $applicant_id;
        }

        return 0;
    }

    /**
     * Create employer profile
     */
    private function create_employer_profile(int $user_id, string $name, string $email): int
    {
        $employer_id = wp_insert_post([
            'post_type' => 'employer',
            'post_title' => $name,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if (!is_wp_error($employer_id)) {
            update_post_meta($employer_id, '_employer_email', $email);

            // Add user as team member
            $team_members = [
                [
                    'user_id' => $user_id,
                    'role' => 'Owner',
                    'permissions' => ['manage_jobs', 'manage_applications', 'manage_team']
                ]
            ];
            update_post_meta($employer_id, '_team_members', $team_members);

            return $employer_id;
        }

        return 0;
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email(int $user_id, string $name, string $email, string $user_type): void
    {
        $platform_name = cn_get_platform_name();
        $from_name = cn_get_email_from_name();

        $subject = "Welcome to {$platform_name}!";

        $message = "Hello {$name},\n\n";
        $message .= "Welcome to {$platform_name}! Your account has been successfully created.\n\n";

        if ($user_type === 'employer') {
            $message .= "You can now start posting jobs and managing applications.\n\n";
            $pages = get_option('careernest_pages', []);
            $employer_dashboard_id = isset($pages['employer-dashboard']) ? $pages['employer-dashboard'] : 0;
            if ($employer_dashboard_id) {
                $message .= "Visit your dashboard: " . get_permalink($employer_dashboard_id) . "\n\n";
            }
        } else {
            $message .= "You can now start applying for jobs and tracking your applications.\n\n";
            $pages = get_option('careernest_pages', []);
            $applicant_dashboard_id = isset($pages['applicant-dashboard']) ? $pages['applicant-dashboard'] : 0;
            if ($applicant_dashboard_id) {
                $message .= "Visit your dashboard: " . get_permalink($applicant_dashboard_id) . "\n\n";
            }
        }

        $message .= "If you have any questions, feel free to contact us.\n\n";
        $message .= "Best regards,\n{$from_name}";

        wp_mail($email, $subject, $message);
    }
}

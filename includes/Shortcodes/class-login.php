<?php

namespace CareerNest\Shortcodes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Login Shortcode Handler
 * 
 * Handles the [careernest_login] shortcode which displays:
 * - Login button and modal when user is logged out
 * - User info and logout button when user is logged in
 */
class Login
{
    /**
     * Register the shortcode
     */
    public static function register(): void
    {
        add_shortcode('careernest_login', [__CLASS__, 'render']);
    }

    /**
     * Render the login shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render($atts): string
    {
        // Enqueue assets
        self::enqueue_assets();

        // Parse attributes with defaults
        $atts = shortcode_atts([
            'button_text' => 'Login',
            'redirect' => '',
        ], $atts, 'careernest_login');

        ob_start();

        if (is_user_logged_in()) {
            self::render_logged_in_state();
        } else {
            self::render_login_button_and_modal($atts);
        }

        return ob_get_clean();
    }

    /**
     * Enqueue shortcode assets
     */
    private static function enqueue_assets(): void
    {
        // Enqueue login modal CSS
        wp_enqueue_style(
            'careernest-login-modal',
            CAREERNEST_URL . 'assets/css/login-modal.css',
            [],
            CAREERNEST_VERSION
        );

        // Enqueue login modal JavaScript
        wp_enqueue_script(
            'careernest-login-modal',
            CAREERNEST_URL . 'assets/js/login-modal.js',
            ['jquery'],
            CAREERNEST_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'careernest-login-modal',
            'careerNestLogin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('careernest_login'),
            ]
        );
    }

    /**
     * Render logged-in user state
     */
    private static function render_logged_in_state(): void
    {
        $current_user = wp_get_current_user();
        $display_name = $current_user->display_name;
        $dashboard_url = self::get_user_dashboard_url();

?>
        <div class="cn-login-widget cn-logged-in">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-user-link">
                <span class="cn-user-icon">👤</span>
                <span class="cn-user-name"><?php echo esc_html($display_name); ?></span>
            </a>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="cn-logout-btn" title="Logout">
                <span class="cn-logout-icon">⎋</span>
            </a>
        </div>
    <?php
    }

    /**
     * Render login button and modal
     * 
     * @param array $atts Shortcode attributes
     */
    private static function render_login_button_and_modal($atts): void
    {
        $button_text = esc_html($atts['button_text']);
        $redirect_url = !empty($atts['redirect']) ? esc_url($atts['redirect']) : '';

        // Get page URLs
        $pages = get_option('careernest_pages', []);
        $register_applicant_url = isset($pages['register-applicant']) ? get_permalink($pages['register-applicant']) : '';
        $register_employer_url = isset($pages['register-employer']) ? get_permalink($pages['register-employer']) : '';

    ?>
        <div class="cn-login-widget">
            <button type="button" class="cn-login-btn" id="cn-login-trigger">
                <?php echo $button_text; ?>
            </button>
        </div>

        <!-- Login Modal -->
        <div class="cn-modal-overlay" id="cn-login-modal" style="display: none;">
            <div class="cn-modal-content">
                <button type="button" class="cn-modal-close" id="cn-modal-close" aria-label="Close">&times;</button>

                <div class="cn-modal-header">
                    <h2>Login to Your Account</h2>
                </div>

                <div class="cn-modal-body">
                    <div class="cn-login-messages"></div>

                    <form id="cn-login-form" method="post">
                        <?php wp_nonce_field('careernest_login', 'careernest_login_nonce'); ?>

                        <div class="cn-form-field">
                            <label for="cn-username">Username or Email</label>
                            <input type="text" id="cn-username" name="username" class="cn-input" required
                                autocomplete="username" />
                        </div>

                        <div class="cn-form-field">
                            <label for="cn-password">Password</label>
                            <input type="password" id="cn-password" name="password" class="cn-input" required
                                autocomplete="current-password" />
                        </div>

                        <div class="cn-form-field cn-remember-me">
                            <label class="cn-checkbox-label">
                                <input type="checkbox" name="remember" value="1" />
                                <span>Remember Me</span>
                            </label>
                        </div>

                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>" />

                        <button type="submit" class="cn-btn cn-btn-primary cn-btn-full">
                            Login
                        </button>
                    </form>

                    <div class="cn-modal-footer">
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="cn-forgot-password">
                            Forgotten Password?
                        </a>

                        <?php if ($register_applicant_url || $register_employer_url): ?>
                            <div class="cn-register-links">
                                <p>Don't have an account?</p>
                                <div class="cn-register-options">
                                    <?php if ($register_applicant_url): ?>
                                        <a href="<?php echo esc_url($register_applicant_url); ?>" class="cn-register-link">
                                            Sign Up as Job Seeker
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($register_employer_url): ?>
                                        <a href="<?php echo esc_url($register_employer_url); ?>" class="cn-register-link">
                                            Sign Up as Employer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Get dashboard URL for current user based on their role
     * 
     * @return string Dashboard URL
     */
    private static function get_user_dashboard_url(): string
    {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        $pages = get_option('careernest_pages', []);

        // Check role priority: admin > employer > applicant
        if (in_array('aes_admin', $roles, true) || in_array('administrator', $roles, true)) {
            return admin_url();
        }

        if (in_array('employer_team', $roles, true) && isset($pages['employer-dashboard'])) {
            return get_permalink($pages['employer-dashboard']);
        }

        if (in_array('applicant', $roles, true) && isset($pages['applicant-dashboard'])) {
            return get_permalink($pages['applicant-dashboard']);
        }

        // Default fallback
        return admin_url();
    }

    /**
     * Handle AJAX login request
     */
    public static function ajax_login(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'careernest_login')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        // Sanitize inputs
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

        // Validate inputs
        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => 'Please enter both username and password.']);
        }

        // Attempt login
        $credentials = [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ];

        $user = wp_signon($credentials, is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => $user->get_error_message()]);
        }

        // Get redirect URL
        $redirect_url = isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])
            ? esc_url_raw($_POST['redirect_to'])
            : '';

        // If no specific redirect, use role-based dashboard
        if (empty($redirect_url)) {
            $redirect_url = self::get_user_dashboard_url();
        }

        wp_send_json_success([
            'message' => 'Login successful!',
            'redirect' => $redirect_url,
        ]);
    }

    /**
     * Handle login redirect based on user role
     * 
     * @param string $redirect_to URL to redirect to
     * @param string $request Requested redirect URL
     * @param \WP_User|\WP_Error $user User object
     * @return string Redirect URL
     */
    public static function login_redirect($redirect_to, $request, $user)
    {
        // Only handle successful logins
        if (!isset($user->ID)) {
            return $redirect_to;
        }

        // If a specific redirect was requested, honor it
        if (!empty($request) && $request !== admin_url()) {
            return $request;
        }

        // Get user roles
        $roles = (array) $user->roles;
        $pages = get_option('careernest_pages', []);

        // Redirect based on role
        if (in_array('aes_admin', $roles, true) || in_array('administrator', $roles, true)) {
            return admin_url();
        }

        if (in_array('employer_team', $roles, true) && isset($pages['employer-dashboard'])) {
            return get_permalink($pages['employer-dashboard']);
        }

        if (in_array('applicant', $roles, true) && isset($pages['applicant-dashboard'])) {
            return get_permalink($pages['applicant-dashboard']);
        }

        // Default redirect
        return $redirect_to;
    }
}

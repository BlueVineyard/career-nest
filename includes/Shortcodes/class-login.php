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
                <span class="cn-user-name"><?php echo esc_html($display_name); ?></span>
            </a>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="cn-logout-btn" title="Logout">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 3H4C3.44772 3 3 3.44772 3 4V16C3 16.5523 3.44772 17 4 17H9" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" />
                    <path d="M16 10H9M16 10L13 7M16 10L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
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
                    <h2>Welcome Back</h2>
                    <p style="color: #718096; font-size: 0.95rem; margin: 0.5rem 0 0 0;">Sign in to your account</p>
                </div>

                <div class="cn-modal-body">
                    <div class="cn-login-messages"></div>

                    <!-- Social Login Buttons -->
                    <div class="cn-social-login">
                        <button type="button" class="cn-social-btn" disabled title="Coming soon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M19.9895 10.1871C19.9895 9.36767 19.9214 8.76973 19.7742 8.14966H10.1992V11.848H15.8195C15.7062 12.7671 15.0943 14.1512 13.7346 15.0813L13.7155 15.2051L16.7429 17.4969L16.9527 17.5174C18.879 15.7789 19.9895 13.221 19.9895 10.1871Z"
                                    fill="#4285F4" />
                                <path
                                    d="M10.1993 19.9313C12.9527 19.9313 15.2643 19.0454 16.9527 17.5174L13.7346 15.0813C12.8734 15.6682 11.7176 16.0779 10.1993 16.0779C7.50243 16.0779 5.21352 14.3395 4.39759 11.9366L4.27799 11.9466L1.13003 14.3273L1.08887 14.4391C2.76588 17.6945 6.21061 19.9313 10.1993 19.9313Z"
                                    fill="#34A853" />
                                <path
                                    d="M4.39748 11.9366C4.18219 11.3166 4.05759 10.6521 4.05759 9.96565C4.05759 9.27909 4.18219 8.61473 4.38615 7.99466L4.38045 7.8626L1.19304 5.44366L1.08876 5.49214C0.397576 6.84305 0 8.36008 0 9.96565C0 11.5712 0.397576 13.0882 1.08876 14.4391L4.39748 11.9366Z"
                                    fill="#FBBC05" />
                                <path
                                    d="M10.1993 3.85336C12.1142 3.85336 13.406 4.66168 14.1425 5.33717L17.0207 2.59107C15.253 0.985496 12.9527 0 10.1993 0C6.2106 0 2.76588 2.23672 1.08887 5.49214L4.38626 7.99466C5.21352 5.59183 7.50242 3.85336 10.1993 3.85336Z"
                                    fill="#EB4335" />
                            </svg>
                            <span>Google</span>
                        </button>
                        <button type="button" class="cn-social-btn" disabled title="Coming soon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18.5195 0H1.47656C0.660156 0 0 0.644531 0 1.44141V18.5547C0 19.3516 0.660156 20 1.47656 20H18.5195C19.3359 20 20 19.3516 20 18.5586V1.44141C20 0.644531 19.3359 0 18.5195 0ZM5.93359 17.043H2.96484V7.49609H5.93359V17.043ZM4.44922 6.19531C3.49609 6.19531 2.72656 5.42578 2.72656 4.47656C2.72656 3.52734 3.49609 2.75781 4.44922 2.75781C5.39844 2.75781 6.16797 3.52734 6.16797 4.47656C6.16797 5.42188 5.39844 6.19531 4.44922 6.19531ZM17.043 17.043H14.0781V12.4023C14.0781 11.2969 14.0586 9.87109 12.5352 9.87109C10.9922 9.87109 10.7578 11.0781 10.7578 12.3242V17.043H7.79297V7.49609H10.6406V8.80078H10.6797C11.0742 8.05078 12.043 7.25781 13.4844 7.25781C16.4883 7.25781 17.043 9.23438 17.043 11.8047V17.043Z"
                                    fill="#0077B5" />
                            </svg>
                            <span>LinkedIn</span>
                        </button>
                    </div>

                    <div class="cn-form-divider">
                        <span>or continue with email</span>
                    </div>

                    <form id="cn-login-form" method="post">
                        <?php wp_nonce_field('careernest_login', 'careernest_login_nonce'); ?>

                        <div class="cn-form-field">
                            <label for="cn-username">Email</label>
                            <div class="cn-input-wrapper">
                                <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 4L10 11L17 4M3 16H17V4H3V16Z" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <input type="text" id="cn-username" name="username" class="cn-input" required
                                    autocomplete="username" placeholder="your.email@example.com" />
                            </div>
                        </div>

                        <div class="cn-form-field">
                            <label for="cn-password">Password</label>
                            <div class="cn-input-wrapper cn-password-wrapper">
                                <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="9" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2" />
                                    <path d="M6 9V6C6 3.79086 7.79086 2 10 2V2C12.2091 2 14 3.79086 14 6V9"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                <input type="password" id="cn-password" name="password" class="cn-input" required
                                    autocomplete="current-password" placeholder="••••••••" />
                                <button type="button" class="cn-password-toggle" aria-label="Toggle password visibility">
                                    <svg class="cn-eye-open" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 4C4 4 1 10 1 10C1 10 4 16 10 16C16 16 19 10 19 10C19 10 16 4 10 4Z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <circle cx="10" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <svg class="cn-eye-closed" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                        <path
                                            d="M3 3L17 17M10.5 13.5C9.5 14 8.5 14 7.5 13.5M1 10C1 10 3 7 6 5.5M19 10C19 10 17 13 14 14.5"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="cn-form-options">
                            <label class="cn-checkbox-label">
                                <input type="checkbox" name="remember" value="1" />
                                <span class="cn-checkbox-custom"></span>
                                <span class="cn-checkbox-text">Remember me</span>
                            </label>
                            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="cn-forgot-link">
                                Forgot password?
                            </a>
                        </div>

                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>" />

                        <button type="submit" class="cn-btn cn-btn-primary cn-btn-submit">
                            <span class="cn-btn-text">Sign In</span>
                        </button>

                        <!-- Registration Links -->
                        <?php if ($register_applicant_url || $register_employer_url): ?>
                            <div class="cn-register-links">
                                <p class="cn-register-text">Don't have an account?</p>
                                <div class="cn-register-buttons">
                                    <?php if ($register_applicant_url): ?>
                                        <a href="<?php echo esc_url($register_applicant_url); ?>" class="cn-btn cn-btn-outline">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="10" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                                                <path d="M3 18C3 14.134 6.13401 11 10 11V11C13.866 11 17 14.134 17 18V18"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                            Register as Job Seeker
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($register_employer_url): ?>
                                        <a href="<?php echo esc_url($register_employer_url); ?>" class="cn-btn cn-btn-outline">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <rect x="2" y="4" width="16" height="14" rx="2" stroke="currentColor"
                                                    stroke-width="2" />
                                                <path d="M7 8V8.01M13 8V8.01M7 12H13" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" />
                                            </svg>
                                            Register as Employer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
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

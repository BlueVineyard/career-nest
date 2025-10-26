<?php

/**
 * Template: CareerNest — Login Page
 * 
 * Minimal & Clean Design
 */

defined('ABSPATH') || exit;

// Redirect if already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $redirect_url = home_url();

    // Redirect based on user role
    if (in_array('applicant', $user->roles)) {
        $pages = get_option('careernest_pages', []);
        $applicant_dashboard_id = isset($pages['applicant-dashboard']) ? $pages['applicant-dashboard'] : 0;
        if ($applicant_dashboard_id) {
            $redirect_url = get_permalink($applicant_dashboard_id);
        }
    } elseif (in_array('employer_team', $user->roles)) {
        $pages = get_option('careernest_pages', []);
        $employer_dashboard_id = isset($pages['employer-dashboard']) ? $pages['employer-dashboard'] : 0;
        if ($employer_dashboard_id) {
            $redirect_url = get_permalink($employer_dashboard_id);
        }
    }

    wp_redirect($redirect_url);
    exit;
}

// Get page links
$pages = get_option('careernest_pages', []);
$applicant_reg_id = isset($pages['register-applicant']) ? $pages['register-applicant'] : 0;
$employer_reg_id = isset($pages['register-employer']) ? $pages['register-employer'] : 0;
$forgot_password_id = isset($pages['forgot-password']) ? $pages['forgot-password'] : 0;
$applicant_reg_url = $applicant_reg_id ? get_permalink($applicant_reg_id) : '#';
$employer_reg_url = $employer_reg_id ? get_permalink($employer_reg_id) : '#';
$forgot_password_url = $forgot_password_id ? get_permalink($forgot_password_id) : wp_lostpassword_url();

get_header();
?>

<main id="primary" class="site-main cn-auth-page">
    <div class="cn-auth-container">
        <div class="cn-auth-card">
            <!-- Login Form -->
            <div class="cn-auth-form cn-login-form active">
                <h2 class="cn-form-title"><?php esc_html_e('Welcome Back', 'careernest'); ?></h2>
                <p class="cn-form-subtitle"><?php esc_html_e('Sign in to your account', 'careernest'); ?></p>

                <form method="post" class="cn-form" id="cn-login-form" novalidate>
                    <!-- Social Login Buttons -->
                    <div class="cn-social-login">
                        <button type="button" class="cn-social-btn cn-social-google" disabled
                            title="<?php esc_attr_e('Coming soon', 'careernest'); ?>">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
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
                            <span><?php esc_html_e('Google', 'careernest'); ?></span>
                        </button>
                        <button type="button" class="cn-social-btn cn-social-linkedin" disabled
                            title="<?php esc_attr_e('Coming soon', 'careernest'); ?>">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18.5195 0H1.47656C0.660156 0 0 0.644531 0 1.44141V18.5547C0 19.3516 0.660156 20 1.47656 20H18.5195C19.3359 20 20 19.3516 20 18.5586V1.44141C20 0.644531 19.3359 0 18.5195 0ZM5.93359 17.043H2.96484V7.49609H5.93359V17.043ZM4.44922 6.19531C3.49609 6.19531 2.72656 5.42578 2.72656 4.47656C2.72656 3.52734 3.49609 2.75781 4.44922 2.75781C5.39844 2.75781 6.16797 3.52734 6.16797 4.47656C6.16797 5.42188 5.39844 6.19531 4.44922 6.19531ZM17.043 17.043H14.0781V12.4023C14.0781 11.2969 14.0586 9.87109 12.5352 9.87109C10.9922 9.87109 10.7578 11.0781 10.7578 12.3242V17.043H7.79297V7.49609H10.6406V8.80078H10.6797C11.0742 8.05078 12.043 7.25781 13.4844 7.25781C16.4883 7.25781 17.043 9.23438 17.043 11.8047V17.043Z"
                                    fill="#0077B5" />
                            </svg>
                            <span><?php esc_html_e('LinkedIn', 'careernest'); ?></span>
                        </button>
                    </div>

                    <div class="cn-form-divider">
                        <span><?php esc_html_e('or continue with email', 'careernest'); ?></span>
                    </div>

                    <!-- Error Messages -->
                    <div class="cn-form-messages"></div>

                    <!-- Email Field -->
                    <div class="cn-form-group">
                        <label for="login_email" class="cn-field-label">
                            <?php esc_html_e('Email', 'careernest'); ?>
                        </label>
                        <div class="cn-input-wrapper">
                            <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 4L10 11L17 4M3 16H17V4H3V16Z" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <input type="email" id="login_email" name="email" class="cn-input"
                                placeholder="<?php esc_attr_e('your.email@example.com', 'careernest'); ?>" required
                                autocomplete="email">
                        </div>
                        <span class="cn-field-error"></span>
                    </div>

                    <!-- Password Field -->
                    <div class="cn-form-group">
                        <label for="login_password" class="cn-field-label">
                            <?php esc_html_e('Password', 'careernest'); ?>
                        </label>
                        <div class="cn-input-wrapper cn-password-wrapper">
                            <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="9" width="14" height="10" rx="2" stroke="currentColor"
                                    stroke-width="2" />
                                <path d="M6 9V6C6 3.79086 7.79086 2 10 2V2C12.2091 2 14 3.79086 14 6V9"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                            <input type="password" id="login_password" name="password" class="cn-input"
                                placeholder="<?php esc_attr_e('••••••••', 'careernest'); ?>" required
                                autocomplete="current-password">
                            <button type="button" class="cn-password-toggle"
                                aria-label="<?php esc_attr_e('Toggle password visibility', 'careernest'); ?>">
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
                        <span class="cn-field-error"></span>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="cn-form-options">
                        <label class="cn-checkbox-label">
                            <input type="checkbox" name="remember" id="remember_me">
                            <span class="cn-checkbox-custom"></span>
                            <span class="cn-checkbox-text"><?php esc_html_e('Remember me', 'careernest'); ?></span>
                        </label>
                        <a href="<?php echo esc_url($forgot_password_url); ?>" class="cn-forgot-link">
                            <?php esc_html_e('Forgot password?', 'careernest'); ?>
                        </a>
                    </div>

                    <?php wp_nonce_field('careernest_login', 'login_nonce'); ?>

                    <!-- Submit Button -->
                    <button type="submit" class="cn-btn cn-btn-primary cn-btn-submit">
                        <span class="cn-btn-text"><?php esc_html_e('Sign In', 'careernest'); ?></span>
                        <span class="cn-btn-loader" style="display: none;">
                            <svg class="cn-spinner" width="20" height="20" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" fill="none"
                                    opacity="0.25" />
                                <path d="M10 2C5.58172 2 2 5.58172 2 10" stroke="currentColor" stroke-width="2"
                                    fill="none" stroke-linecap="round" />
                            </svg>
                        </span>
                    </button>

                    <!-- Registration Links -->
                    <div class="cn-register-links">
                        <p class="cn-register-text"><?php esc_html_e("Don't have an account?", 'careernest'); ?></p>
                        <div class="cn-register-buttons">
                            <a href="<?php echo esc_url($applicant_reg_url); ?>" class="cn-btn cn-btn-outline">
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="10" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                                    <path d="M3 18C3 14.134 6.13401 11 10 11V11C13.866 11 17 14.134 17 18V18"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                <?php esc_html_e('Register as Job Seeker', 'careernest'); ?>
                            </a>
                            <a href="<?php echo esc_url($employer_reg_url); ?>" class="cn-btn cn-btn-outline">
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect x="2" y="4" width="16" height="14" rx="2" stroke="currentColor"
                                        stroke-width="2" />
                                    <path d="M7 8V8.01M13 8V8.01M7 12H13" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" />
                                </svg>
                                <?php esc_html_e('Register as Employer', 'careernest'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
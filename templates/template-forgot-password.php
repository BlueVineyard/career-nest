<?php

/**
 * Template: CareerNest — Forgot Password
 * 
 * Minimal & Clean Design
 */

defined('ABSPATH') || exit;

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$reset_sent = false;
$error_message = '';
$password_reset = false;

// Check if this is a password reset (vs forgot password)
$is_reset_page = isset($_GET['action']) && $_GET['action'] === 'resetpass';
$rp_key = isset($_GET['key']) ? $_GET['key'] : '';
$rp_login = isset($_GET['login']) ? $_GET['login'] : '';

// Handle actual password reset
if ($is_reset_page && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_nonce'])) {
    if (wp_verify_nonce($_POST['reset_password_nonce'], 'careernest_reset_password')) {
        $rp_key = sanitize_text_field($_POST['rp_key']);
        $rp_login = sanitize_text_field($_POST['rp_login']);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $error_message = 'Please enter a new password.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'Password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // Verify reset key
            $user = check_password_reset_key($rp_key, $rp_login);

            if (!is_wp_error($user)) {
                // Reset the password
                reset_password($user, $new_password);
                $password_reset = true;
            } else {
                $error_message = 'Invalid or expired reset link. Please request a new one.';
            }
        }
    } else {
        $error_message = 'Security verification failed.';
    }
}

// Verify reset key on page load for reset page
if ($is_reset_page && !$password_reset && !empty($rp_key) && !empty($rp_login)) {
    $user = check_password_reset_key($rp_key, $rp_login);
    if (is_wp_error($user)) {
        $error_message = 'Invalid or expired reset link. Please request a new password reset.';
    }
}

// Handle forgot password request
if (!$is_reset_page && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_nonce'])) {
    if (wp_verify_nonce($_POST['reset_nonce'], 'careernest_forgot_password')) {
        $email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';

        if (empty($email)) {
            $error_message = 'Please enter your email address.';
        } elseif (!is_email($email)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if user exists
            $user = get_user_by('email', $email);

            if ($user) {
                // Send password reset email
                $result = retrieve_password($email);

                if (!is_wp_error($result)) {
                    $reset_sent = true;
                } else {
                    $error_message = 'Unable to send reset email. Please try again.';
                }
            } else {
                // Still show success even if user doesn't exist (security best practice)
                $reset_sent = true;
            }
        }
    } else {
        $error_message = 'Security verification failed. Please try again.';
    }
}

// Get login page URL
$pages = get_option('careernest_pages', []);
$login_page_id = isset($pages['login']) ? $pages['login'] : 0;
$login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url();

get_header();
?>

<main id="primary" class="site-main cn-auth-page">
    <div class="cn-auth-container">
        <div class="cn-auth-card">
            <?php if ($password_reset): ?>
                <!-- Password Reset Success -->
                <div class="cn-reset-success">
                    <div class="cn-success-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="#10B981" stroke-width="2" />
                            <path d="m9 12 2 2 4-4" stroke="#10B981" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h2 class="cn-form-title"><?php esc_html_e('Password Reset!', 'careernest'); ?></h2>
                    <p class="cn-success-message">
                        <?php esc_html_e('Your password has been successfully reset. You can now log in with your new password.', 'careernest'); ?>
                    </p>
                    <a href="<?php echo esc_url($login_url); ?>" class="cn-btn cn-btn-primary">
                        <?php esc_html_e('Go to Login', 'careernest'); ?>
                    </a>
                </div>
            <?php elseif ($reset_sent): ?>
                <!-- Success State -->
                <div class="cn-reset-success">
                    <div class="cn-success-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="#10B981" stroke-width="2" />
                            <path d="m9 12 2 2 4-4" stroke="#10B981" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h2 class="cn-form-title"><?php esc_html_e('Check Your Email', 'careernest'); ?></h2>
                    <p class="cn-success-message">
                        <?php esc_html_e('If an account exists for the email you entered, you will receive a password reset link shortly.', 'careernest'); ?>
                    </p>
                    <p class="cn-success-hint">
                        <?php esc_html_e("Didn't receive an email? Check your spam folder or try again.", 'careernest'); ?>
                    </p>
                    <a href="<?php echo esc_url($login_url); ?>" class="cn-btn cn-btn-primary">
                        <?php esc_html_e('Back to Login', 'careernest'); ?>
                    </a>
                </div>
            <?php elseif ($is_reset_page): ?>
                <!-- Reset Password Form -->
                <div class="cn-auth-form active">
                    <h2 class="cn-form-title"><?php esc_html_e('Reset Password', 'careernest'); ?></h2>
                    <p class="cn-form-subtitle">
                        <?php esc_html_e('Enter your new password below.', 'careernest'); ?>
                    </p>

                    <?php if (!empty($error_message)): ?>
                        <div class="cn-form-messages">
                            <div class="cn-form-message cn-form-message-error">
                                <span class="cn-form-message-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z"
                                            stroke="currentColor" stroke-width="2" />
                                        <path d="M10 6V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <path d="M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span><?php echo esc_html($error_message); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($error_message) || strpos($error_message, 'match') !== false || strpos($error_message, '8 characters') !== false): ?>
                        <form method="post" class="cn-form">
                            <?php wp_nonce_field('careernest_reset_password', 'reset_password_nonce'); ?>
                            <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>">
                            <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>">

                            <div class="cn-form-group">
                                <label for="new_password" class="cn-field-label">
                                    <?php esc_html_e('New Password', 'careernest'); ?>
                                </label>
                                <div class="cn-input-wrapper cn-password-wrapper">
                                    <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="9" width="14" height="10" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path d="M6 9V6C6 3.79086 7.79086 2 10 2V2C12.2091 2 14 3.79086 14 6V9"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <input type="password" id="new_password" name="new_password" class="cn-input"
                                        placeholder="<?php esc_attr_e('Minimum 8 characters', 'careernest'); ?>" required
                                        autocomplete="new-password">
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
                            </div>

                            <div class="cn-form-group">
                                <label for="confirm_password" class="cn-field-label">
                                    <?php esc_html_e('Confirm Password', 'careernest'); ?>
                                </label>
                                <div class="cn-input-wrapper">
                                    <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="9" width="14" height="10" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path d="M6 9V6C6 3.79086 7.79086 2 10 2V2C12.2091 2 14 3.79086 14 6V9"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <input type="password" id="confirm_password" name="confirm_password" class="cn-input"
                                        placeholder="<?php esc_attr_e('Confirm new password', 'careernest'); ?>" required
                                        autocomplete="new-password">
                                </div>
                            </div>

                            <button type="submit" class="cn-btn cn-btn-primary cn-btn-submit">
                                <?php esc_html_e('Reset Password', 'careernest'); ?>
                            </button>

                            <div class="cn-form-footer">
                                <a href="<?php echo esc_url($login_url); ?>" class="cn-back-link">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 4L6 10L12 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <?php esc_html_e('Back to Login', 'careernest'); ?>
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="cn-form-footer">
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="cn-btn cn-btn-primary">
                                <?php esc_html_e('Request New Reset Link', 'careernest'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Forgot Password Form -->
                <div class="cn-auth-form active">
                    <h2 class="cn-form-title"><?php esc_html_e('Forgot Password?', 'careernest'); ?></h2>
                    <p class="cn-form-subtitle">
                        <?php esc_html_e('Enter your email address and we\'ll send you a link to reset your password.', 'careernest'); ?>
                    </p>

                    <?php if (!empty($error_message)): ?>
                        <div class="cn-form-messages">
                            <div class="cn-form-message cn-form-message-error">
                                <span class="cn-form-message-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z"
                                            stroke="currentColor" stroke-width="2" />
                                        <path d="M10 6V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <path d="M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span><?php echo esc_html($error_message); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="cn-form">
                        <?php wp_nonce_field('careernest_forgot_password', 'reset_nonce'); ?>

                        <div class="cn-form-group">
                            <label for="user_email" class="cn-field-label">
                                <?php esc_html_e('Email Address', 'careernest'); ?>
                            </label>
                            <div class="cn-input-wrapper">
                                <svg class="cn-input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 4L10 11L17 4M3 16H17V4H3V16Z" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <input type="email" id="user_email" name="user_email" class="cn-input"
                                    placeholder="<?php esc_attr_e('your.email@example.com', 'careernest'); ?>"
                                    value="<?php echo esc_attr($_POST['user_email'] ?? ''); ?>" required
                                    autocomplete="email">
                            </div>
                        </div>

                        <button type="submit" class="cn-btn cn-btn-primary cn-btn-submit">
                            <?php esc_html_e('Send Reset Link', 'careernest'); ?>
                        </button>

                        <div class="cn-form-footer">
                            <a href="<?php echo esc_url($login_url); ?>" class="cn-back-link">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4L6 10L12 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e('Back to Login', 'careernest'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    .cn-reset-success {
        text-align: center;
        padding: 2rem 0;
    }

    .cn-success-icon {
        margin-bottom: 1.5rem;
    }

    .cn-success-message {
        color: #4a5568;
        font-size: 1rem;
        line-height: 1.6;
        margin: 0 0 1rem 0;
    }

    .cn-success-hint {
        color: #718096;
        font-size: 0.875rem;
        margin: 0 0 2rem 0;
    }

    .cn-form-footer {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }

    .cn-back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #718096;
        text-decoration: none;
        font-size: 0.95rem;
        transition: color 0.2s ease;
    }

    .cn-back-link:hover {
        color: #ff8200;
    }
</style>

<script>
    // Password toggle functionality
    document.querySelectorAll('.cn-password-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const wrapper = this.closest('.cn-password-wrapper');
            const input = wrapper.querySelector('input');
            const eyeOpen = this.querySelector('.cn-eye-open');
            const eyeClosed = this.querySelector('.cn-eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        });
    });
</script>

<?php get_footer(); ?>
<?php

/**
 * Template: CareerNest — Reset Password
 * 
 * Custom password reset page
 */

defined('ABSPATH') || exit;

// Get reset key and login from URL
$rp_key = isset($_GET['key']) ? $_GET['key'] : '';
$rp_login = isset($_GET['login']) ? $_GET['login'] : '';

$errors = [];
$success = false;

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_nonce'])) {
    if (wp_verify_nonce($_POST['reset_password_nonce'], 'careernest_reset_password')) {
        $rp_key = sanitize_text_field($_POST['rp_key']);
        $rp_login = sanitize_text_field($_POST['rp_login']);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $errors[] = 'Please enter a new password.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        } else {
            // Verify reset key
            $user = check_password_reset_key($rp_key, $rp_login);

            if (!is_wp_error($user)) {
                // Reset the password
                reset_password($user, $new_password);
                $success = true;
            } else {
                $errors[] = 'Invalid or expired reset link. Please request a new one.';
            }
        }
    } else {
        $errors[] = 'Security verification failed.';
    }
}

// Verify reset key on page load
if (!$success && !empty($rp_key) && !empty($rp_login)) {
    $user = check_password_reset_key($rp_key, $rp_login);
    if (is_wp_error($user)) {
        $errors[] = 'Invalid or expired reset link. Please request a new password reset.';
    }
}

// Get URLs
$pages = get_option('careernest_pages', []);
$login_page_id = isset($pages['login']) ? $pages['login'] : 0;
$forgot_password_id = isset($pages['forgot-password']) ? $pages['forgot-password'] : 0;
$login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url();
$forgot_url = $forgot_password_id ? get_permalink($forgot_password_id) : wp_lostpassword_url();

get_header();
?>

<main id="primary" class="site-main cn-auth-page">
    <div class="cn-auth-container">
        <div class="cn-auth-card">
            <?php if ($success): ?>
                <!-- Success State -->
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
            <?php else: ?>
                <!-- Reset Password Form -->
                <div class="cn-auth-form active">
                    <h2 class="cn-form-title"><?php esc_html_e('Reset Password', 'careernest'); ?></h2>
                    <p class="cn-form-subtitle">
                        <?php esc_html_e('Enter your new password below.', 'careernest'); ?>
                    </p>

                    <?php if (!empty($errors)): ?>
                        <div class="cn-form-messages">
                            <?php foreach ($errors as $error): ?>
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
                                    <span><?php echo esc_html($error); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($errors) || (!empty($rp_key) && !empty($rp_login))): ?>
                        <form method="post" class="cn-form" autocomplete="off">
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
                                        placeholder="<?php esc_attr_e('Enter new password', 'careernest'); ?>" required
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
                            <a href="<?php echo esc_url($forgot_url); ?>" class="cn-btn cn-btn-primary">
                                <?php esc_html_e('Request New Reset Link', 'careernest'); ?>
                            </a>
                            <p style="margin-top: 1rem; text-align: center;">
                                <a href="<?php echo esc_url($login_url); ?>" class="cn-back-link">
                                    <?php esc_html_e('Back to Login', 'careernest'); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

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
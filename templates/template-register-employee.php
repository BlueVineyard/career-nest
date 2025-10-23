<?php

/**
 * Template: CareerNest — Employee Registration Request
 */

defined('ABSPATH') || exit;

get_header();

// Handle form submission
$form_submitted = false;
$form_errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_register_employee_nonce'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['cn_register_employee_nonce'], 'cn_register_employee')) {
        $form_errors[] = 'Security verification failed. Please try again.';
    } else {
        // Process registration
        $result = process_employee_registration();
        if ($result['success']) {
            $form_submitted = true;
            $success_message = $result['message'];
        } else {
            $form_errors = $result['errors'];
        }
    }
}

/**
 * Process employee registration request
 */
function process_employee_registration()
{
    $errors = [];

    // Sanitize and validate form data
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $job_title = sanitize_text_field($_POST['job_title'] ?? '');
    $employer_id = isset($_POST['employer_id']) ? absint($_POST['employer_id']) : 0;

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !is_email($email)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($job_title)) {
        $errors[] = 'Job title is required.';
    }

    if (empty($employer_id)) {
        $errors[] = 'Please select your company.';
    }

    // Verify employer exists
    if ($employer_id) {
        $employer = get_post($employer_id);
        if (!$employer || $employer->post_type !== 'employer' || $employer->post_status !== 'publish') {
            $errors[] = 'Selected company is invalid.';
        }
    }

    // Check if email already exists
    if (email_exists($email)) {
        $errors[] = 'An account with this email address already exists.';
    }

    // Check if email already has a pending request
    $existing_request = get_users([
        'meta_key' => '_pending_employee_request',
        'meta_value' => $email,
        'meta_compare' => '=',
        'number' => 1
    ]);

    if (!empty($existing_request)) {
        $errors[] = 'A pending request with this email address already exists.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Create a temporary user record to hold the request
    $request_id = wp_insert_user([
        'user_login' => 'pending_' . time() . '_' . substr(md5($email), 0, 8),
        'user_email' => $email,
        'display_name' => $full_name,
        'role' => 'subscriber', // Temporary role
        'user_pass' => wp_generate_password(20),
    ]);

    if (is_wp_error($request_id)) {
        return ['success' => false, 'errors' => ['Failed to submit request: ' . $request_id->get_error_message()]];
    }

    // Store request data in user meta
    update_user_meta($request_id, '_pending_employee_request', $email);
    update_user_meta($request_id, '_request_full_name', $full_name);
    update_user_meta($request_id, '_request_email', $email);
    update_user_meta($request_id, '_request_phone', $phone);
    update_user_meta($request_id, '_request_job_title', $job_title);
    update_user_meta($request_id, '_request_employer_id', $employer_id);
    update_user_meta($request_id, '_request_status', 'pending');
    update_user_meta($request_id, '_request_date', current_time('mysql'));

    // Get employer name
    $employer_name = get_the_title($employer_id);

    // Send confirmation email to requester
    $subject = 'CareerNest - Employee Account Request Received';
    $message = "Hi {$full_name},\n\n";
    $message .= "Thank you for your interest in joining CareerNest!\n\n";
    $message .= "We have received your employee account request to join {$employer_name}.\n\n";
    $message .= "Our team will review your request and get back to you within 2-3 business days.\n\n";
    $message .= "If you have any questions, please feel free to contact us.\n\n";
    $message .= "Thank you,\nThe CareerNest Team";

    wp_mail($email, $subject, $message);

    // Notify admin
    $admin_email = get_option('admin_email');
    $admin_subject = 'New Employee Account Request - ' . $full_name . ' (' . $employer_name . ')';
    $admin_message = "A new employee account request has been submitted:\n\n";
    $admin_message .= "Name: {$full_name}\n";
    $admin_message .= "Email: {$email}\n";
    $admin_message .= "Job Title: {$job_title}\n";
    $admin_message .= "Company: {$employer_name}\n";
    $admin_message .= "Phone: {$phone}\n\n";
    $admin_message .= "Review this request in the admin panel under CareerNest > Employee Requests.";

    wp_mail($admin_email, $admin_subject, $admin_message);

    return [
        'success' => true,
        'message' => 'Your employee account request has been submitted successfully! Our team will review your request and contact you within 2-3 business days.'
    ];
}

// Get list of published employers
$employers = get_posts([
    'post_type' => 'employer',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>

<main id="primary" class="site-main">
    <div class="cn-register-container">
        <?php if ($form_submitted): ?>
            <!-- Success Message -->
            <div class="cn-register-success">
                <div class="cn-success-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#10B981" stroke-width="2" />
                        <path d="m9 12 2 2 4-4" stroke="#10B981" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <h1><?php echo esc_html__('Request Submitted!', 'careernest'); ?></h1>
                <p><?php echo esc_html($success_message); ?></p>
                <div class="cn-success-actions">
                    <a href="<?php echo esc_url(home_url()); ?>" class="cn-btn cn-btn-primary">Return Home</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Registration Form -->
            <div class="cn-register-form-container">
                <div class="cn-register-header">
                    <h1><?php echo esc_html__('Request Employee Account', 'careernest'); ?></h1>
                    <p><?php echo esc_html__('Submit your information to join an existing company on CareerNest.', 'careernest'); ?>
                    </p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($form_errors)): ?>
                    <div class="cn-register-errors">
                        <h3><?php echo esc_html__('Please correct the following errors:', 'careernest'); ?></h3>
                        <ul>
                            <?php foreach ($form_errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="post" class="cn-register-form">
                    <?php wp_nonce_field('cn_register_employee', 'cn_register_employee_nonce'); ?>

                    <div class="cn-form-section">
                        <h3><?php echo esc_html__('Your Information', 'careernest'); ?></h3>

                        <div class="cn-form-row">
                            <div class="cn-form-field">
                                <label for="full_name"><?php echo esc_html__('Full Name', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?php echo esc_attr($_POST['full_name'] ?? ''); ?>" required class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="email"><?php echo esc_html__('Email Address', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" required class="cn-input">
                            </div>
                        </div>

                        <div class="cn-form-row">
                            <div class="cn-form-field">
                                <label for="phone"><?php echo esc_html__('Phone Number', 'careernest'); ?></label>
                                <input type="tel" id="phone" name="phone"
                                    value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="job_title"><?php echo esc_html__('Job Title', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <input type="text" id="job_title" name="job_title"
                                    value="<?php echo esc_attr($_POST['job_title'] ?? ''); ?>" required class="cn-input"
                                    placeholder="e.g., HR Manager, Recruiter">
                            </div>
                        </div>

                        <div class="cn-form-field">
                            <label for="employer_id"><?php echo esc_html__('Select Your Company', 'careernest'); ?> <span
                                    class="required">*</span></label>
                            <select id="employer_id" name="employer_id" required class="cn-input">
                                <option value=""><?php echo esc_html__('-- Select Company --', 'careernest'); ?></option>
                                <?php foreach ($employers as $employer): ?>
                                    <option value="<?php echo esc_attr($employer->ID); ?>"
                                        <?php selected($_POST['employer_id'] ?? '', $employer->ID); ?>>
                                        <?php echo esc_html($employer->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="cn-field-help">
                                <?php echo esc_html__('Select the company you work for. If your company is not listed, they need to register first.', 'careernest'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="cn-form-actions">
                        <button type="submit" class="cn-btn cn-btn-primary cn-btn-large">
                            <?php echo esc_html__('Submit for Approval', 'careernest'); ?>
                        </button>
                        <p class="cn-form-notice">
                            <?php echo esc_html__('Your request will be reviewed by our team. You will receive login credentials via email once approved.', 'careernest'); ?>
                        </p>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Container */
    .site-main {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .cn-register-container {
        margin: 2rem 0;
    }

    /* Header */
    .cn-register-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .cn-register-header h1 {
        color: #333;
        font-size: 2rem;
        margin: 0 0 1rem 0;
    }

    .cn-register-header p {
        color: #666;
        font-size: 1.1rem;
        margin: 0;
        line-height: 1.6;
    }

    /* Form Styling */
    .cn-register-form-container {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 2rem;
    }

    .cn-form-section {
        margin-bottom: 2rem;
    }

    .cn-form-section h3 {
        color: #333;
        font-size: 1.3rem;
        margin: 0 0 1.5rem 0;
        font-weight: 600;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #0073aa;
    }

    .cn-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .cn-form-field {
        margin-bottom: 1.5rem;
    }

    .cn-form-field label {
        display: block;
        color: #333;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .required {
        color: #dc3545;
    }

    .cn-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        transition: border-color 0.2s ease;
    }

    .cn-input:focus {
        outline: none;
        border-color: #0073aa;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
    }

    .cn-field-help {
        color: #666;
        font-size: 0.85rem;
        margin: 0.5rem 0 0 0;
    }

    /* Buttons */
    .cn-btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .cn-btn-primary {
        background: #0073aa;
        color: white;
    }

    .cn-btn-primary:hover {
        background: #005a87;
        color: white;
    }

    .cn-btn-large {
        padding: 1rem 2rem;
        font-size: 1.1rem;
    }

    .cn-form-actions {
        text-align: center;
        margin-top: 2rem;
    }

    .cn-form-notice {
        margin-top: 1rem;
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Success/Error Messages */
    .cn-register-success {
        text-align: center;
        padding: 3rem 2rem;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .cn-success-icon {
        margin-bottom: 1rem;
    }

    .cn-register-success h1 {
        color: #10B981;
        font-size: 2rem;
        margin: 0 0 1rem 0;
    }

    .cn-register-success p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .cn-success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .cn-register-errors {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .cn-register-errors h3 {
        color: #721c24;
        margin: 0 0 0.5rem 0;
    }

    .cn-register-errors ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .cn-register-errors li {
        color: #721c24;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .cn-form-row {
            grid-template-columns: 1fr;
        }

        .cn-success-actions {
            flex-direction: column;
        }

        .cn-register-form-container {
            padding: 1.5rem;
        }

        .cn-register-header h1 {
            font-size: 1.5rem;
        }
    }
</style>

<?php
get_footer();

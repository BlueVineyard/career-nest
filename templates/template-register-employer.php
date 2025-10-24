<?php

/**
 * Template: CareerNest — Employer Registration Request
 */

defined('ABSPATH') || exit;

get_header();

// Handle form submission
$form_submitted = false;
$form_errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_register_employer_nonce'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['cn_register_employer_nonce'], 'cn_register_employer')) {
        $form_errors[] = 'Security verification failed. Please try again.';
    } else {
        // Process registration
        $result = process_employer_registration();
        if ($result['success']) {
            $form_submitted = true;
            $success_message = $result['message'];
        } else {
            $form_errors = $result['errors'];
        }
    }
}

/**
 * Process employer registration request
 */
function process_employer_registration()
{
    $errors = [];

    // Sanitize and validate form data
    $company_name = sanitize_text_field($_POST['company_name'] ?? '');
    $contact_name = sanitize_text_field($_POST['contact_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $company_website = esc_url_raw($_POST['company_website'] ?? '');
    $company_location = sanitize_text_field($_POST['company_location'] ?? '');
    $company_size = sanitize_text_field($_POST['company_size'] ?? '');
    $industry = sanitize_text_field($_POST['industry'] ?? '');
    $about_company = sanitize_textarea_field($_POST['about_company'] ?? '');

    // Validation
    if (empty($company_name)) {
        $errors[] = 'Company name is required.';
    }

    if (empty($contact_name)) {
        $errors[] = 'Contact person name is required.';
    }

    if (empty($email) || !is_email($email)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($company_location)) {
        $errors[] = 'Company location is required.';
    }

    if (empty($company_website)) {
        $errors[] = 'Company website is required.';
    }

    if (empty($about_company)) {
        $errors[] = 'Please tell us about your company.';
    }

    // Check if email already has a pending or approved request
    $existing_request = new \WP_Query([
        'post_type' => 'employer',
        'post_status' => ['pending', 'publish'],
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_contact_email',
                'value' => $email,
                'compare' => '='
            ]
        ]
    ]);

    if ($existing_request->have_posts()) {
        $errors[] = 'A request with this email address already exists.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Create employer request as pending post
    $employer_id = wp_insert_post([
        'post_type' => 'employer',
        'post_title' => $company_name,
        'post_content' => $about_company,
        'post_status' => 'pending', // Pending approval
        'post_author' => 1, // System user
    ]);

    if (is_wp_error($employer_id)) {
        return ['success' => false, 'errors' => ['Failed to submit request: ' . $employer_id->get_error_message()]];
    }

    // Save request meta data
    update_post_meta($employer_id, '_contact_name', $contact_name);
    update_post_meta($employer_id, '_contact_email', $email);
    update_post_meta($employer_id, '_phone', $phone);
    update_post_meta($employer_id, '_website', $company_website);
    update_post_meta($employer_id, '_location', $company_location);
    update_post_meta($employer_id, '_company_size', $company_size);
    update_post_meta($employer_id, '_industry', $industry);
    update_post_meta($employer_id, '_request_status', 'pending'); // Custom status
    update_post_meta($employer_id, '_request_date', current_time('mysql'));

    // Send confirmation email to requester using HTML template
    \CareerNest\Email\Mailer::send($email, 'employer_request_confirmation', [
        'user_name' => $contact_name,
        'company_name' => $company_name,
    ]);

    // Notify admin using HTML template
    $admin_message = "A new employer account request has been submitted:\n\n";
    $admin_message .= "<strong>Company:</strong> {$company_name}<br>";
    $admin_message .= "<strong>Contact:</strong> {$contact_name}<br>";
    $admin_message .= "<strong>Email:</strong> {$email}<br>";
    $admin_message .= "<strong>Location:</strong> {$company_location}<br><br>";
    $admin_message .= "Review this request in the admin panel under Employers > Account Requests.";

    \CareerNest\Email\Mailer::send_admin_notification(
        'New Employer Account Request - ' . $company_name,
        $admin_message
    );

    return [
        'success' => true,
        'message' => 'Your employer account request has been submitted successfully! Our team will review your request and contact you within 2-3 business days.'
    ];
}

// Check if Google Maps API is available
$options = get_option('careernest_options', []);
$maps_api_key = isset($options['maps_api_key']) ? trim((string) $options['maps_api_key']) : '';
$has_maps_api = $maps_api_key !== '';
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
                    <?php
                    $pages = get_option('careernest_pages', []);
                    $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                    if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'):
                    ?>
                        <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>" class="cn-btn cn-btn-primary">Browse
                            Jobs</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url()); ?>" class="cn-btn cn-btn-secondary">Return Home</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Registration Form -->
            <div class="cn-register-form-container">
                <div class="cn-register-header">
                    <h1><?php echo esc_html__('Request Employer Account', 'careernest'); ?></h1>
                    <p><?php echo esc_html__('Submit your company information and our team will review your request to create an employer account.', 'careernest'); ?>
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
                <form method="post" class="cn-register-form" id="cn-employer-register-form">
                    <?php wp_nonce_field('cn_register_employer', 'cn_register_employer_nonce'); ?>

                    <div class="cn-form-section">
                        <h3><?php echo esc_html__('Company Information', 'careernest'); ?></h3>

                        <div class="cn-form-row">
                            <div class="cn-form-field">
                                <label for="company_name"><?php echo esc_html__('Company Name', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <input type="text" id="company_name" name="company_name"
                                    value="<?php echo esc_attr($_POST['company_name'] ?? ''); ?>" required class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="industry"><?php echo esc_html__('Industry', 'careernest'); ?></label>
                                <input type="text" id="industry" name="industry"
                                    value="<?php echo esc_attr($_POST['industry'] ?? ''); ?>" class="cn-input"
                                    placeholder="e.g., Technology, Healthcare, Finance">
                            </div>
                        </div>

                        <div class="cn-form-row">
                            <div class="cn-form-field">
                                <label for="company_location"><?php echo esc_html__('Company Location', 'careernest'); ?>
                                    <span class="required">*</span></label>
                                <input type="text" id="company_location" name="company_location"
                                    value="<?php echo esc_attr($_POST['company_location'] ?? ''); ?>" required
                                    class="cn-input cn-location-autocomplete" placeholder="e.g., Melbourne, VIC">
                                <p class="cn-field-help">
                                    <?php echo esc_html__('Start typing to search for your location', 'careernest'); ?></p>
                            </div>

                            <div class="cn-form-field">
                                <label for="company_size"><?php echo esc_html__('Company Size', 'careernest'); ?></label>
                                <select id="company_size" name="company_size" class="cn-input">
                                    <option value=""><?php echo esc_html__('Select size', 'careernest'); ?></option>
                                    <option value="1-10" <?php selected($_POST['company_size'] ?? '', '1-10'); ?>>1-10
                                        employees</option>
                                    <option value="11-50" <?php selected($_POST['company_size'] ?? '', '11-50'); ?>>11-50
                                        employees</option>
                                    <option value="51-200" <?php selected($_POST['company_size'] ?? '', '51-200'); ?>>51-200
                                        employees</option>
                                    <option value="201-500" <?php selected($_POST['company_size'] ?? '', '201-500'); ?>>
                                        201-500 employees</option>
                                    <option value="501-1000" <?php selected($_POST['company_size'] ?? '', '501-1000'); ?>>
                                        501-1000 employees</option>
                                    <option value="1001+" <?php selected($_POST['company_size'] ?? '', '1001+'); ?>>1001+
                                        employees</option>
                                </select>
                            </div>
                        </div>

                        <div class="cn-form-field">
                            <label for="company_website"><?php echo esc_html__('Company Website', 'careernest'); ?> <span
                                    class="required">*</span></label>
                            <input type="url" id="company_website" name="company_website"
                                value="<?php echo esc_attr($_POST['company_website'] ?? ''); ?>" required class="cn-input"
                                placeholder="https://example.com">
                        </div>

                        <div class="cn-form-field">
                            <label for="about_company"><?php echo esc_html__('About Your Company', 'careernest'); ?> <span
                                    class="required">*</span></label>
                            <textarea id="about_company" name="about_company" rows="4" required class="cn-input"
                                placeholder="Tell us about your company, culture, and what makes it a great place to work..."><?php echo esc_textarea($_POST['about_company'] ?? ''); ?></textarea>
                            <p class="cn-field-help">
                                <?php echo esc_html__('This will be displayed on your company profile after approval', 'careernest'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="cn-form-section">
                        <h3><?php echo esc_html__('Contact Information', 'careernest'); ?></h3>

                        <div class="cn-form-row">
                            <div class="cn-form-field">
                                <label for="contact_name"><?php echo esc_html__('Contact Person Name', 'careernest'); ?>
                                    <span class="required">*</span></label>
                                <input type="text" id="contact_name" name="contact_name"
                                    value="<?php echo esc_attr($_POST['contact_name'] ?? ''); ?>" required class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="phone"><?php echo esc_html__('Contact Phone', 'careernest'); ?></label>
                                <input type="tel" id="phone" name="phone"
                                    value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" class="cn-input">
                            </div>
                        </div>

                        <div class="cn-form-field">
                            <label for="email"><?php echo esc_html__('Contact Email', 'careernest'); ?> <span
                                    class="required">*</span></label>
                            <input type="email" id="email" name="email"
                                value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" required class="cn-input">
                            <p class="cn-field-help">
                                <?php echo esc_html__('You will receive notifications at this email address', 'careernest'); ?>
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

<?php if ($has_maps_api && !$form_submitted): ?>
    <script>
        // Initialize Google Maps Autocomplete for company location
        function initEmployerAutocomplete() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                const input = document.getElementById('company_location');
                if (input) {
                    const autocomplete = new google.maps.places.Autocomplete(input, {
                        types: ['(cities)'],
                        fields: ['formatted_address', 'geometry', 'name']
                    });

                    autocomplete.addListener('place_changed', function() {
                        const place = autocomplete.getPlace();
                        if (place.geometry) {
                            input.value = place.formatted_address || place.name;
                        }
                    });
                }
            }
        }

        // Initialize when Maps API is ready
        if (typeof google !== 'undefined') {
            initEmployerAutocomplete();
        } else {
            window.initEmployerAutocomplete = initEmployerAutocomplete;
        }
    </script>

<?php
    // Enqueue Google Maps API
    wp_enqueue_script(
        'google-maps',
        'https://maps.googleapis.com/maps/api/js?key=' . urlencode($maps_api_key) . '&libraries=places&callback=initEmployerAutocomplete',
        [],
        null,
        true
    );
endif;
?>

<style>
    /* Container */
    .site-main {
        max-width: 800px;
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
        padding-bottom: 2rem;
        border-bottom: 1px solid #e0e0e0;
    }

    .cn-form-section:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .cn-form-section h3 {
        color: #333;
        font-size: 1.3rem;
        margin: 0 0 1.5rem 0;
        font-weight: 600;
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

    textarea.cn-input {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
        line-height: 1.5;
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

    .cn-btn-secondary {
        background: #6c757d;
        color: white;
    }

    .cn-btn-secondary:hover {
        background: #545b62;
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

<?php

/**
 * Template: CareerNest — Edit Company Profile (Frontend)
 */

defined('ABSPATH') || exit;

// Security check - must be logged in
if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url());
    exit;
}

get_header();

// Get employer ID from query parameter
$employer_id = isset($_GET['employer_id']) ? (int) $_GET['employer_id'] : 0;

if (!$employer_id) {
?>
    <main id="primary" class="site-main">
        <div class="cn-error-container">
            <h1><?php echo esc_html__('Error', 'careernest'); ?></h1>
            <p><?php echo esc_html__('Invalid employer profile.', 'careernest'); ?></p>
        </div>
    </main>
<?php
    get_footer();
    exit;
}

// Verify employer post exists
$employer_profile = get_post($employer_id);
if (!$employer_profile || $employer_profile->post_type !== 'employer') {
?>
    <main id="primary" class="site-main">
        <div class="cn-error-container">
            <h1><?php echo esc_html__('Error', 'careernest'); ?></h1>
            <p><?php echo esc_html__('Employer profile not found.', 'careernest'); ?></p>
        </div>
    </main>
<?php
    get_footer();
    exit;
}

// Verify user is the owner
$current_user = wp_get_current_user();
$owner_id = (int) get_post_meta($employer_id, '_user_id', true);
$user_employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);

$is_owner = ($user_employer_id === $employer_id && $owner_id === $current_user->ID);

if (!$is_owner) {
?>
    <main id="primary" class="site-main">
        <div class="cn-error-container">
            <h1><?php echo esc_html__('Access Denied', 'careernest'); ?></h1>
            <p><?php echo esc_html__('You do not have permission to edit this company profile.', 'careernest'); ?></p>
        </div>
    </main>
<?php
    get_footer();
    exit;
}

// Handle form submission
$success_message = '';
$error_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_edit_company_profile_nonce'])) {
    if (!wp_verify_nonce($_POST['cn_edit_company_profile_nonce'], 'cn_edit_company_profile')) {
        $error_messages[] = 'Security verification failed. Please try again.';
    } else {
        // Sanitize form data
        $company_name = sanitize_text_field($_POST['company_name'] ?? '');
        $tagline = sanitize_text_field($_POST['tagline'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        $contact_email = sanitize_email($_POST['contact_email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $company_size = sanitize_text_field($_POST['company_size'] ?? '');
        $founded_year = sanitize_text_field($_POST['founded_year'] ?? '');
        $industry_desc = sanitize_text_field($_POST['industry_description'] ?? '');
        $specialities = sanitize_text_field($_POST['specialities'] ?? '');

        // Long text fields
        $about = wp_kses_post($_POST['about'] ?? '');
        $mission = wp_kses_post($_POST['mission'] ?? '');
        $spotlight = wp_kses_post($_POST['spotlight'] ?? '');
        $interested = wp_kses_post($_POST['interested_in_working'] ?? '');

        // Validation
        if (empty($company_name)) {
            $error_messages[] = 'Company name is required.';
        }

        if (!empty($contact_email) && !is_email($contact_email)) {
            $error_messages[] = 'Please enter a valid contact email address.';
        }

        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $error_messages[] = 'Please enter a valid website URL.';
        }

        // Update if no errors
        if (empty($error_messages)) {
            // Update post title (company name)
            $post_data = [
                'ID' => $employer_id,
                'post_title' => $company_name,
            ];

            $result = wp_update_post($post_data);

            if (is_wp_error($result)) {
                $error_messages[] = $result->get_error_message();
            } else {
                // Update meta fields
                update_post_meta($employer_id, '_tagline', $tagline);
                update_post_meta($employer_id, '_website', $website);
                update_post_meta($employer_id, '_contact_email', $contact_email);
                update_post_meta($employer_id, '_phone', $phone);
                update_post_meta($employer_id, '_location', $location);
                update_post_meta($employer_id, '_company_size', $company_size);
                update_post_meta($employer_id, '_founded_year', $founded_year);
                update_post_meta($employer_id, '_industry_description', $industry_desc);
                update_post_meta($employer_id, '_specialities', $specialities);
                update_post_meta($employer_id, '_about', $about);
                update_post_meta($employer_id, '_mission', $mission);
                update_post_meta($employer_id, '_spotlight', $spotlight);
                update_post_meta($employer_id, '_interested_in_working', $interested);

                $success_message = 'Company profile updated successfully!';

                // Refresh employer profile data
                $employer_profile = get_post($employer_id);
            }
        }
    }
}

// Get current profile data
$company_name = $employer_profile->post_title;
$tagline = get_post_meta($employer_id, '_tagline', true);
$website = get_post_meta($employer_id, '_website', true);
$contact_email = get_post_meta($employer_id, '_contact_email', true);
$phone = get_post_meta($employer_id, '_phone', true);
$location = get_post_meta($employer_id, '_location', true);
$company_size = get_post_meta($employer_id, '_company_size', true);
$founded_year = get_post_meta($employer_id, '_founded_year', true);
$industry_desc = get_post_meta($employer_id, '_industry_description', true);
$specialities = get_post_meta($employer_id, '_specialities', true);
$about = get_post_meta($employer_id, '_about', true);
$mission = get_post_meta($employer_id, '_mission', true);
$spotlight = get_post_meta($employer_id, '_spotlight', true);
$interested = get_post_meta($employer_id, '_interested_in_working', true);
?>

<main id="primary" class="site-main">
    <div class="cn-profile-edit-container">
        <!-- Header -->
        <div class="cn-profile-edit-header">
            <div>
                <h1><?php echo esc_html__('Edit Company Profile', 'careernest'); ?></h1>
                <p class="cn-profile-edit-subtitle">
                    <?php echo esc_html__('Update your company information and public profile', 'careernest'); ?>
                </p>
            </div>
            <a href="<?php echo esc_url(get_permalink($employer_id)); ?>" class="cn-btn cn-btn-outline">
                <?php echo esc_html__('View Public Profile', 'careernest'); ?>
            </a>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="cn-message cn-message-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php echo esc_html($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_messages)): ?>
            <div class="cn-message cn-message-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                    <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                <div>
                    <?php foreach ($error_messages as $error): ?>
                        <p><?php echo esc_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="post" class="cn-profile-edit-form">
            <?php wp_nonce_field('cn_edit_company_profile', 'cn_edit_company_profile_nonce'); ?>

            <!-- Basic Information -->
            <div class="cn-form-section">
                <h2 class="cn-form-section-title"><?php echo esc_html__('Basic Information', 'careernest'); ?></h2>

                <div class="cn-form-field">
                    <label for="company_name">
                        <?php echo esc_html__('Company Name', 'careernest'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="company_name" name="company_name"
                        value="<?php echo esc_attr($company_name); ?>" required class="cn-input">
                </div>

                <div class="cn-form-field">
                    <label for="tagline"><?php echo esc_html__('Tagline', 'careernest'); ?></label>
                    <input type="text" id="tagline" name="tagline" value="<?php echo esc_attr($tagline); ?>"
                        class="cn-input" placeholder="A brief tagline for your company">
                </div>

                <div class="cn-form-row">
                    <div class="cn-form-field">
                        <label for="website"><?php echo esc_html__('Website', 'careernest'); ?></label>
                        <input type="url" id="website" name="website" value="<?php echo esc_attr($website); ?>"
                            class="cn-input" placeholder="https://www.example.com">
                    </div>

                    <div class="cn-form-field">
                        <label for="contact_email"><?php echo esc_html__('Contact Email', 'careernest'); ?></label>
                        <input type="email" id="contact_email" name="contact_email"
                            value="<?php echo esc_attr($contact_email); ?>" class="cn-input"
                            placeholder="contact@example.com">
                    </div>
                </div>

                <div class="cn-form-row">
                    <div class="cn-form-field">
                        <label for="phone"><?php echo esc_html__('Phone', 'careernest'); ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>"
                            class="cn-input" placeholder="+1 (555) 123-4567">
                    </div>

                    <div class="cn-form-field">
                        <label for="location"><?php echo esc_html__('Location', 'careernest'); ?></label>
                        <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>"
                            class="cn-input" placeholder="City, State/Country">
                    </div>
                </div>
            </div>

            <!-- Company Details -->
            <div class="cn-form-section">
                <h2 class="cn-form-section-title"><?php echo esc_html__('Company Details', 'careernest'); ?></h2>

                <div class="cn-form-row">
                    <div class="cn-form-field">
                        <label for="company_size"><?php echo esc_html__('Company Size', 'careernest'); ?></label>
                        <input type="text" id="company_size" name="company_size"
                            value="<?php echo esc_attr($company_size); ?>" class="cn-input"
                            placeholder="e.g., 50-100 employees">
                    </div>

                    <div class="cn-form-field">
                        <label for="founded_year"><?php echo esc_html__('Founded Year', 'careernest'); ?></label>
                        <input type="text" id="founded_year" name="founded_year"
                            value="<?php echo esc_attr($founded_year); ?>" class="cn-input" placeholder="e.g., 2010">
                    </div>
                </div>

                <div class="cn-form-field">
                    <label for="industry_description"><?php echo esc_html__('Industry', 'careernest'); ?></label>
                    <input type="text" id="industry_description" name="industry_description"
                        value="<?php echo esc_attr($industry_desc); ?>" class="cn-input"
                        placeholder="e.g., Technology, Healthcare, Finance">
                </div>

                <div class="cn-form-field">
                    <label for="specialities"><?php echo esc_html__('Specialities', 'careernest'); ?></label>
                    <input type="text" id="specialities" name="specialities"
                        value="<?php echo esc_attr($specialities); ?>" class="cn-input"
                        placeholder="e.g., Software Development, Cloud Computing">
                </div>
            </div>

            <!-- About Sections -->
            <div class="cn-form-section">
                <h2 class="cn-form-section-title"><?php echo esc_html__('About Your Company', 'careernest'); ?></h2>

                <div class="cn-form-field">
                    <label for="about"><?php echo esc_html__('About', 'careernest'); ?></label>
                    <p class="cn-field-help">
                        <?php echo esc_html__('Tell potential candidates about your company', 'careernest'); ?>
                    </p>
                    <?php
                    wp_editor($about, 'about', [
                        'textarea_name' => 'about',
                        'textarea_rows' => 8,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true,
                    ]);
                    ?>
                </div>

                <div class="cn-form-field">
                    <label for="mission"><?php echo esc_html__('Our Mission Statement', 'careernest'); ?></label>
                    <p class="cn-field-help">
                        <?php echo esc_html__('What drives your company and its goals', 'careernest'); ?>
                    </p>
                    <?php
                    wp_editor($mission, 'mission', [
                        'textarea_name' => 'mission',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true,
                    ]);
                    ?>
                </div>

                <div class="cn-form-field">
                    <label for="spotlight"><?php echo esc_html__('Company Spotlight', 'careernest'); ?></label>
                    <p class="cn-field-help">
                        <?php echo esc_html__('Highlight recent achievements or news', 'careernest'); ?>
                    </p>
                    <?php
                    wp_editor($spotlight, 'spotlight', [
                        'textarea_name' => 'spotlight',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true,
                    ]);
                    ?>
                </div>

                <div class="cn-form-field">
                    <label
                        for="interested_in_working"><?php echo esc_html__('Interested in Working for Us?', 'careernest'); ?></label>
                    <p class="cn-field-help">
                        <?php echo esc_html__('Message to potential candidates about why they should join', 'careernest'); ?>
                    </p>
                    <?php
                    wp_editor($interested, 'interested_in_working', [
                        'textarea_name' => 'interested_in_working',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true,
                    ]);
                    ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="cn-form-actions">
                <button type="submit" class="cn-btn cn-btn-primary">
                    <?php echo esc_html__('Save Changes', 'careernest'); ?>
                </button>
                <a href="<?php echo esc_url(get_permalink($employer_id)); ?>" class="cn-btn cn-btn-outline">
                    <?php echo esc_html__('Cancel', 'careernest'); ?>
                </a>
            </div>
        </form>
    </div>
</main>

<?php
get_footer();

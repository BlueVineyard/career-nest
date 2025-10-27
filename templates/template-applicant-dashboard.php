<?php

/**
 * Template: CareerNest — Applicant Dashboard
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

// Handle profile update
$profile_updated = false;
$profile_errors = [];
$profile_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_update_profile_nonce'])) {
    if (!wp_verify_nonce($_POST['cn_update_profile_nonce'], 'cn_update_profile')) {
        $profile_errors[] = 'Security verification failed. Please try again.';
    } else {
        $result = process_profile_update();
        if ($result['success']) {
            // Redirect to prevent form resubmission on refresh
            wp_safe_redirect(add_query_arg('profile_updated', '1', get_permalink()));
            exit;
        } else {
            $profile_errors = $result['errors'];
        }
    }
}

// Check if redirected after successful update
if (isset($_GET['profile_updated']) && $_GET['profile_updated'] === '1') {
    $profile_updated = true;
    $profile_success = 'Your profile has been updated successfully!';
}

/**
 * Process profile update
 */
function process_profile_update()
{
    global $current_user;

    $errors = [];

    // Sanitize form data
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $professional_title = sanitize_text_field($_POST['professional_title'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $right_to_work = sanitize_text_field($_POST['right_to_work'] ?? '');
    $work_types = isset($_POST['work_types']) ? array_map('sanitize_text_field', $_POST['work_types']) : [];
    $available_for_work = isset($_POST['available_for_work']) ? 1 : 0;
    $skills_input = sanitize_text_field($_POST['skills_input'] ?? '');
    $personal_summary = wp_kses_post($_POST['personal_summary'] ?? '');
    $linkedin_url = esc_url_raw($_POST['linkedin_url'] ?? '');

    // Process education data
    $education_data = [];
    if (isset($_POST['education']) && is_array($_POST['education'])) {
        foreach ($_POST['education'] as $edu) {
            if (!empty($edu['institution']) || !empty($edu['certification'])) {
                $education_data[] = [
                    'institution' => sanitize_text_field($edu['institution'] ?? ''),
                    'certification' => sanitize_text_field($edu['certification'] ?? ''),
                    'end_date' => sanitize_text_field($edu['end_date'] ?? ''),
                    'complete' => isset($edu['complete']) ? 1 : 0
                ];
            }
        }
    }

    // Process work experience data
    $experience_data = [];
    if (isset($_POST['experience']) && is_array($_POST['experience'])) {
        foreach ($_POST['experience'] as $exp) {
            if (!empty($exp['company']) || !empty($exp['title'])) {
                $experience_data[] = [
                    'company' => sanitize_text_field($exp['company'] ?? ''),
                    'title' => sanitize_text_field($exp['title'] ?? ''),
                    'start_date' => sanitize_text_field($exp['start_date'] ?? ''),
                    'end_date' => sanitize_text_field($exp['end_date'] ?? ''),
                    'current' => isset($exp['current']) ? 1 : 0,
                    'description' => sanitize_textarea_field($exp['description'] ?? '')
                ];
            }
        }
    }

    // Process licenses data
    $licenses_data = [];
    if (isset($_POST['licenses']) && is_array($_POST['licenses'])) {
        foreach ($_POST['licenses'] as $license) {
            if (!empty($license['name'])) {
                $licenses_data[] = [
                    'name' => sanitize_text_field($license['name'] ?? ''),
                    'issuer' => sanitize_text_field($license['issuer'] ?? ''),
                    'issue_date' => sanitize_text_field($license['issue_date'] ?? ''),
                    'expiry_date' => sanitize_text_field($license['expiry_date'] ?? ''),
                    'credential_id' => sanitize_text_field($license['credential_id'] ?? '')
                ];
            }
        }
    }

    // Process links data
    $links_data = [];
    if (isset($_POST['links']) && is_array($_POST['links'])) {
        foreach ($_POST['links'] as $link) {
            if (!empty($link['url'])) {
                $links_data[] = [
                    'label' => sanitize_text_field($link['label'] ?? ''),
                    'url' => esc_url_raw($link['url'] ?? '')
                ];
            }
        }
    }

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Find applicant profile
    $applicant_query = new WP_Query([
        'post_type' => 'applicant',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_user_id',
                'value' => $current_user->ID,
                'compare' => '='
            ]
        ]
    ]);

    $applicant_id = 0;
    if ($applicant_query->have_posts()) {
        $applicant_id = $applicant_query->posts[0]->ID;
    }

    if (!$applicant_id) {
        return ['success' => false, 'errors' => ['Profile not found.']];
    }

    // Update user display name
    wp_update_user([
        'ID' => $current_user->ID,
        'display_name' => $full_name,
        'first_name' => explode(' ', $full_name)[0],
        'last_name' => substr($full_name, strlen(explode(' ', $full_name)[0]) + 1),
    ]);

    // Update applicant profile
    wp_update_post([
        'ID' => $applicant_id,
        'post_title' => $full_name
    ]);

    // Update applicant post content (personal summary)
    wp_update_post([
        'ID' => $applicant_id,
        'post_title' => $full_name,
        'post_content' => $personal_summary
    ]);

    // Handle profile picture upload (priority over removal)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Invalid file type. Please upload a JPG, PNG, or GIF image.';
        }
        // Validate file size
        elseif ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds 5MB limit. Please upload a smaller image.';
        }
        // Process upload
        else {
            $upload = wp_handle_upload($file, ['test_form' => false]);

            if (isset($upload['error'])) {
                $errors[] = 'Upload failed: ' . $upload['error'];
            } else {
                $attachment_id = wp_insert_attachment([
                    'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
                    'post_content' => '',
                    'post_status' => 'inherit',
                    'post_mime_type' => $upload['type'],
                ], $upload['file']);

                if (is_wp_error($attachment_id)) {
                    $errors[] = 'Failed to create attachment.';
                } else {
                    // Generate image metadata
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                    wp_update_attachment_metadata($attachment_id, $attach_data);

                    // Delete old profile picture if exists
                    $old_picture_id = get_post_thumbnail_id($applicant_id);
                    if ($old_picture_id) {
                        wp_delete_attachment($old_picture_id, true);
                    }

                    // Set as featured image
                    set_post_thumbnail($applicant_id, $attachment_id);
                }
            }
        }
    }
    // Handle profile picture removal (only if no new file uploaded)
    elseif (isset($_POST['remove_profile_picture']) && $_POST['remove_profile_picture'] == '1') {
        $old_picture_id = get_post_thumbnail_id($applicant_id);
        if ($old_picture_id) {
            wp_delete_attachment($old_picture_id, true);
            delete_post_thumbnail($applicant_id);
        }
    }

    // Update meta fields
    update_post_meta($applicant_id, '_professional_title', $professional_title);
    update_post_meta($applicant_id, '_phone', $phone);
    update_post_meta($applicant_id, '_location', $location);
    update_post_meta($applicant_id, '_right_to_work', $right_to_work);
    update_post_meta($applicant_id, '_work_types', $work_types);
    update_post_meta($applicant_id, '_available_for_work', $available_for_work);
    update_post_meta($applicant_id, '_linkedin_url', $linkedin_url);
    update_post_meta($applicant_id, '_education', $education_data);
    update_post_meta($applicant_id, '_experience', $experience_data);
    update_post_meta($applicant_id, '_licenses', $licenses_data);
    update_post_meta($applicant_id, '_links', $links_data);

    // Handle skills
    if ($skills_input) {
        $skills = array_map('trim', explode(',', $skills_input));
        $skills = array_filter($skills, function ($skill) {
            return !empty($skill);
        });
        update_post_meta($applicant_id, '_skills', $skills);
    }

    // Return errors if any occurred during image upload
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    return [
        'success' => true,
        'message' => 'Your profile has been updated successfully!'
    ];
}

// Get current user and applicant profile
$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// Verify user has applicant role
if (!in_array('applicant', $user_roles, true)) {
?>
    <main id="primary" class="site-main">
        <div class="cn-dashboard-error">
            <h1><?php echo esc_html__('Access Denied', 'careernest'); ?></h1>
            <p><?php echo esc_html__('You do not have permission to access this dashboard.', 'careernest'); ?></p>
        </div>
    </main>
<?php
    get_footer();
    return;
}

// Find applicant profile
$applicant_query = new WP_Query([
    'post_type' => 'applicant',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => '_user_id',
            'value' => $current_user->ID,
            'compare' => '='
        ]
    ]
]);

$applicant_profile = null;
$applicant_id = 0;
if ($applicant_query->have_posts()) {
    $applicant_profile = $applicant_query->posts[0];
    $applicant_id = $applicant_profile->ID;
}

// Calculate profile completeness
$profile_completeness = ['percentage' => 0, 'missing_fields' => []];
if ($applicant_id) {
    $profile_completeness = \CareerNest\Profile_Helper::calculate_completeness($applicant_id);
}

// Get applicant data
$prof_title = $applicant_id ? get_post_meta($applicant_id, '_professional_title', true) : '';
$phone = $applicant_id ? get_post_meta($applicant_id, '_phone', true) : '';
$location = $applicant_id ? get_post_meta($applicant_id, '_location', true) : '';
$available_for_work = $applicant_id ? get_post_meta($applicant_id, '_available_for_work', true) : false;
$skills = $applicant_id ? get_post_meta($applicant_id, '_skills', true) : [];
$work_types = $applicant_id ? get_post_meta($applicant_id, '_work_types', true) : [];
$education = $applicant_id ? get_post_meta($applicant_id, '_education', true) : [];
$experience = $applicant_id ? get_post_meta($applicant_id, '_experience', true) : [];
$licenses = $applicant_id ? get_post_meta($applicant_id, '_licenses', true) : [];
$links = $applicant_id ? get_post_meta($applicant_id, '_links', true) : [];
$linkedin_url = $applicant_id ? get_post_meta($applicant_id, '_linkedin_url', true) : '';

// Get personal summary from post content
$personal_summary = '';
if ($applicant_profile) {
    $personal_summary = $applicant_profile->post_content;
}

// Ensure arrays are properly formatted
$skills = is_array($skills) ? $skills : [];
$work_types = is_array($work_types) ? $work_types : [];
$education = is_array($education) ? $education : [];
$experience = is_array($experience) ? $experience : [];
$licenses = is_array($licenses) ? $licenses : [];
$links = is_array($links) ? $links : [];

// Get user's applications
$applications_query = new WP_Query([
    'post_type' => 'job_application',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_user_id',
            'value' => $current_user->ID,
            'compare' => '='
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get application statistics
$total_applications = $applications_query->found_posts;
$status_counts = [
    'new' => 0,
    'interviewed' => 0,
    'offer_extended' => 0,
    'hired' => 0,
    'rejected' => 0,
    'archived' => 0
];

if ($applications_query->have_posts()) {
    foreach ($applications_query->posts as $app) {
        $status = get_post_meta($app->ID, '_app_status', true) ?: 'new';
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }
}

// Get recommended jobs based on user's profile
$recommended_jobs = [];
if ($applicant_id && ($skills || $work_types)) {
    $job_args = [
        'post_type' => 'job_listing',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ];

    $recommended_query = new WP_Query($job_args);
    if ($recommended_query->have_posts()) {
        $recommended_jobs = $recommended_query->posts;
    }
    wp_reset_postdata();
}
?>

<main id="primary" class="site-main">
    <div class="cn-dashboard-container">
        <!-- Dashboard Header -->
        <div class="cn-dashboard-header">
            <div class="cn-header-content">
                <div class="cn-user-profile-section">
                    <?php
                    // Get profile picture (featured image)
                    $profile_picture_id = $applicant_id ? get_post_thumbnail_id($applicant_id) : 0;
                    $profile_picture_url = $profile_picture_id ? wp_get_attachment_url($profile_picture_id) : '';
                    $first_letter = strtoupper(substr($current_user->display_name, 0, 1));
                    ?>
                    <div class="cn-user-avatar">
                        <?php if ($profile_picture_url): ?>
                            <img src="<?php echo esc_url($profile_picture_url); ?>"
                                alt="<?php echo esc_attr($current_user->display_name); ?>" class="cn-avatar-img">
                        <?php else: ?>
                            <div class="cn-avatar-fallback"><?php echo esc_html($first_letter); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cn-user-info">
                        <h1><?php echo esc_html__('Welcome back,', 'careernest'); ?>
                            <?php echo esc_html($current_user->display_name); ?>!</h1>
                        <?php if ($prof_title): ?>
                            <p class="cn-user-title"><?php echo esc_html($prof_title); ?></p>
                        <?php endif; ?>
                        <?php if ($location): ?>
                            <p class="cn-user-location">📍 <?php echo esc_html($location); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cn-header-actions">
                    <?php if ($applicant_id): ?>
                        <a href="<?php echo esc_url(get_permalink($applicant_id)); ?>" target="_blank"
                            class="cn-btn cn-btn-secondary">
                            <?php echo esc_html__('View Public Profile', 'careernest'); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="cn-btn cn-btn-primary" id="cn-toggle-edit">
                        <span class="cn-edit-text">Edit Profile</span>
                        <span class="cn-cancel-text" style="display: none;">Cancel Edit</span>
                    </button>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="cn-btn cn-btn-outline">Logout</a>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="cn-dashboard-stats">
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($total_applications); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Total Applications', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number">
                    <?php echo esc_html($status_counts['new'] + $status_counts['interviewed']); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Active Applications', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($status_counts['interviewed']); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Interviews', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($status_counts['hired']); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Offers/Hired', 'careernest'); ?></div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($profile_updated): ?>
            <div class="cn-profile-success">
                <p><?php echo esc_html($profile_success); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile_errors)): ?>
            <div class="cn-profile-errors">
                <ul>
                    <?php foreach ($profile_errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Dashboard Content -->
        <div class="cn-dashboard-content">
            <!-- Left Column -->
            <div class="cn-dashboard-main">
                <!-- Dashboard Tabs -->
                <div class="cn-dashboard-tabs">
                    <button class="cn-tab-btn cn-tab-active" data-tab="applications">
                        <?php echo esc_html__('Applications', 'careernest'); ?>
                        <span class="cn-tab-count"><?php echo esc_html($total_applications); ?></span>
                    </button>
                    <button class="cn-tab-btn" data-tab="bookmarks">
                        <?php
                        $bookmarked_jobs = get_user_meta($current_user->ID, '_bookmarked_jobs', true);
                        $bookmarked_jobs = is_array($bookmarked_jobs) ? $bookmarked_jobs : [];
                        $bookmarks_count = count($bookmarked_jobs);
                        echo esc_html__('Bookmarks', 'careernest');
                        ?>
                        <span class="cn-tab-count"><?php echo esc_html($bookmarks_count); ?></span>
                    </button>
                </div>

                <!-- Applications Tab Content -->
                <div class="cn-tab-content cn-tab-content-active" data-tab-content="applications">
                    <!-- Recent Applications -->
                    <div class="cn-dashboard-section">
                        <div class="cn-section-header">
                            <h2><?php echo esc_html__('Your Applications', 'careernest'); ?></h2>
                            <?php
                            $pages = get_option('careernest_pages', []);
                            $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                            if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'):
                            ?>
                                <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>"
                                    class="cn-btn cn-btn-primary">Browse Jobs</a>
                            <?php endif; ?>
                        </div>

                        <?php if ($applications_query->have_posts()): ?>
                            <div class="cn-applications-list">
                                <?php while ($applications_query->have_posts()): $applications_query->the_post();
                                    $app_id = get_the_ID();
                                    $job_id = get_post_meta($app_id, '_job_id', true);
                                    $app_status = get_post_meta($app_id, '_app_status', true) ?: 'new';
                                    $app_date = get_post_meta($app_id, '_application_date', true);
                                    $resume_id = get_post_meta($app_id, '_resume_id', true);

                                    $job_title = $job_id ? get_the_title($job_id) : 'Unknown Job';
                                    $employer_id = $job_id ? get_post_meta($job_id, '_employer_id', true) : 0;
                                    $company_name = $employer_id ? get_the_title($employer_id) : '';

                                    $status_labels = [
                                        'new' => 'New',
                                        'interviewed' => 'Interviewed',
                                        'offer_extended' => 'Offer Extended',
                                        'hired' => 'Hired',
                                        'rejected' => 'Rejected',
                                        'archived' => 'Archived',
                                        'withdrawn' => 'Withdrawn'
                                    ];

                                    $status_colors = [
                                        'new' => '#0073aa',
                                        'interviewed' => '#f39c12',
                                        'offer_extended' => '#27ae60',
                                        'hired' => '#10B981',
                                        'rejected' => '#e74c3c',
                                        'archived' => '#95a5a6',
                                        'withdrawn' => '#6c757d'
                                    ];
                                ?>
                                    <div class="cn-application-card">
                                        <div class="cn-app-header">
                                            <div class="cn-app-job-info">
                                                <h3>
                                                    <?php if ($job_id && get_post_status($job_id) === 'publish'): ?>
                                                        <a
                                                            href="<?php echo esc_url(get_permalink($job_id)); ?>"><?php echo esc_html($job_title); ?></a>
                                                    <?php else: ?>
                                                        <?php echo esc_html($job_title); ?>
                                                    <?php endif; ?>
                                                </h3>
                                                <?php if ($company_name): ?>
                                                    <p class="cn-app-company"><?php echo esc_html($company_name); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="cn-app-status">
                                                <span class="cn-status-badge"
                                                    style="background-color: <?php echo esc_attr($status_colors[$app_status] ?? '#666'); ?>">
                                                    <?php echo esc_html($status_labels[$app_status] ?? 'Unknown'); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="cn-app-meta">
                                            <span class="cn-app-date">Applied:
                                                <?php echo esc_html($app_date ? date('F j, Y', strtotime($app_date)) : get_the_date('F j, Y', $app_id)); ?></span>
                                            <?php if ($resume_id): ?>
                                                <a href="<?php echo esc_url(wp_get_attachment_url($resume_id)); ?>" target="_blank"
                                                    class="cn-app-resume">📄 View Resume</a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="cn-app-actions">
                                            <?php if (current_user_can('edit_post', $app_id)): ?>
                                                <a href="<?php echo esc_url(get_edit_post_link($app_id)); ?>"
                                                    class="cn-btn cn-btn-small cn-btn-outline">View Details</a>
                                            <?php endif; ?>
                                            <?php
                                            // Allow withdrawal for applications that haven't been hired or rejected
                                            if (!in_array($app_status, ['hired', 'rejected', 'withdrawn'])):
                                            ?>
                                                <button type="button" class="cn-btn cn-btn-small cn-btn-danger cn-withdraw-btn"
                                                    data-application-id="<?php echo esc_attr($app_id); ?>"
                                                    data-job-title="<?php echo esc_attr($job_title); ?>">
                                                    Withdraw
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php wp_reset_postdata(); ?>
                        <?php else: ?>
                            <div class="cn-empty-state">
                                <div class="cn-empty-icon">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 6L9 17L4 12" stroke="#ccc" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <h3><?php echo esc_html__('No Applications Yet', 'careernest'); ?></h3>
                                <p><?php echo esc_html__('You haven\'t applied for any jobs yet. Start browsing available positions!', 'careernest'); ?>
                                </p>
                                <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                                    <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>"
                                        class="cn-btn cn-btn-primary">Browse Jobs</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- End Applications Tab Content -->

                <!-- Bookmarks Tab Content -->
                <div class="cn-tab-content" data-tab-content="bookmarks" style="display: none;">
                    <div class="cn-dashboard-section">
                        <div class="cn-section-header">
                            <h2><?php echo esc_html__('Bookmarked Jobs', 'careernest'); ?></h2>
                            <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                                <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>"
                                    class="cn-btn cn-btn-primary">Browse Jobs</a>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Get user's bookmarked jobs
                        $bookmarked_jobs = get_user_meta($current_user->ID, '_bookmarked_jobs', true);
                        $bookmarked_jobs = is_array($bookmarked_jobs) ? $bookmarked_jobs : [];

                        if (!empty($bookmarked_jobs)):
                            // Query bookmarked jobs
                            $bookmarks_query = new WP_Query([
                                'post_type' => 'job_listing',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'post__in' => $bookmarked_jobs,
                                'orderby' => 'post__in', // Maintain bookmark order
                            ]);

                            if ($bookmarks_query->have_posts()):
                        ?>
                                <div class="cn-bookmarks-list">
                                    <?php while ($bookmarks_query->have_posts()): $bookmarks_query->the_post();
                                        $job_id = get_the_ID();
                                        $employer_id = get_post_meta($job_id, '_employer_id', true);
                                        $company_name = $employer_id ? get_the_title($employer_id) : '';
                                        $job_location = get_post_meta($job_id, '_job_location', true);
                                        $job_type = get_post_meta($job_id, '_job_type', true);
                                        $closing_date = get_post_meta($job_id, '_closing_date', true);

                                        // Check if user has already applied to this job
                                        $application_check = new WP_Query([
                                            'post_type' => 'job_application',
                                            'post_status' => 'publish',
                                            'posts_per_page' => 1,
                                            'meta_query' => [
                                                'relation' => 'AND',
                                                [
                                                    'key' => '_job_id',
                                                    'value' => $job_id,
                                                    'compare' => '='
                                                ],
                                                [
                                                    'key' => '_user_id',
                                                    'value' => $current_user->ID,
                                                    'compare' => '='
                                                ]
                                            ]
                                        ]);
                                        $has_applied = $application_check->have_posts();
                                        if ($has_applied) {
                                            $application_post = $application_check->posts[0];
                                            $app_status = get_post_meta($application_post->ID, '_app_status', true) ?: 'new';
                                        }
                                        wp_reset_postdata();

                                        // Calculate days until closing
                                        $days_until_closing = '';
                                        if ($closing_date) {
                                            $today = new DateTime();
                                            $closing = new DateTime($closing_date);
                                            $diff = $today->diff($closing);
                                            if ($diff->invert) {
                                                $days_until_closing = 'Expired';
                                            } else {
                                                $days_until_closing = $diff->days . ' days left';
                                            }
                                        }
                                    ?>
                                        <div class="cn-bookmark-card" data-job-id="<?php echo esc_attr($job_id); ?>">
                                            <div class="cn-bookmark-header">
                                                <div class="cn-bookmark-job-info">
                                                    <h3>
                                                        <a href="<?php echo esc_url(get_permalink($job_id)); ?>">
                                                            <?php echo esc_html(get_the_title($job_id)); ?>
                                                        </a>
                                                    </h3>
                                                    <?php if ($company_name): ?>
                                                        <p class="cn-bookmark-company">
                                                            <?php echo esc_html($company_name); ?>
                                                            <?php if ($job_type): ?>
                                                                <span class="cn-bookmark-type">|
                                                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $job_type))); ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($has_applied): ?>
                                                    <div class="cn-bookmark-status">
                                                        <span class="cn-applied-badge">✓ Applied</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="cn-bookmark-meta">
                                                <?php if ($job_location): ?>
                                                    <span class="cn-bookmark-location">📍 <?php echo esc_html($job_location); ?></span>
                                                <?php endif; ?>
                                                <?php if ($closing_date): ?>
                                                    <span class="cn-bookmark-expiry">
                                                        <?php echo esc_html($days_until_closing); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="cn-bookmark-actions">
                                                <?php if ($has_applied): ?>
                                                    <span class="cn-bookmark-app-status">
                                                        Status: <strong><?php
                                                                        $status_labels = [
                                                                            'new' => 'Under Review',
                                                                            'interviewed' => 'Interviewed',
                                                                            'offer_extended' => 'Offer Extended',
                                                                            'hired' => 'Hired',
                                                                            'rejected' => 'Not Selected',
                                                                            'archived' => 'Archived',
                                                                            'withdrawn' => 'Withdrawn'
                                                                        ];
                                                                        echo esc_html($status_labels[$app_status] ?? 'Unknown');
                                                                        ?></strong>
                                                    </span>
                                                <?php else: ?>
                                                    <a href="<?php echo esc_url(get_permalink($job_id)); ?>"
                                                        class="cn-btn cn-btn-small cn-btn-primary">
                                                        Apply Now
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button"
                                                    class="cn-btn cn-btn-small cn-btn-outline cn-remove-bookmark-btn"
                                                    data-job-id="<?php echo esc_attr($job_id); ?>"
                                                    data-job-title="<?php echo esc_attr(get_the_title($job_id)); ?>">
                                                    Remove Bookmark
                                                </button>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php wp_reset_postdata(); ?>
                            <?php else: ?>
                                <div class="cn-empty-state">
                                    <div class="cn-empty-icon">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" stroke="#ccc"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <h3><?php echo esc_html__('No Bookmarked Jobs', 'careernest'); ?></h3>
                                    <p><?php echo esc_html__('Jobs you bookmark will appear here for easy access.', 'careernest'); ?>
                                    </p>
                                    <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                                        <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>"
                                            class="cn-btn cn-btn-primary">Browse Jobs</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="cn-empty-state">
                                <div class="cn-empty-icon">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" stroke="#ccc"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <h3><?php echo esc_html__('No Bookmarked Jobs', 'careernest'); ?></h3>
                                <p><?php echo esc_html__('Jobs you bookmark will appear here for easy access.', 'careernest'); ?>
                                </p>
                                <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                                    <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>"
                                        class="cn-btn cn-btn-primary">Browse Jobs</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- End Bookmarks Tab Content -->

                <!-- Profile Sections -->
                <!-- Personal Summary -->
                <?php if ($personal_summary): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Personal Summary', 'careernest'); ?></h3>
                        <div class="cn-summary-content">
                            <?php echo wp_kses_post(wpautop($personal_summary)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Work Experience -->
                <?php if ($experience && !empty($experience)):
                    // Sort experience by start date (most recent first)
                    usort($experience, function ($a, $b) {
                        $date_a = isset($a['start_date']) ? $a['start_date'] : '';
                        $date_b = isset($b['start_date']) ? $b['start_date'] : '';
                        // Sort in descending order (newest first)
                        return strcmp($date_b, $date_a);
                    });
                ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Work Experience', 'careernest'); ?></h3>
                        <div class="cn-experience-list">
                            <?php foreach (array_slice($experience, 0, 5) as $exp):
                                $company = isset($exp['company']) ? $exp['company'] : '';
                                $title = isset($exp['title']) ? $exp['title'] : '';
                                $start_date = isset($exp['start_date']) ? $exp['start_date'] : '';
                                $end_date = isset($exp['end_date']) ? $exp['end_date'] : '';
                                $current = isset($exp['current']) ? $exp['current'] : false;
                                $description = isset($exp['description']) ? $exp['description'] : '';

                                $date_range = '';
                                if ($start_date) {
                                    $date_range = date('M Y', strtotime($start_date));
                                    if ($current) {
                                        $date_range .= ' - Present';
                                    } elseif ($end_date) {
                                        $date_range .= ' - ' . date('M Y', strtotime($end_date));
                                    }
                                }
                            ?>
                                <div class="cn-experience-item">
                                    <h4><?php echo esc_html($title); ?></h4>
                                    <p class="cn-exp-company"><?php echo esc_html($company); ?></p>
                                    <?php if ($date_range): ?>
                                        <p class="cn-exp-date"><?php echo esc_html($date_range); ?></p>
                                    <?php endif; ?>
                                    <?php if ($description): ?>
                                        <p class="cn-exp-description"><?php echo esc_html($description); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($experience) > 5): ?>
                                <p class="cn-more-items">+<?php echo count($experience) - 5; ?> more positions</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Education -->
                <?php if ($education && !empty($education)):
                    // Sort education by end date (most recent first)
                    usort($education, function ($a, $b) {
                        $date_a = isset($a['end_date']) ? $a['end_date'] : '';
                        $date_b = isset($b['end_date']) ? $b['end_date'] : '';
                        // Sort in descending order (newest first)
                        return strcmp($date_b, $date_a);
                    });
                ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Education', 'careernest'); ?></h3>
                        <div class="cn-education-list">
                            <?php foreach (array_slice($education, 0, 5) as $edu):
                                $institution = isset($edu['institution']) ? $edu['institution'] : '';
                                $certification = isset($edu['certification']) ? $edu['certification'] : '';
                                $end_date = isset($edu['end_date']) ? $edu['end_date'] : '';
                                $complete = isset($edu['complete']) ? $edu['complete'] : false;

                                $date_display = '';
                                if ($end_date) {
                                    $date_display = date('Y', strtotime($end_date));
                                }
                            ?>
                                <div class="cn-education-item">
                                    <h4><?php echo esc_html($certification); ?></h4>
                                    <p class="cn-edu-institution"><?php echo esc_html($institution); ?></p>
                                    <?php if ($date_display): ?>
                                        <p class="cn-edu-date"><?php echo esc_html($date_display); ?></p>
                                    <?php endif; ?>
                                    <?php if (!$complete): ?>
                                        <p class="cn-edu-status">In Progress</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($education) > 5): ?>
                                <p class="cn-more-items">+<?php echo count($education) - 5; ?> more qualifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Licenses & Certifications -->
                <?php if ($licenses && !empty($licenses)): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Licenses & Certifications', 'careernest'); ?></h3>
                        <div class="cn-licenses-list">
                            <?php foreach (array_slice($licenses, 0, 5) as $license):
                                $name = isset($license['name']) ? $license['name'] : '';
                                $issuer = isset($license['issuer']) ? $license['issuer'] : '';
                                $expiry_date = isset($license['expiry_date']) ? $license['expiry_date'] : '';
                                $credential_id = isset($license['credential_id']) ? $license['credential_id'] : '';

                                $expiry_display = '';
                                if ($expiry_date) {
                                    $expiry_display = 'Expires: ' . date('M Y', strtotime($expiry_date));
                                }
                            ?>
                                <div class="cn-license-item">
                                    <h4><?php echo esc_html($name); ?></h4>
                                    <p class="cn-license-issuer"><?php echo esc_html($issuer); ?></p>
                                    <?php if ($expiry_display): ?>
                                        <p class="cn-license-expiry"><?php echo esc_html($expiry_display); ?></p>
                                    <?php endif; ?>
                                    <?php if ($credential_id): ?>
                                        <p class="cn-license-credential">ID: <?php echo esc_html($credential_id); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($licenses) > 5): ?>
                                <p class="cn-more-items">+<?php echo count($licenses) - 5; ?> more certifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if ($skills && is_array($skills) && !empty($skills)): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Your Skills', 'careernest'); ?></h3>
                        <div class="cn-skills-list">
                            <?php foreach (array_slice($skills, 0, 15) as $skill): ?>
                                <span class="cn-skill-tag"><?php echo esc_html($skill); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($skills) > 15): ?>
                                <span class="cn-skill-more">+<?php echo count($skills) - 15; ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Profile Edit Form (Hidden by default) -->
                <div class="cn-profile-edit-form" id="cn-profile-edit-form" style="display: none;">
                    <form method="post" class="cn-profile-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('cn_update_profile', 'cn_update_profile_nonce'); ?>

                        <!-- Profile Picture Section -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Profile Picture', 'careernest'); ?></h3>

                            <div class="cn-profile-picture-upload">
                                <?php
                                $current_picture_id = $applicant_id ? get_post_thumbnail_id($applicant_id) : 0;
                                $current_picture_url = $current_picture_id ? wp_get_attachment_url($current_picture_id) : '';
                                $avatar_letter = strtoupper(substr($current_user->display_name, 0, 1));
                                ?>
                                <div class="cn-picture-preview">
                                    <?php if ($current_picture_url): ?>
                                        <img src="<?php echo esc_url($current_picture_url); ?>" alt="Profile Picture"
                                            class="cn-preview-img">
                                    <?php else: ?>
                                        <div class="cn-preview-avatar"><?php echo esc_html($avatar_letter); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="cn-picture-upload-field">
                                    <label
                                        for="profile_picture"><?php echo esc_html__('Upload New Picture', 'careernest'); ?></label>
                                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                                        class="cn-file-input">
                                    <p class="cn-field-help">
                                        <?php echo esc_html__('JPG, PNG, or GIF. Max 5MB.', 'careernest'); ?></p>
                                    <?php if ($current_picture_id): ?>
                                        <label class="cn-checkbox-label" style="margin-top: 0.5rem;">
                                            <input type="checkbox" name="remove_profile_picture" value="1"
                                                class="cn-checkbox">
                                            <span><?php echo esc_html__('Remove current picture', 'careernest'); ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Profile Fields -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Basic Information', 'careernest'); ?></h3>

                            <div class="cn-form-field">
                                <label for="full_name"><?php echo esc_html__('Full Name', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <div class="cn-input-with-icon">
                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <input type="text" id="full_name" name="full_name"
                                        value="<?php echo esc_attr($current_user->display_name); ?>" required
                                        class="cn-input cn-input-with-icon-field">
                                </div>
                            </div>

                            <div class="cn-form-field">
                                <label
                                    for="professional_title"><?php echo esc_html__('Professional Title', 'careernest'); ?></label>
                                <div class="cn-input-with-icon">
                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <input type="text" id="professional_title" name="professional_title"
                                        value="<?php echo esc_attr($prof_title); ?>"
                                        class="cn-input cn-input-with-icon-field"
                                        placeholder="e.g., Senior Software Engineer">
                                </div>
                            </div>

                            <div class="cn-form-field">
                                <label for="phone"><?php echo esc_html__('Phone Number', 'careernest'); ?></label>
                                <div class="cn-input-with-icon">
                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>"
                                        class="cn-input cn-input-with-icon-field">
                                </div>
                            </div>

                            <div class="cn-form-field">
                                <label for="location"><?php echo esc_html__('Location', 'careernest'); ?></label>
                                <div class="cn-input-with-icon">
                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 20 21" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                        <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor"
                                            stroke-width="1.5" />
                                    </svg>
                                    <input type="text" id="location" name="location"
                                        value="<?php echo esc_attr($location); ?>"
                                        class="cn-input cn-input-with-icon-field" placeholder="e.g., Melbourne, VIC">
                                </div>
                            </div>

                            <div class="cn-form-field">
                                <label
                                    for="right_to_work"><?php echo esc_html__('Right to Work', 'careernest'); ?></label>
                                <select id="right_to_work" name="right_to_work" class="cn-input">
                                    <option value="foreign"
                                        <?php selected(get_post_meta($applicant_id, '_right_to_work', true), 'foreign'); ?>>
                                        <?php echo esc_html__('Foreign Citizen', 'careernest'); ?></option>
                                    <option value="australian"
                                        <?php selected(get_post_meta($applicant_id, '_right_to_work', true), 'australian'); ?>>
                                        <?php echo esc_html__('Australian Citizen', 'careernest'); ?></option>
                                </select>
                            </div>

                            <div class="cn-form-field">
                                <label><?php echo esc_html__('Work Preferences', 'careernest'); ?></label>
                                <div class="cn-checkbox-group">
                                    <?php
                                    $work_type_options = [
                                        'full_time' => 'Full-time',
                                        'part_time' => 'Part-time',
                                        'contract' => 'Contract',
                                        'temporary' => 'Temporary',
                                        'internship' => 'Internship',
                                        'remote' => 'Remote',
                                        'on_site' => 'On-site',
                                        'hybrid' => 'Hybrid'
                                    ];
                                    foreach ($work_type_options as $value => $label):
                                    ?>
                                        <label class="cn-checkbox-label">
                                            <input type="checkbox" name="work_types[]"
                                                value="<?php echo esc_attr($value); ?>"
                                                <?php checked(in_array($value, $work_types)); ?> class="cn-checkbox">
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="cn-form-field">
                                <label for="skills_input"><?php echo esc_html__('Skills', 'careernest'); ?></label>
                                <input type="text" id="skills_input" name="skills_input"
                                    value="<?php echo esc_attr(is_array($skills) ? implode(', ', $skills) : ''); ?>"
                                    class="cn-input" placeholder="e.g., PHP, JavaScript, Project Management">
                                <p class="cn-field-help">
                                    <?php echo esc_html__('Separate skills with commas', 'careernest'); ?></p>
                            </div>

                            <div class="cn-form-field">
                                <label class="cn-checkbox-label">
                                    <input type="checkbox" name="available_for_work" value="1"
                                        <?php checked($available_for_work); ?> class="cn-checkbox">
                                    <span><?php echo esc_html__('Available for Work', 'careernest'); ?></span>
                                </label>
                            </div>
                        </div>

                        <!-- Personal Summary Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Personal Summary', 'careernest'); ?></h3>
                            <div class="cn-form-field">
                                <label
                                    for="personal_summary"><?php echo esc_html__('Personal Summary', 'careernest'); ?></label>
                                <p class="cn-field-help">
                                    <?php echo esc_html__('Provide a brief summary about yourself and your career goals', 'careernest'); ?>
                                </p>
                                <?php
                                wp_editor($personal_summary, 'personal_summary', [
                                    'textarea_name' => 'personal_summary',
                                    'textarea_rows' => 8,
                                    'media_buttons' => false,
                                    'teeny' => true,
                                    'quicktags' => true,
                                ]);
                                ?>
                            </div>
                        </div>

                        <!-- LinkedIn Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('LinkedIn Profile', 'careernest'); ?></h3>
                            <div class="cn-form-field">
                                <label
                                    for="linkedin_url"><?php echo esc_html__('LinkedIn URL', 'careernest'); ?></label>
                                <div class="cn-input-with-icon">
                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <input type="url" id="linkedin_url" name="linkedin_url"
                                        value="<?php echo esc_attr($linkedin_url); ?>"
                                        class="cn-input cn-input-with-icon-field"
                                        placeholder="https://linkedin.com/in/yourprofile">
                                </div>
                            </div>
                        </div>

                        <!-- Education Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Education', 'careernest'); ?></h3>
                            <div id="cn-education-fields">
                                <?php if (!empty($education)): ?>
                                    <?php foreach ($education as $index => $edu): ?>
                                        <div class="cn-repeater-item" data-index="<?php echo $index; ?>">
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Institution', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="education[<?php echo $index; ?>][institution]"
                                                        value="<?php echo esc_attr($edu['institution'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Degree/Certification', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M22 10v6M2 10l10-5 10 5-10 5z M6 12v5c0 1 2 3 6 3s6-2 6-3v-5"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="education[<?php echo $index; ?>][certification]"
                                                        value="<?php echo esc_attr($edu['certification'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Completion Date', 'careernest'); ?></label>
                                                <input type="month" name="education[<?php echo $index; ?>][end_date]"
                                                    value="<?php echo esc_attr($edu['end_date'] ?? ''); ?>" class="cn-input">
                                            </div>
                                            <div class="cn-form-field">
                                                <label class="cn-checkbox-label">
                                                    <input type="checkbox" name="education[<?php echo $index; ?>][complete]"
                                                        value="1" <?php checked($edu['complete'] ?? false); ?>
                                                        class="cn-checkbox">
                                                    <span><?php echo esc_html__('Completed', 'careernest'); ?></span>
                                                </label>
                                            </div>
                                            <button type="button"
                                                class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="cn-repeater-item" data-index="0">
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Institution', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="education[0][institution]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Degree/Certification', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z M6 12v5c0 1 2 3 6 3s6-2 6-3v-5"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="education[0][certification]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Completion Date', 'careernest'); ?></label>
                                            <input type="month" name="education[0][end_date]" class="cn-input">
                                        </div>
                                        <div class="cn-form-field">
                                            <label class="cn-checkbox-label">
                                                <input type="checkbox" name="education[0][complete]" value="1"
                                                    class="cn-checkbox">
                                                <span><?php echo esc_html__('Completed', 'careernest'); ?></span>
                                            </label>
                                        </div>
                                        <button type="button"
                                            class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-outline"
                                id="cn-add-education"><?php echo esc_html__('Add Education', 'careernest'); ?></button>
                        </div>

                        <!-- Work Experience Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Work Experience', 'careernest'); ?></h3>
                            <div id="cn-experience-fields">
                                <?php if (!empty($experience)): ?>
                                    <?php foreach ($experience as $index => $exp): ?>
                                        <div class="cn-repeater-item" data-index="<?php echo $index; ?>">
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Company', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="experience[<?php echo $index; ?>][company]"
                                                        value="<?php echo esc_attr($exp['company'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Job Title', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="experience[<?php echo $index; ?>][title]"
                                                        value="<?php echo esc_attr($exp['title'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Start Date', 'careernest'); ?></label>
                                                <input type="month" name="experience[<?php echo $index; ?>][start_date]"
                                                    value="<?php echo esc_attr($exp['start_date'] ?? ''); ?>" class="cn-input">
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('End Date', 'careernest'); ?></label>
                                                <input type="month" name="experience[<?php echo $index; ?>][end_date]"
                                                    value="<?php echo esc_attr($exp['end_date'] ?? ''); ?>"
                                                    class="cn-input cn-end-date">
                                            </div>
                                            <div class="cn-form-field">
                                                <label class="cn-checkbox-label">
                                                    <input type="checkbox" name="experience[<?php echo $index; ?>][current]"
                                                        value="1" <?php checked($exp['current'] ?? false); ?>
                                                        class="cn-checkbox cn-current-job">
                                                    <span><?php echo esc_html__('Current Position', 'careernest'); ?></span>
                                                </label>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Description', 'careernest'); ?></label>
                                                <textarea name="experience[<?php echo $index; ?>][description]" rows="4"
                                                    class="cn-input"><?php echo esc_textarea($exp['description'] ?? ''); ?></textarea>
                                            </div>
                                            <button type="button"
                                                class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="cn-repeater-item" data-index="0">
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Company', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="experience[0][company]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Job Title', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="experience[0][title]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Start Date', 'careernest'); ?></label>
                                            <input type="month" name="experience[0][start_date]" class="cn-input">
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('End Date', 'careernest'); ?></label>
                                            <input type="month" name="experience[0][end_date]" class="cn-input cn-end-date">
                                        </div>
                                        <div class="cn-form-field">
                                            <label class="cn-checkbox-label">
                                                <input type="checkbox" name="experience[0][current]" value="1"
                                                    class="cn-checkbox cn-current-job">
                                                <span><?php echo esc_html__('Current Position', 'careernest'); ?></span>
                                            </label>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Description', 'careernest'); ?></label>
                                            <textarea name="experience[0][description]" rows="4"
                                                class="cn-input"></textarea>
                                        </div>
                                        <button type="button"
                                            class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-outline"
                                id="cn-add-experience"><?php echo esc_html__('Add Experience', 'careernest'); ?></button>
                        </div>

                        <!-- Licenses Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Licenses & Certifications', 'careernest'); ?></h3>
                            <div id="cn-licenses-fields">
                                <?php if (!empty($licenses)): ?>
                                    <?php foreach ($licenses as $index => $license): ?>
                                        <div class="cn-repeater-item" data-index="<?php echo $index; ?>">
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Name', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M12 18v-6M9 15l3 3 3-3" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="licenses[<?php echo $index; ?>][name]"
                                                        value="<?php echo esc_attr($license['name'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Issuing Organization', 'careernest'); ?></label>
                                                <div class="cn-input-with-icon">
                                                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <input type="text" name="licenses[<?php echo $index; ?>][issuer]"
                                                        value="<?php echo esc_attr($license['issuer'] ?? ''); ?>"
                                                        class="cn-input cn-input-with-icon-field">
                                                </div>
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Issue Date', 'careernest'); ?></label>
                                                <input type="month" name="licenses[<?php echo $index; ?>][issue_date]"
                                                    value="<?php echo esc_attr($license['issue_date'] ?? ''); ?>"
                                                    class="cn-input">
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Expiry Date', 'careernest'); ?></label>
                                                <input type="month" name="licenses[<?php echo $index; ?>][expiry_date]"
                                                    value="<?php echo esc_attr($license['expiry_date'] ?? ''); ?>"
                                                    class="cn-input">
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Credential ID', 'careernest'); ?></label>
                                                <input type="text" name="licenses[<?php echo $index; ?>][credential_id]"
                                                    value="<?php echo esc_attr($license['credential_id'] ?? ''); ?>"
                                                    class="cn-input">
                                            </div>
                                            <button type="button"
                                                class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="cn-repeater-item" data-index="0">
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Name', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12 18v-6M9 15l3 3 3-3" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="licenses[0][name]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Issuing Organization', 'careernest'); ?></label>
                                            <div class="cn-input-with-icon">
                                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <input type="text" name="licenses[0][issuer]"
                                                    class="cn-input cn-input-with-icon-field">
                                            </div>
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Issue Date', 'careernest'); ?></label>
                                            <input type="month" name="licenses[0][issue_date]" class="cn-input">
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Expiry Date', 'careernest'); ?></label>
                                            <input type="month" name="licenses[0][expiry_date]" class="cn-input">
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Credential ID', 'careernest'); ?></label>
                                            <input type="text" name="licenses[0][credential_id]" class="cn-input">
                                        </div>
                                        <button type="button"
                                            class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-outline"
                                id="cn-add-license"><?php echo esc_html__('Add License/Certification', 'careernest'); ?></button>
                        </div>

                        <!-- Links Edit -->
                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('Websites & Social Profiles', 'careernest'); ?></h3>
                            <div id="cn-links-fields">
                                <?php if (!empty($links)): ?>
                                    <?php foreach ($links as $index => $link): ?>
                                        <div class="cn-repeater-item" data-index="<?php echo $index; ?>">
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('Label', 'careernest'); ?></label>
                                                <input type="text" name="links[<?php echo $index; ?>][label]"
                                                    value="<?php echo esc_attr($link['label'] ?? ''); ?>" class="cn-input"
                                                    placeholder="e.g., Portfolio, GitHub, Twitter">
                                            </div>
                                            <div class="cn-form-field">
                                                <label><?php echo esc_html__('URL', 'careernest'); ?></label>
                                                <input type="url" name="links[<?php echo $index; ?>][url]"
                                                    value="<?php echo esc_attr($link['url'] ?? ''); ?>" class="cn-input"
                                                    placeholder="https://example.com">
                                            </div>
                                            <button type="button"
                                                class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="cn-repeater-item" data-index="0">
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('Label', 'careernest'); ?></label>
                                            <input type="text" name="links[0][label]" class="cn-input"
                                                placeholder="e.g., Portfolio, GitHub, Twitter">
                                        </div>
                                        <div class="cn-form-field">
                                            <label><?php echo esc_html__('URL', 'careernest'); ?></label>
                                            <input type="url" name="links[0][url]" class="cn-input"
                                                placeholder="https://example.com">
                                        </div>
                                        <button type="button"
                                            class="cn-btn cn-btn-small cn-btn-outline cn-remove-item"><?php echo esc_html__('Remove', 'careernest'); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-outline"
                                id="cn-add-link"><?php echo esc_html__('Add Website/Link', 'careernest'); ?></button>
                        </div>

                        <!-- Form Actions -->
                        <div class="cn-form-actions">
                            <button type="button" class="cn-btn cn-btn-outline"
                                id="cn-cancel-edit"><?php echo esc_html__('Cancel', 'careernest'); ?></button>
                            <button type="submit"
                                class="cn-btn cn-btn-primary"><?php echo esc_html__('Save Changes', 'careernest'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="cn-dashboard-sidebar">
                <!-- Profile Completeness -->
                <?php if ($applicant_id): ?>
                    <div class="cn-dashboard-section cn-profile-completeness">
                        <h3><?php echo esc_html__('Profile Strength', 'careernest'); ?></h3>

                        <?php
                        $percentage = $profile_completeness['percentage'];
                        $color = \CareerNest\Profile_Helper::get_completion_color($percentage);
                        $status = \CareerNest\Profile_Helper::get_completion_status($percentage);
                        ?>

                        <div class="cn-completeness-meter">
                            <div class="cn-meter-bar">
                                <div class="cn-meter-fill"
                                    style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($color); ?>;">
                                </div>
                            </div>
                            <div class="cn-meter-text">
                                <span class="cn-percentage"><?php echo esc_html($percentage); ?>%</span>
                                <span class="cn-status"
                                    style="color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($status); ?></span>
                            </div>
                        </div>

                        <?php if ($percentage < 100): ?>
                            <div class="cn-profile-tips">
                                <p class="cn-tips-header"><?php echo esc_html__('Complete your profile:', 'careernest'); ?></p>
                                <ul class="cn-missing-fields">
                                    <?php foreach (array_slice($profile_completeness['missing_fields'], 0, 3) as $field): ?>
                                        <li>• <?php echo esc_html($field); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p class="cn-profile-complete">
                                ✓ <?php echo esc_html__('Your profile is complete!', 'careernest'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Work Preferences -->
                <?php if ($work_types && is_array($work_types) && !empty($work_types)): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Work Preferences', 'careernest'); ?></h3>
                        <div class="cn-work-types">
                            <?php foreach ($work_types as $type): ?>
                                <span
                                    class="cn-work-type-tag"><?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Websites & Social Profiles -->
                <?php if (($links && !empty($links)) || $linkedin_url): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Websites & Social Profiles', 'careernest'); ?></h3>
                        <div class="cn-links-list">
                            <?php if ($linkedin_url): ?>
                                <div class="cn-link-item">
                                    <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer"
                                        class="cn-link-with-icon">
                                        <svg class="cn-link-icon cn-link-linkedin" width="16" height="16" viewBox="0 0 24 24"
                                            fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                        </svg>
                                        <strong>LinkedIn</strong>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php
                            foreach (array_slice($links, 0, 5) as $link):
                                $label = isset($link['label']) ? $link['label'] : '';
                                $url = isset($link['url']) ? $link['url'] : '';

                                if ($url):
                                    // Detect link type based on URL or label
                                    $link_type = 'website'; // default
                                    $label_lower = strtolower($label);
                                    $url_lower = strtolower($url);

                                    if (strpos($url_lower, 'github.com') !== false || strpos($label_lower, 'github') !== false) {
                                        $link_type = 'github';
                                    } elseif (strpos($url_lower, 'twitter.com') !== false || strpos($url_lower, 'x.com') !== false || strpos($label_lower, 'twitter') !== false) {
                                        $link_type = 'twitter';
                                    } elseif (strpos($url_lower, 'facebook.com') !== false || strpos($label_lower, 'facebook') !== false) {
                                        $link_type = 'facebook';
                                    } elseif (strpos($url_lower, 'instagram.com') !== false || strpos($label_lower, 'instagram') !== false) {
                                        $link_type = 'instagram';
                                    } elseif (strpos($label_lower, 'portfolio') !== false || strpos($label_lower, 'website') !== false) {
                                        $link_type = 'portfolio';
                                    }
                            ?>
                                    <div class="cn-link-item">
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"
                                            class="cn-link-with-icon">
                                            <?php if ($link_type === 'github'): ?>
                                                <svg class="cn-link-icon cn-link-github" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                                </svg>
                                            <?php elseif ($link_type === 'twitter'): ?>
                                                <svg class="cn-link-icon cn-link-twitter" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                                </svg>
                                            <?php elseif ($link_type === 'facebook'): ?>
                                                <svg class="cn-link-icon cn-link-facebook" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                </svg>
                                            <?php elseif ($link_type === 'instagram'): ?>
                                                <svg class="cn-link-icon cn-link-instagram" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z" />
                                                </svg>
                                            <?php elseif ($link_type === 'portfolio'): ?>
                                                <svg class="cn-link-icon cn-link-portfolio" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="cn-link-icon cn-link-website" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path
                                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            <?php endif; ?>
                                            <strong><?php echo esc_html($label ?: 'Website'); ?></strong>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (count($links) > 5): ?>
                                <p class="cn-more-items">+<?php echo count($links) - 5; ?> more links</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recommended Jobs -->
                <?php if (!empty($recommended_jobs)): ?>
                    <div class="cn-dashboard-section">
                        <h3><?php echo esc_html__('Recommended Jobs', 'careernest'); ?></h3>
                        <div class="cn-recommended-jobs">
                            <?php foreach ($recommended_jobs as $job):
                                $job_employer_id = get_post_meta($job->ID, '_employer_id', true);
                                $job_company = $job_employer_id ? get_the_title($job_employer_id) : '';
                                $job_location = get_post_meta($job->ID, '_job_location', true);
                            ?>
                                <div class="cn-recommended-job">
                                    <h4><a
                                            href="<?php echo esc_url(get_permalink($job->ID)); ?>"><?php echo esc_html($job->post_title); ?></a>
                                    </h4>
                                    <?php if ($job_company): ?>
                                        <p class="cn-job-company"><?php echo esc_html($job_company); ?></p>
                                    <?php endif; ?>
                                    <?php if ($job_location): ?>
                                        <p class="cn-job-location">📍 <?php echo esc_html($job_location); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                            <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>" class="cn-view-all-jobs">View All
                                Jobs →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
// Add Google Maps autocomplete for location field
$options = get_option('careernest_options', []);
$maps_api_key = isset($options['maps_api_key']) ? trim((string) $options['maps_api_key']) : '';
$has_maps_api = $maps_api_key !== '';

if ($has_maps_api):
?>
    <script>
        // Initialize Google Maps Autocomplete for applicant location
        function initApplicantProfileAutocomplete() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                const input = document.getElementById('location');
                if (input) {
                    const options = {
                        fields: ['formatted_address', 'geometry', 'name']
                    };

                    // Add country restrictions if available
                    <?php
                    $maps_countries = isset($options['maps_countries']) && is_array($options['maps_countries']) ? $options['maps_countries'] : [];
                    if (!empty($maps_countries)) {
                        $maps_countries_lower = array_map('strtolower', $maps_countries);
                        echo 'const countries = ' . json_encode($maps_countries_lower) . ';';
                        echo "\n                    ";
                        echo 'if (countries.length > 0) {';
                        echo "\n                        ";
                        echo 'options.componentRestrictions = { country: countries };';
                        echo "\n                    ";
                        echo '}';
                    }
                    ?>

                    const autocomplete = new google.maps.places.Autocomplete(input, options);

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
            initApplicantProfileAutocomplete();
        } else {
            window.initApplicantProfileAutocomplete = initApplicantProfileAutocomplete;
        }
    </script>

<?php
    // Enqueue Google Maps API
    wp_enqueue_script(
        'google-maps-applicant-profile',
        'https://maps.googleapis.com/maps/api/js?key=' . urlencode($maps_api_key) . '&libraries=places&callback=initApplicantProfileAutocomplete',
        [],
        null,
        true
    );
endif;
?>

<?php
get_footer();

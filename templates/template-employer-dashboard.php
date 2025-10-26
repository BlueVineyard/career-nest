<?php

/**
 * Template: CareerNest — Employer Dashboard
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    if (!wp_verify_nonce($_POST['cn_delete_job_nonce'], 'cn_delete_job')) {
        wp_die(__('Security check failed.', 'careernest'));
    }

    $job_id_to_delete = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
    $current_user = wp_get_current_user();
    $user_employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
    $job_employer_id = (int) get_post_meta($job_id_to_delete, '_employer_id', true);

    if ($job_id_to_delete && $job_employer_id === $user_employer_id) {
        wp_trash_post($job_id_to_delete);
        wp_safe_redirect(remove_query_arg(['action', 'job_id']));
        exit;
    }
}

// Handle job duplication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'duplicate_job') {
    if (!wp_verify_nonce($_POST['cn_duplicate_job_nonce'], 'cn_duplicate_job')) {
        wp_die(__('Security check failed.', 'careernest'));
    }

    $job_id_to_duplicate = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
    $current_user = wp_get_current_user();
    $user_employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
    $job_employer_id = (int) get_post_meta($job_id_to_duplicate, '_employer_id', true);

    if ($job_id_to_duplicate && $job_employer_id === $user_employer_id) {
        // Get original job
        $original_job = get_post($job_id_to_duplicate);

        if ($original_job) {
            // Create duplicate
            $new_job_data = [
                'post_title' => $original_job->post_title . ' (Copy)',
                'post_content' => $original_job->post_content,
                'post_status' => 'draft',
                'post_type' => 'job_listing',
                'post_author' => $current_user->ID,
            ];

            $new_job_id = wp_insert_post($new_job_data);

            if ($new_job_id && !is_wp_error($new_job_id)) {
                // Copy all meta fields
                $meta_fields = [
                    '_employer_id',
                    '_job_location',
                    '_remote_position',
                    '_opening_date',
                    '_closing_date',
                    '_salary_range',
                    '_apply_externally',
                    '_external_apply',
                    '_overview',
                    '_who_we_are',
                    '_what_we_offer',
                    '_responsibilities',
                    '_how_to_apply'
                ];

                foreach ($meta_fields as $meta_key) {
                    $meta_value = get_post_meta($job_id_to_duplicate, $meta_key, true);
                    if ($meta_value !== '') {
                        update_post_meta($new_job_id, $meta_key, $meta_value);
                    }
                }

                // Copy taxonomies
                $taxonomies = ['job_category', 'job_type'];
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_object_terms($job_id_to_duplicate, $taxonomy, ['fields' => 'ids']);
                    if (!is_wp_error($terms) && !empty($terms)) {
                        wp_set_object_terms($new_job_id, $terms, $taxonomy);
                    }
                }

                // Redirect to edit page
                wp_safe_redirect(add_query_arg(['action' => 'edit-job', 'job_id' => $new_job_id], get_permalink()));
                exit;
            }
        }
    }
}

// Handle profile update
$profile_updated = false;
$profile_errors = [];
$profile_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_update_employer_profile_nonce'])) {
    if (!wp_verify_nonce($_POST['cn_update_employer_profile_nonce'], 'cn_update_employer_profile')) {
        $profile_errors[] = 'Security verification failed. Please try again.';
    } else {
        $result = process_employer_profile_update();
        if ($result['success']) {
            $profile_updated = true;
            $profile_success = $result['message'];
        } else {
            $profile_errors = $result['errors'];
        }
    }
}

/**
 * Process personal profile update for employer team member
 */
function process_employer_profile_update()
{
    global $current_user;

    $errors = [];

    // Sanitize form data for personal profile
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $job_title = sanitize_text_field($_POST['job_title'] ?? '');
    $personal_email = sanitize_email($_POST['personal_email'] ?? '');
    $personal_phone = sanitize_text_field($_POST['personal_phone'] ?? '');
    $bio = wp_kses_post($_POST['bio'] ?? '');

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (!empty($personal_email) && !is_email($personal_email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Update user profile
    $user_data = [
        'ID' => $current_user->ID,
        'display_name' => $full_name,
        'first_name' => explode(' ', $full_name)[0],
        'last_name' => substr($full_name, strlen(explode(' ', $full_name)[0]) + 1),
    ];

    // Only update email if it's different and not empty
    if (!empty($personal_email) && $personal_email !== $current_user->user_email) {
        $user_data['user_email'] = $personal_email;
    }

    $result = wp_update_user($user_data);
    if (is_wp_error($result)) {
        return ['success' => false, 'errors' => [$result->get_error_message()]];
    }

    // Update user meta fields
    update_user_meta($current_user->ID, '_job_title', $job_title);
    update_user_meta($current_user->ID, '_personal_phone', $personal_phone);
    update_user_meta($current_user->ID, '_bio', $bio);

    return [
        'success' => true,
        'message' => 'Your personal profile has been updated successfully!'
    ];
}

// Get dashboard settings
$dashboard_settings = get_option('careernest_employer_dashboard', []);
$recent_jobs_count = isset($dashboard_settings['recent_jobs_count']) ? (int) $dashboard_settings['recent_jobs_count'] : 5;
$recent_apps_count = isset($dashboard_settings['recent_apps_count']) ? (int) $dashboard_settings['recent_apps_count'] : 5;
$welcome_message = isset($dashboard_settings['welcome_message']) ? $dashboard_settings['welcome_message'] : '';

// Get current user and employer profile
$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// Verify user has employer_team role
if (!in_array('employer_team', $user_roles, true)) {
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

// Find employer profile - employer team members are linked via user meta _employer_id
$employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
$employer_profile = null;

if ($employer_id) {
    $employer_profile = get_post($employer_id);
    // Verify the employer post exists and is published
    if (!$employer_profile || $employer_profile->post_status !== 'publish' || $employer_profile->post_type !== 'employer') {
        $employer_id = 0;
        $employer_profile = null;
    }
}

// Get employer data
$company_name = $employer_profile ? $employer_profile->post_title : '';
$company_description = $employer_profile ? $employer_profile->post_content : '';
$website = $employer_id ? get_post_meta($employer_id, '_website', true) : '';
$contact_email = $employer_id ? get_post_meta($employer_id, '_contact_email', true) : '';
$phone = $employer_id ? get_post_meta($employer_id, '_phone', true) : '';
$location = $employer_id ? get_post_meta($employer_id, '_location', true) : '';
$employee_count = $employer_id ? get_post_meta($employer_id, '_employee_count', true) : '';

// If no employee count in new field, check the old company_size field
if (!$employee_count) {
    $employee_count = $employer_id ? get_post_meta($employer_id, '_company_size', true) : '';
}

// Get personal profile data for the team member
$personal_job_title = get_user_meta($current_user->ID, '_job_title', true);
$personal_phone = get_user_meta($current_user->ID, '_personal_phone', true);
$personal_bio = get_user_meta($current_user->ID, '_bio', true);

// Check if current user is owner
$is_owner = false;
if ($employer_id) {
    $owner_id = (int) get_post_meta($employer_id, '_user_id', true);
    $is_owner = ($owner_id === $current_user->ID);
}

// Get employer's jobs (including drafts)
$jobs_query = new WP_Query([
    'post_type' => 'job_listing',
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_employer_id',
            'value' => $employer_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get job statistics
$total_jobs = $jobs_query->found_posts;
$active_jobs = 0;
$filled_jobs = 0;
$expired_jobs = 0;

if ($jobs_query->have_posts()) {
    foreach ($jobs_query->posts as $job) {
        $position_filled = get_post_meta($job->ID, '_position_filled', true);
        $closing_date = get_post_meta($job->ID, '_closing_date', true);

        if ($position_filled) {
            $filled_jobs++;
        } elseif ($closing_date && strtotime($closing_date) < current_time('timestamp')) {
            $expired_jobs++;
        } else {
            $active_jobs++;
        }
    }
}

// Get applications for employer's jobs
$job_ids = wp_list_pluck($jobs_query->posts, 'ID');

// Only query applications if there are jobs, otherwise create empty query
if (!empty($job_ids)) {
    $applications_query = new WP_Query([
        'post_type' => 'job_application',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_job_id',
                'value' => $job_ids,
                'compare' => 'IN'
            ]
        ],
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
} else {
    // Empty query - no jobs means no applications
    $applications_query = new WP_Query([
        'post_type' => 'job_application',
        'post__in' => [0], // Force no results
    ]);
}

// Get application statistics
$total_applications = $applications_query->found_posts;
$new_applications = 0;
$reviewed_applications = 0;

if ($applications_query->have_posts()) {
    foreach ($applications_query->posts as $app) {
        $status = get_post_meta($app->ID, '_app_status', true) ?: 'new';
        if ($status === 'new') {
            $new_applications++;
        } elseif (in_array($status, ['interviewed', 'offer_extended', 'hired'])) {
            $reviewed_applications++;
        }
    }
}
?>

<main id="primary" class="site-main">
    <div class="cn-employer-dashboard-container">
        <!-- Dashboard Header -->
        <div class="cn-dashboard-header">
            <div class="cn-header-content">
                <div class="cn-user-info">
                    <h1>
                        <?php echo esc_html__('Employer Dashboard', 'careernest'); ?>
                        <?php if ($is_owner): ?>
                            <span class="cn-owner-badge-header"
                                style="display:inline-block; background:#0073aa; color:white; padding:4px 12px; border-radius:15px; font-size:0.5em; font-weight:500; margin-left:10px; vertical-align:middle;">
                                <?php echo esc_html__('OWNER', 'careernest'); ?>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <?php if ($company_name): ?>
                        <p class="cn-company-name"><?php echo esc_html($company_name); ?></p>
                    <?php endif; ?>
                    <?php if ($location): ?>
                        <p class="cn-company-location">📍 <?php echo esc_html($location); ?></p>
                    <?php endif; ?>
                </div>
                <div class="cn-header-actions">
                    <?php if ($employer_id): ?>
                        <a href="<?php echo esc_url(get_permalink($employer_id)); ?>" target="_blank"
                            class="cn-btn cn-btn-secondary">
                            <?php echo esc_html__('View Public Profile', 'careernest'); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="cn-btn cn-btn-primary" id="cn-toggle-edit">
                        <span class="cn-edit-text">Edit My Profile</span>
                        <span class="cn-cancel-text" style="display: none;">Cancel Edit</span>
                    </button>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="cn-btn cn-btn-outline">Logout</a>
                </div>
            </div>
        </div>

        <!-- Welcome Message -->
        <?php if (!empty($welcome_message)): ?>
            <div class="cn-dashboard-welcome"
                style="margin: 2rem 0; padding: 1.5rem; background: #f0f9ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                <?php echo wp_kses_post(wpautop($welcome_message)); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="cn-dashboard-stats">
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($total_jobs); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Total Jobs', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($active_jobs); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Active Jobs', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($total_applications); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('Total Applications', 'careernest'); ?></div>
            </div>
            <div class="cn-stat-card">
                <div class="cn-stat-number"><?php echo esc_html($new_applications); ?></div>
                <div class="cn-stat-label"><?php echo esc_html__('New Applications', 'careernest'); ?></div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="cn-dashboard-content">
            <!-- Main Content -->
            <div class="cn-dashboard-main">
                <!-- Job Listings -->
                <div class="cn-dashboard-section">
                    <div class="cn-section-header">
                        <h2><?php echo esc_html__('Recent Job Listings', 'careernest'); ?></h2>
                        <a href="<?php echo esc_url(add_query_arg('action', 'manage-jobs', get_permalink())); ?>"
                            class="cn-btn cn-btn-outline">
                            <?php echo esc_html__('View All Jobs', 'careernest'); ?>
                        </a>
                    </div>

                    <?php if ($jobs_query->have_posts()): ?>
                        <div class="cn-jobs-list">
                            <?php
                            $recent_jobs = array_slice($jobs_query->posts, 0, $recent_jobs_count);
                            foreach ($recent_jobs as $job):
                                $jobs_query->the_post();
                                $job_id = get_the_ID();
                                $job_location = get_post_meta($job_id, '_job_location', true);
                                $position_filled = get_post_meta($job_id, '_position_filled', true);
                                $closing_date = get_post_meta($job_id, '_closing_date', true);
                                $opening_date = get_post_meta($job_id, '_opening_date', true);

                                // Get application count for this job
                                $job_applications = new WP_Query([
                                    'post_type' => 'job_application',
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_job_id',
                                            'value' => $job_id,
                                            'compare' => '='
                                        ]
                                    ]
                                ]);
                                $application_count = $job_applications->found_posts;
                                wp_reset_postdata();

                                // Get external application count
                                $external_count = (int) get_post_meta($job_id, '_external_application_count', true);
                                $total_apps = $application_count + $external_count;

                                // Determine job status
                                $current_post_status = get_post_status($job_id);

                                if ($current_post_status === 'draft') {
                                    $job_status = 'draft';
                                    $status_color = '#f39c12';
                                } elseif ($position_filled) {
                                    $job_status = 'filled';
                                    $status_color = '#0073aa';
                                } elseif ($closing_date && strtotime($closing_date) < current_time('timestamp')) {
                                    $job_status = 'expired';
                                    $status_color = '#dc3545';
                                } else {
                                    $job_status = 'active';
                                    $status_color = '#10B981';
                                }
                            ?>
                                <?php
                                // Get who posted this job
                                $posted_by_id = get_post_field('post_author', $job_id);
                                $posted_by_user = get_userdata($posted_by_id);
                                $posted_by_name = $posted_by_user ? $posted_by_user->display_name : 'Unknown';
                                $is_current_user_author = ($posted_by_id == $current_user->ID);
                                ?>
                                <div class="cn-job-card">
                                    <div class="cn-job-header">
                                        <div class="cn-job-info">
                                            <h3>
                                                <a
                                                    href="<?php echo esc_url(get_permalink($job_id)); ?>"><?php echo esc_html(get_the_title($job_id)); ?></a>
                                            </h3>
                                            <?php if ($job_location): ?>
                                                <p class="cn-job-location">📍 <?php echo esc_html($job_location); ?></p>
                                            <?php endif; ?>
                                            <p class="cn-job-author"
                                                style="font-size: 0.85rem; color: #718096; margin: 0.25rem 0 0 0;">
                                                Posted by: <strong
                                                    style="color: #4a5568;"><?php echo $is_current_user_author ? 'You' : esc_html($posted_by_name); ?></strong>
                                            </p>
                                        </div>
                                        <div class="cn-job-status">
                                            <span class="cn-status-badge"
                                                style="background-color: <?php echo esc_attr($status_color); ?>">
                                                <?php echo esc_html(ucfirst($job_status)); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="cn-job-meta">
                                        <span class="cn-job-date">Posted: <?php echo esc_html(get_the_date('F j, Y')); ?></span>
                                        <?php if ($closing_date): ?>
                                            <span class="cn-job-closing">Closes:
                                                <?php echo esc_html(date('F j, Y', strtotime($closing_date))); ?></span>
                                        <?php endif; ?>
                                        <span class="cn-job-applications">
                                            <?php echo esc_html($total_apps); ?>
                                            application<?php echo $total_apps != 1 ? 's' : ''; ?>
                                            <?php if ($external_count > 0): ?>
                                                <span class="cn-external-indicator"
                                                    title="<?php echo esc_attr($external_count . ' external'); ?>">
                                                    (<?php echo esc_html($application_count); ?> internal,
                                                    <?php echo esc_html($external_count); ?> external)
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <div class="cn-job-actions">
                                        <?php if ($current_post_status !== 'draft'): ?>
                                            <a href="<?php echo esc_url(get_permalink($job_id)); ?>"
                                                class="cn-btn cn-btn-small cn-btn-outline">View Job</a>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url(add_query_arg(['action' => 'edit-job', 'job_id' => $job_id], get_permalink())); ?>"
                                            class="cn-btn cn-btn-small <?php echo $current_post_status === 'draft' ? 'cn-btn-primary' : 'cn-btn-outline'; ?>">
                                            <?php echo $current_post_status === 'draft' ? esc_html__('Continue Submission', 'careernest') : esc_html__('Edit', 'careernest'); ?>
                                        </a>
                                        <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-duplicate-job"
                                            data-job-id="<?php echo esc_attr($job_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($job_id)); ?>"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('cn_duplicate_job')); ?>">
                                            Duplicate
                                        </button>
                                        <?php if ($application_count > 0 && $current_post_status !== 'draft'): ?>
                                            <a href="<?php echo esc_url(add_query_arg(['action' => 'view-applications', 'filter_job' => $job_id], get_permalink())); ?>"
                                                class="cn-btn cn-btn-small cn-btn-primary">
                                                View Applications (<?php echo esc_html($application_count); ?>)
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="cn-btn cn-btn-small cn-btn-danger cn-delete-job"
                                            data-job-id="<?php echo esc_attr($job_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($job_id)); ?>"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('cn_delete_job')); ?>">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>

                        <?php if ($jobs_query->found_posts > $recent_jobs_count): ?>
                            <div style="margin-top: 1rem; text-align: center;">
                                <a href="<?php echo esc_url(add_query_arg('action', 'manage-jobs', get_permalink())); ?>"
                                    class="cn-btn cn-btn-primary">
                                    <?php echo esc_html__('View All Jobs', 'careernest'); ?>
                                    (<?php echo esc_html($jobs_query->found_posts); ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="cn-empty-state">
                            <div class="cn-empty-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34" stroke="#ccc"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <polygon points="18,2 22,6 12,16 8,16 8,12 18,2" stroke="#ccc" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h3><?php echo esc_html__('No Job Listings Yet', 'careernest'); ?></h3>
                            <p><?php echo esc_html__('Start by posting your first job listing to attract qualified candidates.', 'careernest'); ?>
                            </p>
                            <a href="<?php echo esc_url(add_query_arg('action', 'add-job', get_permalink())); ?>"
                                class="cn-btn cn-btn-primary">
                                <?php echo esc_html__('Post Your First Job', 'careernest'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Personal Profile Display -->
                <div class="cn-dashboard-section">
                    <h3><?php echo esc_html__('My Profile', 'careernest'); ?></h3>

                    <div class="cn-profile-item">
                        <strong><?php echo esc_html__('Name:', 'careernest'); ?></strong>
                        <span><?php echo esc_html($current_user->display_name); ?></span>
                    </div>

                    <div class="cn-profile-item">
                        <strong><?php echo esc_html__('Email:', 'careernest'); ?></strong>
                        <span><?php echo esc_html($current_user->user_email); ?></span>
                    </div>

                    <?php if ($personal_job_title): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Job Title:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($personal_job_title); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($personal_phone): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Phone:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($personal_phone); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($personal_bio): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Bio:', 'careernest'); ?></strong>
                            <div class="cn-personal-bio"><?php echo wp_kses_post(wpautop($personal_bio)); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Applications -->
                <?php if ($applications_query->have_posts()): ?>
                    <div class="cn-dashboard-section">
                        <div class="cn-section-header">
                            <h2><?php echo esc_html__('Recent Applications', 'careernest'); ?></h2>
                            <a href="<?php echo esc_url(add_query_arg('action', 'view-applications', get_permalink())); ?>"
                                class="cn-btn cn-btn-outline">
                                <?php echo esc_html__('View All Applications', 'careernest'); ?>
                            </a>
                        </div>

                        <div class="cn-applications-list">
                            <?php
                            $recent_applications = array_slice($applications_query->posts, 0, $recent_apps_count);
                            foreach ($recent_applications as $app):
                                $app_id = $app->ID;
                                $job_id = get_post_meta($app_id, '_job_id', true);
                                $applicant_id = get_post_meta($app_id, '_applicant_id', true);
                                $app_status = get_post_meta($app_id, '_app_status', true) ?: 'new';
                                $app_date = get_post_meta($app_id, '_application_date', true);
                                $resume_id = get_post_meta($app_id, '_resume_id', true);

                                $job_title = $job_id ? get_the_title($job_id) : 'Unknown Job';
                                $applicant_name = $applicant_id ? get_the_title($applicant_id) : 'Unknown Applicant';

                                $status_labels = [
                                    'new' => 'New',
                                    'reviewed' => 'Reviewed',
                                    'interviewed' => 'Interviewed',
                                    'offer_extended' => 'Offer Extended',
                                    'hired' => 'Hired',
                                    'rejected' => 'Rejected'
                                ];

                                $status_colors = [
                                    'new' => '#0073aa',
                                    'reviewed' => '#f39c12',
                                    'interviewed' => '#e67e22',
                                    'offer_extended' => '#27ae60',
                                    'hired' => '#10B981',
                                    'rejected' => '#e74c3c'
                                ];
                            ?>
                                <div class="cn-application-card">
                                    <div class="cn-app-header">
                                        <div class="cn-app-info">
                                            <h4><?php echo esc_html($applicant_name); ?></h4>
                                            <p class="cn-app-job">Applied for:
                                                <strong><?php echo esc_html($job_title); ?></strong>
                                            </p>
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
                                        <a href="<?php echo esc_url(add_query_arg(['action' => 'view-applications', 'search' => $applicant_name], get_permalink())); ?>"
                                            class="cn-btn cn-btn-small cn-btn-outline">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

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

                <!-- Personal Profile Edit Form (Hidden by default) -->
                <div class="cn-profile-edit-form" id="cn-profile-edit-form" style="display: none;">
                    <form method="post" class="cn-profile-form">
                        <?php wp_nonce_field('cn_update_employer_profile', 'cn_update_employer_profile_nonce'); ?>

                        <div class="cn-dashboard-section">
                            <h3><?php echo esc_html__('My Profile', 'careernest'); ?></h3>

                            <div class="cn-form-field">
                                <label for="full_name"><?php echo esc_html__('Full Name', 'careernest'); ?> <span
                                        class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?php echo esc_attr($current_user->display_name); ?>" required
                                    class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="job_title"><?php echo esc_html__('Job Title', 'careernest'); ?></label>
                                <input type="text" id="job_title" name="job_title"
                                    value="<?php echo esc_attr($personal_job_title); ?>" class="cn-input"
                                    placeholder="e.g., HR Manager, Recruiter">
                            </div>

                            <div class="cn-form-field">
                                <label
                                    for="personal_email"><?php echo esc_html__('Email Address', 'careernest'); ?></label>
                                <input type="email" id="personal_email" name="personal_email"
                                    value="<?php echo esc_attr($current_user->user_email); ?>" class="cn-input">
                                <p class="cn-field-help">
                                    <?php echo esc_html__('This will update your login email address.', 'careernest'); ?>
                                </p>
                            </div>

                            <div class="cn-form-field">
                                <label
                                    for="personal_phone"><?php echo esc_html__('Phone Number', 'careernest'); ?></label>
                                <input type="tel" id="personal_phone" name="personal_phone"
                                    value="<?php echo esc_attr($personal_phone); ?>" class="cn-input">
                            </div>

                            <div class="cn-form-field">
                                <label for="bio"><?php echo esc_html__('Bio', 'careernest'); ?></label>
                                <textarea id="bio" name="bio" rows="4" class="cn-input"
                                    placeholder="Tell us about yourself and your role..."><?php echo esc_textarea($personal_bio); ?></textarea>
                            </div>

                            <div class="cn-form-actions">
                                <button type="submit"
                                    class="cn-btn cn-btn-primary"><?php echo esc_html__('Save Changes', 'careernest'); ?></button>
                                <button type="button" class="cn-btn cn-btn-outline"
                                    id="cn-cancel-edit"><?php echo esc_html__('Cancel', 'careernest'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="cn-dashboard-sidebar">
                <!-- Company Information -->
                <div class="cn-dashboard-section">
                    <h3><?php echo esc_html__('Company Information', 'careernest'); ?></h3>

                    <?php if ($company_name): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Company:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($company_name); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($website): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Website:', 'careernest'); ?></strong>
                            <a href="<?php echo esc_url($website); ?>" target="_blank"
                                rel="noopener noreferrer"><?php echo esc_html($website); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ($contact_email): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Contact:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($contact_email); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($phone): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Phone:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($phone); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($location): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Location:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($location); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($employee_count): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('Employees:', 'careernest'); ?></strong>
                            <span><?php echo esc_html($employee_count); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($company_description): ?>
                        <div class="cn-profile-item">
                            <strong><?php echo esc_html__('About:', 'careernest'); ?></strong>
                            <div class="cn-company-description"><?php echo wp_kses_post(wpautop($company_description)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="cn-dashboard-section">
                    <h3><?php echo esc_html__('Quick Actions', 'careernest'); ?></h3>
                    <div class="cn-quick-actions">
                        <a href="<?php echo esc_url(add_query_arg('action', 'manage-team', get_permalink())); ?>"
                            class="cn-quick-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <?php echo esc_html__('Manage Team', 'careernest'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('action', 'add-job', get_permalink())); ?>"
                            class="cn-quick-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <?php echo esc_html__('Post New Job', 'careernest'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('action', 'manage-jobs', get_permalink())); ?>"
                            class="cn-quick-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <?php echo esc_html__('Manage Jobs', 'careernest'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('action', 'view-applications', get_permalink())); ?>"
                            class="cn-quick-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <?php echo esc_html__('View Applications', 'careernest'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Modal -->
    <div id="cn-job-modal" class="cn-job-modal" style="display: none;">
        <div class="cn-job-modal-content" id="cn-job-modal-content">
            <div class="cn-job-modal-header">
                <h2 id="cn-job-modal-title">Post New Job</h2>
                <button type="button" class="cn-job-modal-close">&times;</button>
            </div>

            <div id="cn-job-form-errors" class="cn-job-form-errors cn-error" style="display: none;"></div>

            <form id="cn-job-form" class="cn-job-form">
                <!-- Basic Information -->
                <div class="cn-job-form-section">
                    <h3>Basic Information</h3>

                    <div class="cn-form-field">
                        <label for="cn-job-title">Job Title <span class="required">*</span></label>
                        <input type="text" id="cn-job-title" name="job_title" required class="cn-input"
                            placeholder="e.g., Senior Software Engineer">
                    </div>

                    <div class="cn-form-row">
                        <div class="cn-form-field">
                            <label for="cn-job-location">Location</label>
                            <input type="text" id="cn-job-location" name="job_location" class="cn-input"
                                placeholder="e.g., Melbourne, VIC">
                        </div>

                        <div class="cn-form-field">
                            <label>
                                <input type="checkbox" id="cn-job-remote" name="remote_position" value="1">
                                Remote Position
                            </label>
                        </div>
                    </div>

                    <div class="cn-form-row">
                        <div class="cn-form-field">
                            <label for="cn-job-opening-date">Opening Date</label>
                            <input type="date" id="cn-job-opening-date" name="opening_date" class="cn-input">
                        </div>

                        <div class="cn-form-field">
                            <label for="cn-job-closing-date">Closing Date</label>
                            <input type="date" id="cn-job-closing-date" name="closing_date" class="cn-input">
                        </div>
                    </div>

                    <div class="cn-form-field">
                        <label for="cn-job-salary-range">Salary Range</label>
                        <input type="text" id="cn-job-salary-range" name="salary_range" class="cn-input"
                            placeholder="e.g., $80,000 - $120,000 per year">
                    </div>

                    <div class="cn-form-field">
                        <label>
                            <input type="checkbox" id="cn-job-apply-externally" name="apply_externally" value="1">
                            Applications handled externally
                        </label>
                        <div id="cn-external-apply-container" style="display: none; margin-top: 0.5rem;">
                            <input type="text" id="cn-job-external-apply" name="external_apply" class="cn-input"
                                placeholder="External URL or email (e.g., jobs@company.com)">
                        </div>
                    </div>
                </div>

                <!-- Job Details -->
                <div class="cn-job-form-section">
                    <h3>Overview</h3>
                    <p class="cn-field-description">Provide a high-level summary of the role and its impact.</p>
                    <textarea id="cn-job-overview" name="overview" rows="4" class="cn-textarea"></textarea>
                </div>

                <div class="cn-job-form-section">
                    <h3>Who We Are</h3>
                    <p class="cn-field-description">Introduce the company, culture, and mission.</p>
                    <textarea id="cn-job-who-we-are" name="who_we_are" rows="4" class="cn-textarea"></textarea>
                </div>

                <div class="cn-job-form-section">
                    <h3>What We Offer</h3>
                    <p class="cn-field-description">Outline compensation, benefits, growth, and perks.</p>
                    <textarea id="cn-job-what-we-offer" name="what_we_offer" rows="4" class="cn-textarea"></textarea>
                </div>

                <div class="cn-job-form-section">
                    <h3>Key Responsibilities</h3>
                    <p class="cn-field-description">List main responsibilities and expectations.</p>
                    <textarea id="cn-job-responsibilities" name="responsibilities" rows="4"
                        class="cn-textarea"></textarea>
                </div>

                <div class="cn-job-form-section">
                    <h3>How to Apply</h3>
                    <p class="cn-field-description">Explain the application process and required materials.</p>
                    <textarea id="cn-job-how-to-apply" name="how_to_apply" rows="4" class="cn-textarea"></textarea>
                </div>

                <div class="cn-job-modal-footer">
                    <button type="submit" id="cn-job-submit" class="cn-btn cn-btn-primary">Post Job</button>
                    <button type="button" class="cn-btn cn-btn-outline cn-job-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
get_footer();

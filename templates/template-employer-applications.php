<?php

/**
 * Template: CareerNest — Employer Applications Management
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('employer_team', (array) $current_user->roles, true)) {
    wp_die(__('Access denied.', 'careernest'));
}

$employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
if (!$employer_id) {
    wp_die(__('No employer linked.', 'careernest'));
}

// Get dashboard URL
$pages = get_option('careernest_pages', []);
$dashboard_url = isset($pages['employer-dashboard']) ? get_permalink($pages['employer-dashboard']) : home_url();

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
$filter_job = isset($_GET['filter_job']) ? (int) $_GET['filter_job'] : 0;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get all employer's jobs for filter dropdown
$employer_jobs = get_posts([
    'post_type' => 'job_listing',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_employer_id',
            'value' => $employer_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'title',
    'order' => 'ASC'
]);

// Build applications query
$meta_query = [
    [
        'key' => '_job_id',
        'value' => wp_list_pluck($employer_jobs, 'ID'),
        'compare' => 'IN'
    ]
];

// Filter by specific job
if ($filter_job > 0) {
    $meta_query = [
        [
            'key' => '_job_id',
            'value' => $filter_job,
            'compare' => '='
        ]
    ];
}

// Filter by status
if ($filter_status !== 'all') {
    $meta_query[] = [
        'key' => '_app_status',
        'value' => $filter_status,
        'compare' => '='
    ];
}

$applications_query = new WP_Query([
    'post_type' => 'job_application',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => $meta_query,
    's' => $search,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Enqueue custom dropdown assets
wp_enqueue_style('careernest-custom-dropdown', CAREERNEST_URL . 'assets/css/custom-dropdown.css', [], CAREERNEST_VERSION);
wp_enqueue_script('careernest-custom-dropdown', CAREERNEST_URL . 'assets/js/custom-dropdown.js', ['jquery'], CAREERNEST_VERSION, true);

get_header();
?>

<main id="primary" class="site-main cn-applications-page">
    <div class="cn-applications-container">
        <div class="cn-applications-header">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-back-btn">
                <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 7H15M1 7L7 13M1 7L7 1" stroke="#0073aa" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php echo esc_html__('Back to Dashboard', 'careernest'); ?></span>
            </a>
            <h1><?php echo esc_html__('Manage Applications', 'careernest'); ?></h1>
        </div>

        <!-- Filters -->
        <div class="cn-applications-filters">
            <form method="get" class="cn-filter-form">
                <input type="hidden" name="action" value="view-applications">

                <div class="cn-filter-group">
                    <label for="search"><?php echo esc_html__('Search', 'careernest'); ?></label>
                    <input type="text" id="search" name="search" class="cn-input"
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="<?php echo esc_attr__('Search applicant name...', 'careernest'); ?>">
                </div>

                <div class="cn-filter-group">
                    <label for="filter_job"><?php echo esc_html__('Job', 'careernest'); ?></label>
                    <div class="cn-custom-select-wrapper" data-icon="briefcase">
                        <select id="filter_job" name="filter_job" class="cn-input cn-custom-select">
                            <option value="0"><?php echo esc_html__('All Jobs', 'careernest'); ?></option>
                            <?php foreach ($employer_jobs as $job): ?>
                                <option value="<?php echo esc_attr($job->ID); ?>" <?php selected($filter_job, $job->ID); ?>>
                                    <?php echo esc_html($job->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="cn-filter-group">
                    <label for="filter_status"><?php echo esc_html__('Status', 'careernest'); ?></label>
                    <div class="cn-custom-select-wrapper" data-icon="filter">
                        <select id="filter_status" name="filter_status" class="cn-input cn-custom-select">
                            <option value="all" <?php selected($filter_status, 'all'); ?>>
                                <?php echo esc_html__('All Statuses', 'careernest'); ?></option>
                            <option value="new" <?php selected($filter_status, 'new'); ?>>
                                <?php echo esc_html__('New', 'careernest'); ?></option>
                            <option value="reviewed" <?php selected($filter_status, 'reviewed'); ?>>
                                <?php echo esc_html__('Reviewed', 'careernest'); ?></option>
                            <option value="interviewed" <?php selected($filter_status, 'interviewed'); ?>>
                                <?php echo esc_html__('Interviewed', 'careernest'); ?></option>
                            <option value="offer_extended" <?php selected($filter_status, 'offer_extended'); ?>>
                                <?php echo esc_html__('Offer Extended', 'careernest'); ?></option>
                            <option value="hired" <?php selected($filter_status, 'hired'); ?>>
                                <?php echo esc_html__('Hired', 'careernest'); ?></option>
                            <option value="rejected" <?php selected($filter_status, 'rejected'); ?>>
                                <?php echo esc_html__('Rejected', 'careernest'); ?></option>
                            <option value="withdrawn" <?php selected($filter_status, 'withdrawn'); ?>>
                                <?php echo esc_html__('Withdrawn', 'careernest'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="cn-filter-actions">
                    <button type="submit"
                        class="cn-btn cn-btn-primary"><?php echo esc_html__('Filter', 'careernest'); ?></button>
                    <a href="<?php echo esc_url(add_query_arg('action', 'view-applications', get_permalink())); ?>"
                        class="cn-btn cn-btn-outline"><?php echo esc_html__('Clear', 'careernest'); ?></a>
                </div>
            </form>
        </div>

        <!-- Applications List -->
        <?php if ($applications_query->have_posts()): ?>
            <div class="cn-applications-stats">
                <p><?php echo sprintf(esc_html__('Showing %d application(s)', 'careernest'), $applications_query->found_posts); ?>
                </p>
            </div>

            <div class="cn-applications-list">
                <?php
                $status_labels = [
                    'new' => __('New', 'careernest'),
                    'reviewed' => __('Reviewed', 'careernest'),
                    'interviewed' => __('Interviewed', 'careernest'),
                    'offer_extended' => __('Offer Extended', 'careernest'),
                    'hired' => __('Hired', 'careernest'),
                    'rejected' => __('Rejected', 'careernest'),
                    'withdrawn' => __('Withdrawn', 'careernest')
                ];

                $status_colors = [
                    'new' => '#0073aa',
                    'reviewed' => '#f39c12',
                    'interviewed' => '#e67e22',
                    'offer_extended' => '#27ae60',
                    'hired' => '#10B981',
                    'rejected' => '#e74c3c',
                    'withdrawn' => '#6c757d'
                ];

                while ($applications_query->have_posts()): $applications_query->the_post();
                    $app_id = get_the_ID();
                    $job_id = get_post_meta($app_id, '_job_id', true);
                    $applicant_id = get_post_meta($app_id, '_applicant_id', true);
                    $app_status = get_post_meta($app_id, '_app_status', true) ?: 'new';
                    $app_date = get_post_meta($app_id, '_application_date', true);
                    $resume_id = get_post_meta($app_id, '_resume_id', true);
                    $cover_letter = get_post_meta($app_id, '_cover_letter', true);

                    $job_title = $job_id ? get_the_title($job_id) : __('Unknown Job', 'careernest');
                    $applicant_name = $applicant_id ? get_the_title($applicant_id) : get_the_title($app_id);

                    // Get applicant email
                    $applicant_email = '';
                    if ($applicant_id) {
                        $user_id = get_post_meta($applicant_id, '_user_id', true);
                        if ($user_id) {
                            $user = get_user_by('id', $user_id);
                            $applicant_email = $user ? $user->user_email : '';
                        }
                    }
                    if (!$applicant_email) {
                        $applicant_email = get_post_meta($app_id, '_applicant_email', true);
                    }
                ?>
                    <div class="cn-application-item" data-app-id="<?php echo esc_attr($app_id); ?>">
                        <div class="cn-app-main">
                            <div class="cn-app-info">
                                <h3><?php echo esc_html($applicant_name); ?></h3>
                                <p class="cn-app-email"><?php echo esc_html($applicant_email); ?></p>
                                <p class="cn-app-job-title"><?php echo esc_html__('Applied for:', 'careernest'); ?>
                                    <strong><?php echo esc_html($job_title); ?></strong>
                                </p>
                                <p class="cn-app-date"><?php echo esc_html__('Date:', 'careernest'); ?>
                                    <?php echo esc_html($app_date ? date('F j, Y', strtotime($app_date)) : get_the_date('F j, Y', $app_id)); ?>
                                </p>
                            </div>

                            <div class="cn-app-status-section">
                                <span class="cn-status-badge"
                                    style="background-color: <?php echo esc_attr($status_colors[$app_status] ?? '#666'); ?>">
                                    <?php echo esc_html($status_labels[$app_status] ?? 'Unknown'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="cn-app-actions">
                            <?php if ($resume_id): ?>
                                <a href="<?php echo esc_url(wp_get_attachment_url($resume_id)); ?>" target="_blank"
                                    class="cn-btn cn-btn-small cn-btn-outline">
                                    📄 <?php echo esc_html__('View Resume', 'careernest'); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-primary cn-view-details"
                                data-app-id="<?php echo esc_attr($app_id); ?>">
                                <?php echo esc_html__('View Details', 'careernest'); ?>
                            </button>
                            <?php if ($app_status !== 'withdrawn'): ?>
                                <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-change-status"
                                    data-app-id="<?php echo esc_attr($app_id); ?>"
                                    data-current-status="<?php echo esc_attr($app_status); ?>">
                                    <?php echo esc_html__('Change Status', 'careernest'); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Hidden details panel -->
                        <div class="cn-app-details" id="details-<?php echo esc_attr($app_id); ?>" style="display: none;">
                            <?php if ($cover_letter): ?>
                                <div class="cn-detail-section">
                                    <h4><?php echo esc_html__('Cover Letter', 'careernest'); ?></h4>
                                    <div class="cn-cover-letter"><?php echo wp_kses_post(wpautop($cover_letter)); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($applicant_id): ?>
                                <div class="cn-detail-section">
                                    <h4><?php echo esc_html__('Applicant Profile', 'careernest'); ?></h4>
                                    <a href="<?php echo esc_url(get_permalink($applicant_id)); ?>" target="_blank"
                                        class="cn-btn cn-btn-outline">
                                        <?php echo esc_html__('View Full Profile', 'careernest'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="cn-empty-state">
                <div class="cn-empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" stroke="#ccc"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1" stroke="#ccc" stroke-width="2" />
                    </svg>
                </div>
                <h3><?php echo esc_html__('No Applications Found', 'careernest'); ?></h3>
                <p><?php echo esc_html__('No applications match your current filters.', 'careernest'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('action', 'view-applications', get_permalink())); ?>"
                    class="cn-btn cn-btn-primary"><?php echo esc_html__('Clear Filters', 'careernest'); ?></a>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>

    <!-- Status Change Modal -->
    <div id="cn-status-modal" class="cn-modal" style="display: none;">
        <div class="cn-modal-content">
            <div class="cn-modal-header">
                <h3><?php echo esc_html__('Change Application Status', 'careernest'); ?></h3>
                <button type="button" class="cn-modal-close">&times;</button>
            </div>
            <div class="cn-modal-body">
                <form id="cn-status-form">
                    <input type="hidden" id="status-app-id" name="app_id" value="">
                    <div class="cn-form-field">
                        <label for="new-status"><?php echo esc_html__('New Status', 'careernest'); ?></label>
                        <div class="cn-custom-select-wrapper" data-icon="check">
                            <select id="new-status" name="new_status" class="cn-input cn-custom-select" required>
                                <option value="new"><?php echo esc_html__('New', 'careernest'); ?></option>
                                <option value="reviewed"><?php echo esc_html__('Reviewed', 'careernest'); ?></option>
                                <option value="interviewed"><?php echo esc_html__('Interviewed', 'careernest'); ?>
                                </option>
                                <option value="offer_extended"><?php echo esc_html__('Offer Extended', 'careernest'); ?>
                                </option>
                                <option value="hired"><?php echo esc_html__('Hired', 'careernest'); ?></option>
                                <option value="rejected"><?php echo esc_html__('Rejected', 'careernest'); ?></option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="cn-modal-footer">
                <button type="button"
                    class="cn-btn cn-btn-outline cn-modal-close"><?php echo esc_html__('Cancel', 'careernest'); ?></button>
                <button type="button"
                    class="cn-btn cn-btn-primary cn-save-status"><?php echo esc_html__('Save Status', 'careernest'); ?></button>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();

<?php

/**
 * Template: CareerNest — Employer Jobs Management
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
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Build jobs query
$query_args = [
    'post_type' => 'job_listing',
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
];

// Filter by status
if ($filter_status !== 'all') {
    $query_args['post_status'] = $filter_status;
} else {
    $query_args['post_status'] = ['publish', 'draft'];
}

// Add search
if ($search) {
    $query_args['s'] = $search;
}

$jobs_query = new WP_Query($query_args);

// Enqueue custom dropdown assets
wp_enqueue_style('careernest-custom-dropdown', CAREERNEST_URL . 'assets/css/custom-dropdown.css', [], CAREERNEST_VERSION);
wp_enqueue_script('careernest-custom-dropdown', CAREERNEST_URL . 'assets/js/custom-dropdown.js', ['jquery'], CAREERNEST_VERSION, true);

get_header();
?>

<main id="primary" class="site-main cn-jobs-management-page">
    <div class="cn-jobs-management-container">
        <div class="cn-jobs-header">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-back-btn">
                <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 7H15M1 7L7 13M1 7L7 1" stroke="#0073aa" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php echo esc_html__('Back to Dashboard', 'careernest'); ?></span>
            </a>
            <div class="cn-header-actions-row">
                <h1><?php echo esc_html__('Manage Jobs', 'careernest'); ?></h1>
                <a href="<?php echo esc_url(add_query_arg('action', 'add-job', $dashboard_url)); ?>"
                    class="cn-btn cn-btn-primary">
                    <?php echo esc_html__('Post New Job', 'careernest'); ?>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="cn-jobs-filters">
            <form method="get" class="cn-filter-form">
                <input type="hidden" name="action" value="manage-jobs">

                <div class="cn-filter-group">
                    <label for="search"><?php echo esc_html__('Search', 'careernest'); ?></label>
                    <input type="text" id="search" name="search" class="cn-input"
                        value="<?php echo esc_attr($search); ?>"
                        placeholder="<?php echo esc_attr__('Search job title...', 'careernest'); ?>">
                </div>

                <div class="cn-filter-group">
                    <label for="filter_status"><?php echo esc_html__('Status', 'careernest'); ?></label>
                    <div class="cn-custom-select-wrapper" data-icon="filter">
                        <select id="filter_status" name="filter_status" class="cn-input cn-custom-select">
                            <option value="all" <?php selected($filter_status, 'all'); ?>>
                                <?php echo esc_html__('All Statuses', 'careernest'); ?></option>
                            <option value="publish" <?php selected($filter_status, 'publish'); ?>>
                                <?php echo esc_html__('Published', 'careernest'); ?></option>
                            <option value="draft" <?php selected($filter_status, 'draft'); ?>>
                                <?php echo esc_html__('Draft', 'careernest'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="cn-filter-actions">
                    <button type="submit"
                        class="cn-btn cn-btn-primary"><?php echo esc_html__('Filter', 'careernest'); ?></button>
                    <a href="<?php echo esc_url(add_query_arg('action', 'manage-jobs', $dashboard_url)); ?>"
                        class="cn-btn cn-btn-outline"><?php echo esc_html__('Clear', 'careernest'); ?></a>
                </div>
            </form>
        </div>

        <!-- Jobs List -->
        <?php if ($jobs_query->have_posts()): ?>
            <div class="cn-jobs-stats">
                <p><?php echo sprintf(esc_html__('Showing %d job(s)', 'careernest'), $jobs_query->found_posts); ?></p>
            </div>

            <div class="cn-jobs-management-list">
                <?php while ($jobs_query->have_posts()): $jobs_query->the_post();
                    $job_id = get_the_ID();
                    $job_location = get_post_meta($job_id, '_job_location', true);
                    $closing_date = get_post_meta($job_id, '_closing_date', true);
                    $position_filled = get_post_meta($job_id, '_position_filled', true);
                    $current_post_status = get_post_status($job_id);

                    // Get application count
                    $app_count = get_posts([
                        'post_type' => 'job_application',
                        'post_status' => 'publish',
                        'meta_query' => [['key' => '_job_id', 'value' => $job_id, 'compare' => '=']],
                        'fields' => 'ids'
                    ]);
                    $application_count = count($app_count);

                    // Determine status
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
                    <div class="cn-job-item">
                        <div class="cn-job-main">
                            <div class="cn-job-info-section">
                                <h3><?php echo esc_html(get_the_title()); ?></h3>
                                <?php if ($job_location): ?>
                                    <p class="cn-job-location">📍 <?php echo esc_html($job_location); ?></p>
                                <?php endif; ?>
                                <p class="cn-job-author"
                                    style="font-size: 0.85rem; color: #718096; margin: 0.25rem 0 0.5rem 0;">
                                    Posted by: <strong
                                        style="color: #4a5568;"><?php echo $is_current_user_author ? 'You' : esc_html($posted_by_name); ?></strong>
                                </p>
                                <p class="cn-job-meta-info">
                                    <span>Posted: <?php echo esc_html(get_the_date('F j, Y')); ?></span>
                                    <?php if ($closing_date): ?>
                                        <span class="cn-separator">•</span>
                                        <span>Closes: <?php echo esc_html(date('F j, Y', strtotime($closing_date))); ?></span>
                                    <?php endif; ?>
                                    <span class="cn-separator">•</span>
                                    <span><?php echo esc_html($application_count); ?> applications</span>
                                </p>
                            </div>

                            <div class="cn-job-status-section">
                                <span class="cn-status-badge" style="background-color: <?php echo esc_attr($status_color); ?>">
                                    <?php echo esc_html(ucfirst($job_status)); ?>
                                </span>
                            </div>
                        </div>

                        <div class="cn-job-actions">
                            <?php if ($current_post_status !== 'draft'): ?>
                                <a href="<?php echo esc_url(get_permalink($job_id)); ?>" class="cn-btn cn-btn-small cn-btn-outline"
                                    target="_blank">View Job</a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(add_query_arg(['action' => 'edit-job', 'job_id' => $job_id], $dashboard_url)); ?>"
                                class="cn-btn cn-btn-small <?php echo $current_post_status === 'draft' ? 'cn-btn-primary' : 'cn-btn-outline'; ?>">
                                <?php echo $current_post_status === 'draft' ? esc_html__('Continue Submission', 'careernest') : esc_html__('Edit', 'careernest'); ?>
                            </a>
                            <?php if ($application_count > 0 && $current_post_status !== 'draft'): ?>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'view-applications', 'filter_job' => $job_id], $dashboard_url)); ?>"
                                    class="cn-btn cn-btn-small cn-btn-primary">
                                    Applications (<?php echo esc_html($application_count); ?>)
                                </a>
                            <?php endif; ?>
                            <button type="button" class="cn-btn cn-btn-small cn-btn-danger cn-delete-job-mgmt"
                                data-job-id="<?php echo esc_attr($job_id); ?>"
                                data-job-title="<?php echo esc_attr(get_the_title()); ?>"
                                data-nonce="<?php echo esc_attr(wp_create_nonce('cn_delete_job')); ?>">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="cn-empty-state">
                <div class="cn-empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34" stroke="#ccc"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <polygon points="18,2 22,6 12,16 8,16 8,12 18,2" stroke="#ccc" stroke-width="2" />
                    </svg>
                </div>
                <h3><?php echo esc_html__('No Jobs Found', 'careernest'); ?></h3>
                <p><?php echo esc_html__('No jobs match your current filters.', 'careernest'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('action', 'add-job', $dashboard_url)); ?>"
                    class="cn-btn cn-btn-primary"><?php echo esc_html__('Post New Job', 'careernest'); ?></a>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</main>

<script>
    // Delete job handler
    jQuery(document).ready(function($) {
        $('.cn-delete-job-mgmt').on('click', function(e) {
            e.preventDefault();
            const jobId = $(this).data('job-id');
            const jobTitle = $(this).data('job-title');
            const nonce = $(this).data('nonce');

            if (confirm('Are you sure you want to delete "' + jobTitle +
                    '"? This will move it to trash.')) {
                const form = $('<form method="post" action="' + window.location.href + '"></form>');
                form.append('<input type="hidden" name="action" value="delete_job">');
                form.append('<input type="hidden" name="job_id" value="' + jobId + '">');
                form.append('<input type="hidden" name="cn_delete_job_nonce" value="' + nonce + '">');
                $('body').append(form);
                form.submit();
            }
        });
    });
</script>

<?php
get_footer();

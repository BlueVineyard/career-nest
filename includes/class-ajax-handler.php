<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler
{
    public function __construct()
    {
        add_action('wp_ajax_careernest_filter_jobs', [$this, 'filter_jobs']);
        add_action('wp_ajax_nopriv_careernest_filter_jobs', [$this, 'filter_jobs']);
    }

    public function filter_jobs()
    {
        // Verify nonce
        check_ajax_referer('careernest_jobs_nonce', 'nonce');

        // Get and sanitize parameters
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $selected_category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $selected_type = isset($_POST['type']) ? absint($_POST['type']) : 0;
        $selected_location = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
        $selected_employer = isset($_POST['employer']) ? absint($_POST['employer']) : 0;
        $min_salary = isset($_POST['min_salary']) ? absint($_POST['min_salary']) : 0;
        $max_salary = isset($_POST['max_salary']) ? absint($_POST['max_salary']) : 200000;
        $date_posted = isset($_POST['date_posted']) ? sanitize_text_field(wp_unslash($_POST['date_posted'])) : '';
        $sort_by = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'date_desc';
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

        // Build query arguments
        $args = [
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => $paged,
        ];

        // Search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }

        // Tax query for categories and types
        $tax_query = [];
        if ($selected_category > 0) {
            $tax_query[] = [
                'taxonomy' => 'job_category',
                'field' => 'term_id',
                'terms' => $selected_category,
            ];
        }
        if ($selected_type > 0) {
            $tax_query[] = [
                'taxonomy' => 'job_type',
                'field' => 'term_id',
                'terms' => $selected_type,
            ];
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Build meta query array
        $meta_query = ['relation' => 'AND'];

        // Location filter
        if (!empty($selected_location)) {
            $meta_query[] = [
                'key' => '_job_location',
                'value' => $selected_location,
                'compare' => 'LIKE',
            ];
        }

        // Employer filter
        if ($selected_employer > 0) {
            $meta_query[] = [
                'key' => '_employer_id',
                'value' => $selected_employer,
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
        }

        // Salary range filter (only for numeric salaries)
        if ($min_salary > 0 || $max_salary < 200000) {
            if ($min_salary > 0 && $max_salary < 200000) {
                $meta_query[] = [
                    'key' => '_salary',
                    'value' => [$min_salary, $max_salary],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC',
                ];
            } elseif ($min_salary > 0) {
                $meta_query[] = [
                    'key' => '_salary',
                    'value' => $min_salary,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            } elseif ($max_salary < 200000) {
                $meta_query[] = [
                    'key' => '_salary',
                    'value' => $max_salary,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ];
            }
        }

        // Add meta query if we have conditions
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        // Date posted filter
        if (!empty($date_posted)) {
            switch ($date_posted) {
                case '24h':
                    $args['date_query'] = [
                        ['after' => '24 hours ago']
                    ];
                    break;
                case '7d':
                    $args['date_query'] = [
                        ['after' => '7 days ago']
                    ];
                    break;
                case '30d':
                    $args['date_query'] = [
                        ['after' => '30 days ago']
                    ];
                    break;
            }
        }

        // Sorting
        switch ($sort_by) {
            case 'date_asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'title_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'date_desc':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        // Execute query
        $jobs_query = new \WP_Query($args);

        // Generate header HTML
        $active_filters = 0;
        if (!empty($search_query)) $active_filters++;
        if ($selected_category > 0) $active_filters++;
        if ($selected_type > 0) $active_filters++;
        if (!empty($selected_location)) $active_filters++;
        if ($selected_employer > 0) $active_filters++;
        if ($min_salary > 0 || ($max_salary > 0 && $max_salary < 200000)) $active_filters++;
        if (!empty($date_posted)) $active_filters++;

        ob_start();
?>
        <h1 class="cn-jobs-title"><?php esc_html_e('Jobs', 'careernest'); ?></h1>

        <?php if ($active_filters > 0): ?>
            <p class="cn-jobs-results-info">
                <?php
                /* translators: %d: number of jobs found */
                printf(esc_html__('Found %d job(s)', 'careernest'), $jobs_query->found_posts);
                ?>
                <span class="cn-active-filters-count">
                    <?php
                    /* translators: %d: number of active filters */
                    printf(esc_html__('with %d active filter(s)', 'careernest'), $active_filters);
                    ?>
                </span>
            </p>
        <?php else: ?>
            <p class="cn-jobs-subtitle">
                <?php
                /* translators: %d: total number of available jobs */
                printf(esc_html__('Showing %d available positions', 'careernest'), $jobs_query->found_posts);
                ?>
            </p>
        <?php endif; ?>
        <?php
        $header_html = ob_get_clean();

        // Generate jobs HTML
        ob_start();
        if ($jobs_query->have_posts()): ?>
            <div class="cn-jobs-list">
                <?php while ($jobs_query->have_posts()): $jobs_query->the_post();
                    $this->render_job_card(get_the_ID());
                endwhile; ?>
            </div>

            <?php if ($jobs_query->max_num_pages > 1): ?>
                <nav class="cn-pagination">
                    <?php
                    $big = 999999999;
                    echo paginate_links([
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, $paged),
                        'total' => $jobs_query->max_num_pages,
                        'prev_text' => '&laquo; ' . __('Previous', 'careernest'),
                        'next_text' => __('Next', 'careernest') . ' &raquo;',
                        'type' => 'list',
                        'end_size' => 2,
                        'mid_size' => 2,
                    ]);
                    ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="cn-jobs-empty">
                <div class="cn-empty-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M21 16V8C21 6.89543 20.1046 6 19 6H5C3.89543 6 3 6.89543 3 8V16C3 17.1046 3.89543 18 5 18H19C20.1046 18 21 17.1046 21 16Z"
                            stroke="#CCCCCC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 6V18" stroke="#CCCCCC" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
                <h2 class="cn-empty-title">
                    <?php
                    if ($active_filters > 0) {
                        esc_html_e('No Jobs Found', 'careernest');
                    } else {
                        esc_html_e('No Jobs Available', 'careernest');
                    }
                    ?>
                </h2>
                <p class="cn-empty-message">
                    <?php
                    if ($active_filters > 0) {
                        esc_html_e('No jobs match your current filters. Try adjusting your search criteria or clearing filters.', 'careernest');
                    } else {
                        esc_html_e('There are currently no job listings available. Please check back later.', 'careernest');
                    }
                    ?>
                </p>
            </div>
        <?php endif;

        $jobs_html = ob_get_clean();
        wp_reset_postdata();

        // Send response
        wp_send_json_success([
            'header_html' => $header_html,
            'jobs_html' => $jobs_html,
            'found_posts' => $jobs_query->found_posts,
            'max_pages' => $jobs_query->max_num_pages,
        ]);
    }

    private function render_job_card($job_id)
    {
        $employer_id = get_post_meta($job_id, '_employer_id', true);
        $employer_id = $employer_id ? (int) $employer_id : 0;

        // Get company info with validation
        $company_name = '';
        $employer_logo = '';
        if ($employer_id && get_post_status($employer_id)) {
            $company_name = get_the_title($employer_id);
            $employer_logo = get_the_post_thumbnail_url($employer_id, 'thumbnail');
        }

        $location = get_post_meta($job_id, '_job_location', true);
        $remote_position = get_post_meta($job_id, '_remote_position', true);
        $salary_mode = get_post_meta($job_id, '_salary_mode', true);
        $salary_range = get_post_meta($job_id, '_salary_range', true);
        $salary_numeric = get_post_meta($job_id, '_salary', true);
        $closing_date = get_post_meta($job_id, '_closing_date', true);
        $position_filled = get_post_meta($job_id, '_position_filled', true);

        // Get job type terms
        $job_types_terms = get_the_terms($job_id, 'job_type');

        // Calculate days until closing
        $expiry_text = '';
        $expiry_class = '';
        if ($closing_date) {
            $closing_timestamp = strtotime($closing_date . ' 23:59:59');
            $current_timestamp = current_time('timestamp');
            $days_diff = ceil(($closing_timestamp - $current_timestamp) / DAY_IN_SECONDS);

            if ($days_diff > 7) {
                $expiry_text = 'Expires in ' . $days_diff . ' days';
                $expiry_class = 'cn-expiry-normal';
            } elseif ($days_diff > 0) {
                $expiry_text = 'Expires in ' . $days_diff . ' day' . ($days_diff > 1 ? 's' : '');
                $expiry_class = 'cn-expiry-warning';
            } elseif ($days_diff === 0) {
                $expiry_text = 'Expires today';
                $expiry_class = 'cn-expiry-urgent';
            } else {
                $expiry_text = 'Expired';
                $expiry_class = 'cn-expiry-expired';
            }
        }
        ?>
        <article class="cn-job-card <?php echo $position_filled ? 'cn-job-filled' : ''; ?>">
            <div class="cn-job-card-header">
                <?php if ($employer_logo): ?>
                    <img src="<?php echo esc_url($employer_logo); ?>" alt="<?php echo esc_attr($company_name); ?>"
                        class="cn-job-logo">
                <?php else: ?>
                    <div class="cn-job-logo-placeholder">
                        <span><?php echo esc_html(substr($company_name ?: get_the_title(), 0, 1)); ?></span>
                    </div>
                <?php endif; ?>

                <div class="cn-job-header-content">
                    <h2 class="cn-job-title">
                        <a href="<?php echo esc_url(get_permalink()); ?>">
                            <?php echo esc_html(get_the_title()); ?>
                        </a>
                    </h2>
                    <?php if ($company_name): ?>
                        <p class="cn-job-company"><?php echo esc_html($company_name); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($position_filled): ?>
                    <span class="cn-job-status-badge cn-status-filled">
                        <?php esc_html_e('Filled', 'careernest'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="cn-job-card-meta">
                <?php if ($location): ?>
                    <span class="cn-job-meta-item">
                        <svg width="16" height="16" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                stroke="currentColor" stroke-width="1.5" />
                            <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                        <?php echo esc_html($location); ?>
                        <?php if ($remote_position): ?>
                            <span class="cn-remote-badge"><?php esc_html_e('Remote', 'careernest'); ?></span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>

                <?php if ($job_types_terms && !is_wp_error($job_types_terms)): ?>
                    <span class="cn-job-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M4.97883 9.68508C2.99294 8.89073 2 8.49355 2 8C2 7.50645 2.99294 7.10927 4.97883 6.31492L7.7873 5.19153C9.77318 4.39718 10.7661 4 12 4C13.2339 4 14.2268 4.39718 16.2127 5.19153L19.0212 6.31492C21.0071 7.10927 22 7.50645 22 8C22 8.49355 21.0071 8.89073 19.0212 9.68508L16.2127 10.8085C14.2268 11.6028 13.2339 12 12 12C10.7661 12 9.77318 11.6028 7.7873 10.8085L4.97883 9.68508Z"
                                stroke="currentColor" stroke-width="1.5" />
                        </svg>
                        <?php echo esc_html($job_types_terms[0]->name); ?>
                    </span>
                <?php endif; ?>

                <?php if ($salary_mode === 'numeric' && $salary_numeric): ?>
                    <span class="cn-job-meta-item cn-salary">
                        <svg width="16" height="16" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="10" cy="10.5" r="8.33333" stroke="currentColor" stroke-width="1.5" />
                            <path d="M10 5.5V15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path
                                d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        $<?php echo esc_html(number_format($salary_numeric)); ?>
                    </span>
                <?php elseif ($salary_range): ?>
                    <span class="cn-job-meta-item cn-salary">
                        <svg width="16" height="16" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="10" cy="10.5" r="8.33333" stroke="currentColor" stroke-width="1.5" />
                            <path d="M10 5.5V15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path
                                d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <?php echo esc_html($salary_range); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty(get_the_excerpt())): ?>
                <div class="cn-job-excerpt">
                    <?php echo wp_kses_post(wp_trim_words(get_the_excerpt(), 30, '...')); ?>
                </div>
            <?php endif; ?>

            <div class="cn-job-card-footer">
                <?php if ($expiry_text): ?>
                    <span class="cn-job-expiry <?php echo esc_attr($expiry_class); ?>">
                        <svg width="14" height="14" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.66659 10.5C1.66659 15.1024 5.39755 18.8333 9.99992 18.8333C14.6023 18.8333 18.3333 15.1024 18.3333 10.5C18.3333 5.89763 14.6023 2.16667 9.99992 2.16667"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M10 8V11.3333H13.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <?php echo esc_html($expiry_text); ?>
                    </span>
                <?php endif; ?>

                <a href="<?php echo esc_url(get_permalink()); ?>" class="cn-btn cn-btn-view-job">
                    <?php esc_html_e('View Details', 'careernest'); ?>
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </article>
<?php
    }
}

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
        add_action('wp_ajax_careernest_track_external_click', [$this, 'track_external_click']);
        add_action('wp_ajax_nopriv_careernest_track_external_click', [$this, 'track_external_click']);
        add_action('wp_ajax_careernest_toggle_bookmark', [$this, 'toggle_bookmark']);
        add_action('wp_ajax_nopriv_careernest_toggle_bookmark', [$this, 'toggle_bookmark']);
    }

    /**
     * Track external application click
     */
    public function track_external_click()
    {
        check_ajax_referer('careernest_external_click', 'nonce');

        $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;

        if (!$job_id) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }

        // Get current count
        $current_count = get_post_meta($job_id, '_external_application_count', true);
        $current_count = $current_count ? (int) $current_count : 0;

        // Increment count
        update_post_meta($job_id, '_external_application_count', $current_count + 1);

        wp_send_json_success([
            'message' => 'Click tracked',
            'count' => $current_count + 1
        ]);
    }

    /**
     * Toggle job bookmark for applicants
     */
    public function toggle_bookmark()
    {
        // Verify nonce
        check_ajax_referer('careernest_jobs_nonce', 'nonce');

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please log in to bookmark jobs', 'require_login' => true]);
        }

        // Check if user is an applicant
        $user = wp_get_current_user();
        if (!in_array('applicant', $user->roles)) {
            wp_send_json_error(['message' => 'Only applicants can bookmark jobs', 'require_login' => true]);
        }

        $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;

        if (!$job_id || get_post_type($job_id) !== 'job_listing') {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }

        $user_id = get_current_user_id();

        // Get current bookmarks
        $bookmarks = get_user_meta($user_id, '_bookmarked_jobs', true);
        $bookmarks = $bookmarks ? (array) $bookmarks : [];

        // Toggle bookmark
        if (in_array($job_id, $bookmarks)) {
            // Remove bookmark
            $bookmarks = array_diff($bookmarks, [$job_id]);
            $is_bookmarked = false;
            $message = 'Bookmark removed';
        } else {
            // Add bookmark
            $bookmarks[] = $job_id;
            $is_bookmarked = true;
            $message = 'Job bookmarked!';
        }

        // Update user meta
        update_user_meta($user_id, '_bookmarked_jobs', array_values($bookmarks));

        wp_send_json_success([
            'is_bookmarked' => $is_bookmarked,
            'message' => $message
        ]);
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
        $sort_by = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'expiry_asc';
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

        // Radius search parameters
        $radius = isset($_POST['radius']) ? absint($_POST['radius']) : 0;
        $user_lat = isset($_POST['user_lat']) ? floatval($_POST['user_lat']) : 0;
        $user_lng = isset($_POST['user_lng']) ? floatval($_POST['user_lng']) : 0;

        // Build query arguments
        $args = [
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => 9,
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

        // Location filter - skip text-based if we have coordinates (for distance-based search)
        // Only use text-based search when no coordinates are available
        if (!empty($selected_location) && !($user_lat && $user_lng)) {
            $meta_query[] = [
                'key' => '_job_location',
                'value' => $selected_location,
                'compare' => 'LIKE',
            ];
        }

        // Radius search - add bounding box for initial filtering (ONLY when radius > 0)
        // When radius = 0, we don't filter by location in SQL - just calculate distances in PHP
        if ($radius > 0 && $user_lat && $user_lng) {
            $bounds = $this->calculate_bounding_box($user_lat, $user_lng, $radius);

            $meta_query[] = [
                'key' => '_job_location_lat',
                'value' => [$bounds['min_lat'], $bounds['max_lat']],
                'compare' => 'BETWEEN',
                'type' => 'DECIMAL(10,8)',
            ];

            $meta_query[] = [
                'key' => '_job_location_lng',
                'value' => [$bounds['min_lng'], $bounds['max_lng']],
                'compare' => 'BETWEEN',
                'type' => 'DECIMAL(11,8)',
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
            case 'expiry_asc':
                // Expiring soonest first (most urgent)
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = '_closing_date';
                $args['meta_type'] = 'DATE';
                $args['order'] = 'ASC';
                break;
            case 'expiry_desc':
                // Expiring latest first
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = '_closing_date';
                $args['meta_type'] = 'DATE';
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

        // Calculate distances and optionally filter by radius
        if ($user_lat && $user_lng && $jobs_query->have_posts()) {
            $jobs_query = $this->calculate_distances($jobs_query, $user_lat, $user_lng, $radius);
        }

        // Generate header HTML
        $active_filters = 0;
        if (!empty($search_query)) $active_filters++;
        if ($selected_category > 0) $active_filters++;
        if ($selected_type > 0) $active_filters++;
        if (!empty($selected_location)) $active_filters++;
        if ($selected_employer > 0) $active_filters++;
        if ($min_salary > 0 || ($max_salary > 0 && $max_salary < 200000)) $active_filters++;
        if (!empty($date_posted)) $active_filters++;
        if ($radius > 0 && $user_lat && $user_lng) $active_filters++;

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

        // Get column setting
        $filter_settings = get_option('careernest_options', []);
        $job_columns = isset($filter_settings['job_listing_columns']) ? $filter_settings['job_listing_columns'] : '1';

        // Generate jobs HTML
        ob_start();
        if ($jobs_query->have_posts()): ?>
            <div class="cn-jobs-list cn-jobs-columns-<?php echo esc_attr($job_columns); ?>">
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

        // Build marker data for map view
        $markers = [];
        if ($jobs_query->have_posts()) {
            foreach ($jobs_query->posts as $post) {
                $job_lat = get_post_meta($post->ID, '_job_location_lat', true);
                $job_lng = get_post_meta($post->ID, '_job_location_lng', true);

                // Only include jobs with coordinates
                if (!empty($job_lat) && !empty($job_lng)) {
                    $employer_id = get_post_meta($post->ID, '_employer_id', true);
                    $employer_logo = $employer_id ? get_the_post_thumbnail_url($employer_id, 'thumbnail') : '';
                    $company_name = $employer_id ? get_the_title($employer_id) : '';

                    $job_types_terms = get_the_terms($post->ID, 'job_type');
                    $job_type = ($job_types_terms && !is_wp_error($job_types_terms)) ? $job_types_terms[0]->name : '';

                    $markers[] = [
                        'id' => $post->ID,
                        'title' => get_the_title($post->ID),
                        'company' => $company_name,
                        'logo' => $employer_logo,
                        'location' => get_post_meta($post->ID, '_job_location', true),
                        'job_type' => $job_type,
                        'lat' => floatval($job_lat),
                        'lng' => floatval($job_lng),
                        'distance' => isset($post->distance) ? $post->distance : null,
                        'permalink' => get_permalink($post->ID),
                    ];
                }
            }
        }

        // Send response
        wp_send_json_success([
            'header_html' => $header_html,
            'jobs_html' => $jobs_html,
            'found_posts' => $jobs_query->found_posts,
            'max_pages' => $jobs_query->max_num_pages,
            'markers' => $markers,
            'user_location' => ($user_lat && $user_lng) ? ['lat' => $user_lat, 'lng' => $user_lng] : null,
            'radius' => $radius,
        ]);
    }

    /**
     * Calculate bounding box for initial SQL filtering
     */
    private function calculate_bounding_box($lat, $lng, $radius)
    {
        // Earth's radius in km
        $earth_radius = 6371;

        // Calculate angular distance
        $angular_distance = $radius / $earth_radius;

        // Convert to radians
        $lat_rad = deg2rad($lat);
        $lng_rad = deg2rad($lng);

        // Calculate bounds
        $min_lat = $lat - rad2deg($angular_distance);
        $max_lat = $lat + rad2deg($angular_distance);

        // Longitude calculation accounts for latitude
        $lng_delta = rad2deg($angular_distance / cos($lat_rad));
        $min_lng = $lng - $lng_delta;
        $max_lng = $lng + $lng_delta;

        return [
            'min_lat' => $min_lat,
            'max_lat' => $max_lat,
            'min_lng' => $min_lng,
            'max_lng' => $max_lng,
        ];
    }

    /**
     * Calculate distances for all jobs and optionally filter by radius
     * When radius = 0, calculates distances but doesn't filter (shows all with badges)
     * When radius > 0, filters to only jobs within radius
     */
    private function calculate_distances($query, $user_lat, $user_lng, $radius)
    {
        $processed_posts = [];

        foreach ($query->posts as $post) {
            $job_lat = get_post_meta($post->ID, '_job_location_lat', true);
            $job_lng = get_post_meta($post->ID, '_job_location_lng', true);

            // Skip jobs without coordinates
            if (empty($job_lat) || empty($job_lng)) {
                // If radius is 0 (Any distance), keep jobs without coordinates
                if ($radius === 0) {
                    $processed_posts[] = $post;
                }
                continue;
            }

            $distance = $this->calculate_distance($user_lat, $user_lng, floatval($job_lat), floatval($job_lng));

            // Store distance for display
            $post->distance = $distance;

            // If radius is 0 (Any distance), include all jobs with distance badges
            // If radius > 0, only include jobs within radius
            if ($radius === 0 || $distance <= $radius) {
                $processed_posts[] = $post;
            }
        }

        // Update query object
        $query->posts = $processed_posts;
        $query->post_count = count($processed_posts);
        $query->found_posts = count($processed_posts);

        // Keep pagination if radius is 0, simplify if radius filtering
        if ($radius > 0) {
            $query->max_num_pages = 1;
        }

        return $query;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2)
    {
        // Earth's radius in km
        $earth_radius = 6371;

        // Convert to radians
        $lat1_rad = deg2rad($lat1);
        $lon1_rad = deg2rad($lon1);
        $lat2_rad = deg2rad($lat2);
        $lon2_rad = deg2rad($lon2);

        // Calculate differences
        $dlat = $lat2_rad - $lat1_rad;
        $dlon = $lon2_rad - $lon1_rad;

        // Haversine formula
        $a = sin($dlat / 2) * sin($dlat / 2) +
            cos($lat1_rad) * cos($lat2_rad) *
            sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earth_radius * $c;

        return round($distance, 2);
    }

    private function render_job_card($job_id, $distance = null)
    {
        // Get distance if set on post object
        global $post;
        if ($distance === null && isset($post->distance)) {
            $distance = $post->distance;
        }

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

        // Check if job is bookmarked
        $user_bookmarks = [];
        if (is_user_logged_in()) {
            $user_bookmarks = get_user_meta(get_current_user_id(), '_bookmarked_jobs', true);
            $user_bookmarks = $user_bookmarks ? (array) $user_bookmarks : [];
        }
        $is_bookmarked = in_array($job_id, $user_bookmarks);
        ?>
        <article class="cn-job-card <?php echo $position_filled ? 'cn-job-filled' : ''; ?>"
            data-job-id="<?php echo esc_attr($job_id); ?>">
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
                    <?php if ($company_name):
                        // Get job type for inline display
                        $job_types_terms_header = get_the_terms($job_id, 'job_type');
                        $job_type_name_header = '';
                        $job_type_slug_header = '';
                        if ($job_types_terms_header && !is_wp_error($job_types_terms_header)) {
                            $job_type_name_header = $job_types_terms_header[0]->name;
                            $job_type_slug_header = sanitize_title($job_type_name_header);
                        }
                    ?>
                        <p class="cn-job-company">
                            <?php echo esc_html($company_name); ?>
                            <?php if ($job_type_name_header): ?>
                                <span class="cn-company-separator"> | </span>
                                <span class="cn-job-type-inline cn-job-type-<?php echo esc_attr($job_type_slug_header); ?>">
                                    <?php echo esc_html($job_type_name_header); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($position_filled): ?>
                    <span class="cn-job-status-badge cn-status-filled">
                        <?php esc_html_e('Filled', 'careernest'); ?>
                    </span>
                <?php endif; ?>

                <?php
                // Bookmark icon with conditional tooltip
                $is_applicant = is_user_logged_in() && (current_user_can('applicant') || in_array('applicant', wp_get_current_user()->roles));
                $bookmark_tooltip = $is_applicant ? __('Bookmark this job', 'careernest') : __('Log in as an applicant', 'careernest');
                ?>
                <button type="button" class="cn-job-bookmark-btn <?php echo $is_bookmarked ? 'bookmarked' : ''; ?>"
                    title="<?php echo esc_attr($bookmark_tooltip); ?>" aria-label="<?php echo esc_attr($bookmark_tooltip); ?>">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16.1269 3.04559C17.1353 3.16292 17.875 4.03284 17.875 5.0485V19.2504L11 15.8129L4.125 19.2504V5.0485C4.125 4.03284 4.86383 3.16292 5.87308 3.04559C9.27959 2.65017 12.7204 2.65017 16.1269 3.04559Z"
                            stroke="#636363" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>

            <div class="cn-job-card-meta">
                <?php if ($location):
                    $debug_mode = get_option('careernest_options', []);
                    $show_coords = isset($debug_mode['debug_show_coordinates']) && $debug_mode['debug_show_coordinates'] === '1';
                    $job_lat = get_post_meta($job_id, '_job_location_lat', true);
                    $job_lng = get_post_meta($job_id, '_job_location_lng', true);
                ?>
                    <span class="cn-job-meta-item">
                        <svg width="16" height="16" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                stroke="currentColor" stroke-width="1.5" />
                            <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                        <?php echo esc_html($location); ?>
                        <?php if ($show_coords && ($job_lat || $job_lng)): ?>
                            <span class="cn-debug-coords"
                                style="display: inline-block; margin-left: 8px; padding: 2px 6px; background: #f0f0f0; border-radius: 3px; font-size: 11px; color: #666; font-family: monospace;">
                                <?php echo esc_html($job_lat ? number_format((float)$job_lat, 6) : 'N/A'); ?>,
                                <?php echo esc_html($job_lng ? number_format((float)$job_lng, 6) : 'N/A'); ?>
                            </span>
                        <?php elseif ($show_coords): ?>
                            <span class="cn-debug-coords"
                                style="display: inline-block; margin-left: 8px; padding: 2px 6px; background: #ffebee; border-radius: 3px; font-size: 11px; color: #c62828; font-family: monospace;">
                                NO COORDS
                            </span>
                        <?php endif; ?>
                        <?php if ($distance !== null): ?>
                            <span class="cn-distance-badge"><?php echo esc_html(number_format($distance, 1)); ?> km</span>
                        <?php endif; ?>
                        <?php if ($remote_position): ?>
                            <span class="cn-remote-badge"><?php esc_html_e('Remote', 'careernest'); ?></span>
                        <?php endif; ?>
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
                <?php if ($closing_date): ?>
                    <span class="cn-job-closing-date">
                        <svg width="14" height="14" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.66659 10.5C1.66659 15.1024 5.39755 18.8333 9.99992 18.8333C14.6023 18.8333 18.3333 15.1024 18.3333 10.5C18.3333 5.89763 14.6023 2.16667 9.99992 2.16667"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M10 8V11.3333H13.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <?php echo esc_html(date('M jS, Y', strtotime($closing_date))); ?>
                    </span>
                <?php endif; ?>

                <?php if ($expiry_text): ?>
                    <span class="cn-job-expiry <?php echo esc_attr($expiry_class); ?>">
                        <?php echo esc_html($expiry_text); ?>
                    </span>
                <?php endif; ?>
            </div>
        </article>
<?php
    }
}

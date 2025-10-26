<?php

/**
 * Template: CareerNest — Job Listings
 */

defined('ABSPATH') || exit;

// Enqueue custom dropdown assets
wp_enqueue_style('careernest-custom-dropdown', CAREERNEST_URL . 'assets/css/custom-dropdown.css', [], CAREERNEST_VERSION);
wp_enqueue_script('careernest-custom-dropdown', CAREERNEST_URL . 'assets/js/custom-dropdown.js', ['jquery'], CAREERNEST_VERSION, true);

// Enqueue AJAX script
wp_enqueue_script('careernest-jobs-ajax', CAREERNEST_URL . 'assets/js/jobs-ajax.js', ['jquery', 'careernest-custom-dropdown'], CAREERNEST_VERSION, true);

// Get CareerNest pages
$careernest_pages = get_option('careernest_pages', []);
$login_page_id = isset($careernest_pages['login']) ? $careernest_pages['login'] : 0;
$login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url();

wp_localize_script('careernest-jobs-ajax', 'careerNestAjax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('careernest_jobs_nonce'),
    'pages' => [
        'login' => $login_url
    ]
]);

get_header();

// Get current page number for pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get filter parameters from URL
$search_query = isset($_GET['job_search']) ? sanitize_text_field(wp_unslash($_GET['job_search'])) : '';
$selected_category = isset($_GET['job_category']) ? absint($_GET['job_category']) : 0;
$selected_type = isset($_GET['job_type']) ? absint($_GET['job_type']) : 0;
$selected_location = isset($_GET['job_location']) ? sanitize_text_field(wp_unslash($_GET['job_location'])) : '';
$selected_employer = isset($_GET['employer_id']) ? absint($_GET['employer_id']) : 0;
$min_salary = isset($_GET['min_salary']) ? absint($_GET['min_salary']) : 0;
$max_salary = isset($_GET['max_salary']) ? absint($_GET['max_salary']) : 200000;
$date_posted = isset($_GET['date_posted']) ? sanitize_text_field(wp_unslash($_GET['date_posted'])) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'expiry_asc';

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
$jobs_query = new WP_Query($args);

// Get filter settings
$filter_settings = get_option('careernest_options', []);

// Get taxonomies for filters
$job_categories = get_terms([
    'taxonomy' => 'job_category',
    'hide_empty' => true,
]);

$job_types = get_terms([
    'taxonomy' => 'job_type',
    'hide_empty' => true,
]);

// Filter settings already loaded above for country filtering
$show_category = isset($filter_settings['filter_category']) ? $filter_settings['filter_category'] === '1' : true;
$show_job_type = isset($filter_settings['filter_job_type']) ? $filter_settings['filter_job_type'] === '1' : true;
$show_location = isset($filter_settings['filter_location']) ? $filter_settings['filter_location'] === '1' : true;
$show_employer = isset($filter_settings['filter_employer']) ? $filter_settings['filter_employer'] === '1' : true;
$show_salary = isset($filter_settings['filter_salary']) ? $filter_settings['filter_salary'] === '1' : true;
$show_date_posted = isset($filter_settings['filter_date_posted']) ? $filter_settings['filter_date_posted'] === '1' : true;
$show_sort = isset($filter_settings['filter_sort']) ? $filter_settings['filter_sort'] === '1' : true;
$filter_position = isset($filter_settings['filter_position']) ? $filter_settings['filter_position'] : 'left';
$job_columns = isset($filter_settings['job_listing_columns']) ? $filter_settings['job_listing_columns'] : '1';

// Get filter order (default order if not set)
$default_order = ['search', 'filter_category', 'filter_job_type', 'filter_location', 'filter_employer', 'filter_salary', 'filter_date_posted', 'filter_sort'];
$filter_order = isset($filter_settings['filter_order']) && is_array($filter_settings['filter_order']) ? $filter_settings['filter_order'] : $default_order;

// Helper function to render each filter
$render_filter = function ($filter_key) use ($show_category, $show_job_type, $show_location, $show_employer, $show_salary, $show_date_posted, $show_sort, $job_categories, $job_types, $selected_category, $selected_type, $selected_location, $selected_employer, $min_salary, $max_salary, $date_posted, $sort_by, $search_query) {
    switch ($filter_key) {
        case 'search':
?>
            <!-- Search -->
            <div class="cn-filter-group">
                <label for="job_search" class="cn-filter-label">
                    <?php esc_html_e('Search', 'careernest'); ?>
                </label>
                <div class="cn-input-with-icon">
                    <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17.5 17.5L13.875 13.875M15.8333 9.16667C15.8333 12.8486 12.8486 15.8333 9.16667 15.8333C5.48477 15.8333 2.5 12.8486 2.5 9.16667C2.5 5.48477 5.48477 2.5 9.16667 2.5C12.8486 2.5 15.8333 5.48477 15.8333 9.16667Z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="text" id="job_search" name="job_search" value="<?php echo esc_attr($search_query); ?>"
                        placeholder="<?php esc_attr_e('Job title, keywords...', 'careernest'); ?>"
                        class="cn-filter-input cn-input-with-icon-field" />
                </div>
            </div>
            <?php
            break;

        case 'filter_category':
            if ($show_category && !empty($job_categories) && !is_wp_error($job_categories)):
            ?>
                <!-- Category Filter -->
                <div class="cn-filter-group">
                    <label for="job_category" class="cn-filter-label">
                        <?php esc_html_e('Category', 'careernest'); ?>
                    </label>
                    <div class="cn-custom-select-wrapper" data-icon="folder">
                        <select name="job_category" id="job_category" class="cn-filter-select cn-custom-select">
                            <option value=""><?php esc_html_e('All Categories', 'careernest'); ?></option>
                            <?php foreach ($job_categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>"
                                    <?php selected($selected_category, $category->term_id); ?>>
                                    <?php echo esc_html($category->name); ?>
                                    (<?php echo esc_html($category->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_job_type':
            if ($show_job_type && !empty($job_types) && !is_wp_error($job_types)):
            ?>
                <!-- Job Type Filter -->
                <div class="cn-filter-group">
                    <label for="job_type" class="cn-filter-label">
                        <?php esc_html_e('Job Type', 'careernest'); ?>
                    </label>
                    <div class="cn-custom-select-wrapper" data-icon="layers">
                        <select name="job_type" id="job_type" class="cn-filter-select cn-custom-select">
                            <option value=""><?php esc_html_e('All Types', 'careernest'); ?></option>
                            <?php foreach ($job_types as $type): ?>
                                <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected($selected_type, $type->term_id); ?>>
                                    <?php echo esc_html($type->name); ?> (<?php echo esc_html($type->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_location':
            if ($show_location):
            ?>
                <!-- Location Filter -->
                <div class="cn-filter-group">
                    <label for="job_location" class="cn-filter-label">
                        <?php esc_html_e('Location', 'careernest'); ?>
                    </label>
                    <div class="cn-location-input-wrapper">
                        <div class="cn-input-with-icon">
                            <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 20 21" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                    stroke="currentColor" stroke-width="1.5" />
                                <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <input type="text" id="job_location" name="job_location" value="<?php echo esc_attr($selected_location); ?>"
                                placeholder="<?php esc_attr_e('City, state, or region...', 'careernest'); ?>"
                                class="cn-filter-input cn-input-with-icon-field cn-input-with-chips" />
                            <!-- Distance chip (shown when radius > 0) -->
                            <span class="cn-distance-chip" style="display: none;">
                                <span class="cn-distance-chip-text"></span>
                                <svg class="cn-distance-chip-close" width="12" height="12" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                        <button type="button" class="cn-get-location-btn"
                            title="<?php esc_attr_e('Use my current location', 'careernest'); ?>"
                            aria-label="<?php esc_attr_e('Use my current location', 'careernest'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px"
                                fill="currentColor">
                                <path
                                    d="M440-42v-80q-125-14-214.5-103.5T122-440H42v-80h80q14-125 103.5-214.5T440-838v-80h80v80q125 14 214.5 103.5T838-520h80v80h-80q-14 125-103.5 214.5T520-122v80h-80Zm40-158q116 0 198-82t82-198q0-116-82-198t-198-82q-116 0-198 82t-82 198q0 116 82 198t198 82Zm0-120q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Radius Filter (collapsible) -->
                    <div class="cn-radius-filter" style="display: none; margin-top: 1rem;">
                        <label for="search_radius" class="cn-filter-label">
                            <?php esc_html_e('Distance', 'careernest'); ?>
                            <span id="radius-display" class="cn-radius-display">Any distance</span>
                        </label>
                        <input type="range" id="search_radius" name="search_radius" min="0" max="100" step="5" value="0"
                            class="cn-range-slider" />
                        <div class="cn-range-labels">
                            <span><?php esc_html_e('Any', 'careernest'); ?></span>
                            <span><?php esc_html_e('100 km', 'careernest'); ?></span>
                        </div>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_employer':
            $employers = get_posts([
                'post_type' => 'employer',
                'posts_per_page' => 100,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            if ($show_employer && !empty($employers)):
            ?>
                <!-- Employer Filter -->
                <div class="cn-filter-group">
                    <label for="employer_id" class="cn-filter-label">
                        <?php esc_html_e('Employer', 'careernest'); ?>
                    </label>
                    <div class="cn-custom-select-wrapper" data-icon="building">
                        <select name="employer_id" id="employer_id" class="cn-filter-select cn-custom-select">
                            <option value=""><?php esc_html_e('All Employers', 'careernest'); ?></option>
                            <?php foreach ($employers as $employer): ?>
                                <option value="<?php echo esc_attr($employer->ID); ?>"
                                    <?php selected($selected_employer, $employer->ID); ?>>
                                    <?php echo esc_html(get_the_title($employer->ID)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_salary':
            if ($show_salary):
            ?>
                <!-- Salary Range Filter -->
                <div class="cn-filter-group">
                    <label class="cn-filter-label">
                        <?php esc_html_e('Salary Range', 'careernest'); ?>
                        <span id="salary-range-display" class="cn-salary-display">
                            $<?php echo esc_html(number_format($min_salary ?: 0)); ?> -
                            $<?php echo esc_html(number_format($max_salary ?: 200000)); ?>
                        </span>
                    </label>
                    <div class="cn-range-inputs">
                        <input type="range" id="min_salary" name="min_salary" min="0" max="200000" step="5000"
                            value="<?php echo esc_attr($min_salary ?: 0); ?>" class="cn-range-slider" />
                        <input type="range" id="max_salary" name="max_salary" min="0" max="200000" step="5000"
                            value="<?php echo esc_attr($max_salary ?: 200000); ?>" class="cn-range-slider" />
                    </div>
                    <div class="cn-range-labels">
                        <span>$0</span>
                        <span>$200k+</span>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_date_posted':
            if ($show_date_posted):
            ?>
                <!-- Date Posted Filter -->
                <div class="cn-filter-group">
                    <label for="date_posted" class="cn-filter-label">
                        <?php esc_html_e('Date Posted', 'careernest'); ?>
                    </label>
                    <div class="cn-custom-select-wrapper" data-icon="calendar">
                        <select name="date_posted" id="date_posted" class="cn-filter-select cn-custom-select">
                            <option value=""><?php esc_html_e('Any Time', 'careernest'); ?></option>
                            <option value="24h" <?php selected($date_posted, '24h'); ?>>
                                <?php esc_html_e('Last 24 Hours', 'careernest'); ?></option>
                            <option value="7d" <?php selected($date_posted, '7d'); ?>>
                                <?php esc_html_e('Last 7 Days', 'careernest'); ?></option>
                            <option value="30d" <?php selected($date_posted, '30d'); ?>>
                                <?php esc_html_e('Last 30 Days', 'careernest'); ?></option>
                        </select>
                    </div>
                </div>
            <?php
            endif;
            break;

        case 'filter_sort':
            if ($show_sort):
            ?>
                <!-- Sort By -->
                <div class="cn-filter-group">
                    <label for="sort" class="cn-filter-label">
                        <?php esc_html_e('Sort By', 'careernest'); ?>
                    </label>
                    <div class="cn-custom-select-wrapper" data-icon="sort">
                        <select name="sort" id="sort" class="cn-filter-select cn-custom-select">
                            <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>
                                <?php esc_html_e('Newest First', 'careernest'); ?></option>
                            <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>>
                                <?php esc_html_e('Oldest First', 'careernest'); ?></option>
                            <option value="expiry_asc" <?php selected($sort_by, 'expiry_asc'); ?>>
                                <?php esc_html_e('Expiring Soonest', 'careernest'); ?></option>
                            <option value="expiry_desc" <?php selected($sort_by, 'expiry_desc'); ?>>
                                <?php esc_html_e('Expiring Latest', 'careernest'); ?></option>
                            <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>>
                                <?php esc_html_e('Title (A-Z)', 'careernest'); ?></option>
                            <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>>
                                <?php esc_html_e('Title (Z-A)', 'careernest'); ?></option>
                        </select>
                    </div>
                </div>
<?php
            endif;
            break;
    }
};
?>

<main id="primary" class="site-main cn-jobs-page">
    <div class="cn-jobs-container">
        <header class="cn-jobs-header">
            <h1 class="cn-jobs-title"><?php esc_html_e('Jobs', 'careernest'); ?></h1>

            <?php
            // Display active filters count
            $active_filters = 0;
            if (!empty($search_query)) $active_filters++;
            if ($selected_category > 0) $active_filters++;
            if ($selected_type > 0) $active_filters++;
            if (!empty($selected_location)) $active_filters++;
            if ($selected_employer > 0) $active_filters++;
            if ($min_salary > 0 || ($max_salary > 0 && $max_salary < 200000)) $active_filters++;
            if (!empty($date_posted)) $active_filters++;

            if ($active_filters > 0): ?>
                <p class="cn-jobs-results-info">
                    <?php
                    /* translators: %d: number of jobs found */
                    printf(esc_html__('Found %d job(s)', 'careernest'), $jobs_query->found_posts);
                    ?>
                    <?php if ($active_filters > 0): ?>
                        <span class="cn-active-filters-count">
                            <?php
                            /* translators: %d: number of active filters */
                            printf(esc_html__('with %d active filter(s)', 'careernest'), $active_filters);
                            ?>
                        </span>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="cn-jobs-subtitle">
                    <?php
                    /* translators: %d: total number of available jobs */
                    printf(esc_html__('Showing %d available positions', 'careernest'), $jobs_query->found_posts);
                    ?>
                </p>
            <?php endif; ?>
        </header>

        <!-- View Toggle -->
        <div class="cn-view-toggle">
            <button type="button" class="cn-view-toggle-btn cn-view-toggle-list active" data-view="list"
                aria-label="<?php esc_attr_e('List View', 'careernest'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 6H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M8 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M8 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M3 6H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M3 12H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M3 18H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php esc_html_e('List', 'careernest'); ?></span>
            </button>
            <button type="button" class="cn-view-toggle-btn cn-view-toggle-map" data-view="map"
                aria-label="<?php esc_attr_e('Map View', 'careernest'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 6L9 2L1 6V20L9 16L15 20L23 16V2L15 6Z" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 2V16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M15 6V20" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php esc_html_e('Map', 'careernest'); ?></span>
            </button>
        </div>

        <div class="cn-jobs-layout cn-filter-position-<?php echo esc_attr($filter_position); ?>">
            <!-- Sidebar Filters -->
            <aside class="cn-jobs-sidebar cn-filters-<?php echo esc_attr($filter_position); ?>">
                <div class="cn-filters-wrapper">
                    <form method="get" action="" class="cn-jobs-filters" id="cn-jobs-filter-form">
                        <h2 class="cn-filters-title"><?php esc_html_e('Filter Jobs', 'careernest'); ?></h2>

                        <?php
                        // Render filters in specified order
                        foreach ($filter_order as $filter_key) {
                            $render_filter($filter_key);
                        }
                        ?>

                        <!-- Filter Actions -->
                        <div class="cn-filter-actions">
                            <button type="submit" class="cn-btn cn-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M17.5 17.5L13.875 13.875M15.8333 9.16667C15.8333 12.8486 12.8486 15.8333 9.16667 15.8333C5.48477 15.8333 2.5 12.8486 2.5 9.16667C2.5 5.48477 5.48477 2.5 9.16667 2.5C12.8486 2.5 15.8333 5.48477 15.8333 9.16667Z"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e('Apply Filters', 'careernest'); ?>
                            </button>

                            <button type="button" class="cn-btn cn-btn-secondary cn-clear-filters-btn">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <?php esc_html_e('Clear Filters', 'careernest'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Job Listings -->
            <div class="cn-jobs-main">
                <!-- Map View Container (hidden by default) -->
                <div class="cn-jobs-map-container" style="display: none;">
                    <div id="cn-jobs-map" class="cn-jobs-map"></div>
                </div>

                <!-- List View Container -->
                <div class="cn-jobs-list-container">
                    <?php if ($jobs_query->have_posts()): ?>
                        <div class="cn-jobs-list cn-jobs-columns-<?php echo esc_attr($job_columns); ?>">
                            <?php while ($jobs_query->have_posts()): $jobs_query->the_post();
                                $job_id = get_the_ID();
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
                                <?php
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
                                            <img src="<?php echo esc_url($employer_logo); ?>"
                                                alt="<?php echo esc_attr($company_name); ?>" class="cn-job-logo">
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
                                                $job_types_terms_header = get_the_terms(get_the_ID(), 'job_type');
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
                                                        <span
                                                            class="cn-job-type-inline cn-job-type-<?php echo esc_attr($job_type_slug_header); ?>">
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
                                        <button type="button"
                                            class="cn-job-bookmark-btn <?php echo $is_bookmarked ? 'bookmarked' : ''; ?>"
                                            title="<?php echo esc_attr($bookmark_tooltip); ?>"
                                            aria-label="<?php echo esc_attr($bookmark_tooltip); ?>">
                                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M16.1269 3.04559C17.1353 3.16292 17.875 4.03284 17.875 5.0485V19.2504L11 15.8129L4.125 19.2504V5.0485C4.125 4.03284 4.86383 3.16292 5.87308 3.04559C9.27959 2.65017 12.7204 2.65017 16.1269 3.04559Z"
                                                    stroke="#636363" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="cn-job-card-meta">
                                        <?php if ($location): ?>
                                            <span class="cn-job-meta-item">
                                                <svg width="16" height="16" viewBox="0 0 20 21" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                                        stroke="currentColor" stroke-width="1.5" />
                                                    <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor"
                                                        stroke-width="1.5" />
                                                </svg>
                                                <?php echo esc_html($location); ?>
                                                <?php if ($remote_position): ?>
                                                    <span class="cn-remote-badge"><?php esc_html_e('Remote', 'careernest'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($salary_mode === 'numeric' && $salary_numeric): ?>
                                            <span class="cn-job-meta-item cn-salary">
                                                <svg width="16" height="16" viewBox="0 0 20 21" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="10" cy="10.5" r="8.33333" stroke="currentColor"
                                                        stroke-width="1.5" />
                                                    <path d="M10 5.5V15.5" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" />
                                                    <path
                                                        d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                                </svg>
                                                $<?php echo esc_html(number_format($salary_numeric)); ?>
                                            </span>
                                        <?php elseif ($salary_range): ?>
                                            <span class="cn-job-meta-item cn-salary">
                                                <svg width="16" height="16" viewBox="0 0 20 21" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="10" cy="10.5" r="8.33333" stroke="currentColor"
                                                        stroke-width="1.5" />
                                                    <path d="M10 5.5V15.5" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" />
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
                                                <svg width="14" height="14" viewBox="0 0 20 21" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M1.66659 10.5C1.66659 15.1024 5.39755 18.8333 9.99992 18.8333C14.6023 18.8333 18.3333 15.1024 18.3333 10.5C18.3333 5.89763 14.6023 2.16667 9.99992 2.16667"
                                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                                    <path d="M10 8V11.3333H13.3333" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" stroke-linejoin="round" />
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
                            <?php endwhile; ?>
                        </div>

                        <!-- Pagination -->
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
                        <!-- Empty State -->
                        <div class="cn-jobs-empty">
                            <div class="cn-empty-icon">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
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

                            <?php if ($active_filters > 0): ?>
                                <a href="<?php echo esc_url(get_permalink()); ?>" class="cn-btn cn-btn-primary">
                                    <?php esc_html_e('Clear All Filters', 'careernest'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
</main>

<style>
    /* Job Listings Page Styles */
    .cn-jobs-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .cn-jobs-container {
        width: 100%;
    }

    /* Header */
    .cn-jobs-header {
        margin-bottom: 2rem;
    }

    .cn-jobs-title {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 0 0 0.5rem 0;
        color: #1a202c;
    }

    .cn-jobs-subtitle,
    .cn-jobs-results-info {
        font-size: 1.1rem;
        color: #666;
        margin: 0;
    }

    .cn-active-filters-count {
        color: #0073aa;
        font-weight: 500;
    }

    /* Layout */
    .cn-jobs-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
        align-items: start;
    }

    /* Sidebar Filters */
    .cn-jobs-sidebar {
        position: sticky;
        top: 2rem;
    }

    .cn-filters-wrapper {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
    }

    .cn-filters-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 1.5rem 0;
        color: #1a202c;
    }

    .cn-filter-group {
        margin-bottom: 1.5rem;
    }

    .cn-filter-label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .cn-filter-input,
    .cn-filter-select {
        width: 100%;
        padding: 0.625rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 0.9rem;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .cn-filter-input:focus,
    .cn-filter-select:focus {
        outline: none;
        border-color: #0073aa;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
    }

    .cn-filter-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    /* Input with Icon */
    .cn-input-with-icon {
        position: relative;
        width: 100%;
    }

    .cn-input-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #4a5568;
        pointer-events: none;
        flex-shrink: 0;
    }

    .cn-input-with-icon-field {
        padding-left: 2.5rem !important;
        min-height: 42px;
        box-sizing: border-box;
    }

    /* Salary Range Slider */
    .cn-salary-display {
        float: right;
        font-size: 0.85rem;
        color: #0073aa;
        font-weight: 600;
    }

    .cn-range-inputs {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin: 0.75rem 0;
    }

    .cn-range-slider {
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: #cbd5e0;
        outline: none;
        -webkit-appearance: none;
    }

    .cn-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #0073aa;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .cn-range-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #0073aa;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .cn-range-slider::-webkit-slider-thumb:hover {
        background: #005a87;
    }

    .cn-range-slider::-moz-range-thumb:hover {
        background: #005a87;
    }

    .cn-range-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: #718096;
        margin-top: 0.25rem;
    }

    /* Buttons */
    .cn-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border: none;
        border-radius: 4px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
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
        background: white;
        color: #0073aa;
        border: 1px solid #0073aa;
    }

    .cn-btn-secondary:hover {
        background: #f0f8ff;
    }

    .cn-btn-view-job {
        background: transparent;
        color: #0073aa;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    .cn-btn-view-job:hover {
        background: #f0f8ff;
        color: #005a87;
    }

    /* Job Cards */
    .cn-jobs-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    /* Multi-column layouts */
    .cn-jobs-columns-2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .cn-jobs-columns-3 {
        grid-template-columns: repeat(3, 1fr);
    }

    .cn-job-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        transition: box-shadow 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
    }

    .cn-job-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e0;
    }

    .cn-job-card.cn-job-filled {
        opacity: 0.7;
        background: #f7fafc;
    }

    .cn-job-card-header {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: flex-start;
        position: relative;
    }

    .cn-job-bookmark-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: transparent;
        border: none;
        padding: 0;
        cursor: pointer;
        color: #636363;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cn-job-bookmark-btn:hover {
        color: #0073aa;
        transform: scale(1.1);
    }

    .cn-job-bookmark-btn:hover svg {
        fill: var(--bde-brand-primary-color);
    }

    .cn-job-bookmark-btn:hover svg path {
        stroke: var(--bde-brand-primary-color);
    }

    .cn-job-bookmark-btn svg {
        display: block;
        width: 18px;
        height: 18px;
    }

    .cn-job-bookmark-btn.bookmarked svg {
        fill: var(--bde-brand-primary-color);
    }

    .cn-job-bookmark-btn.bookmarked svg path {
        stroke: var(--bde-brand-primary-color);
    }

    .cn-job-logo,
    .cn-job-logo-placeholder {
        width: 60px;
        height: 60px;
        flex-shrink: 0;
        border-radius: 6px;
        object-fit: contain;
    }

    .cn-job-logo-placeholder {
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.5rem;
        color: #4a5568;
    }

    .cn-job-header-content {
        flex: 1;
        min-width: 0;
    }

    .cn-job-title {
        margin: 0 0 0.25rem 0;
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .cn-job-title a {
        color: #1a202c;
        text-decoration: none;
    }

    .cn-job-title a:hover {
        color: #0073aa;
        text-decoration: underline;
    }

    .cn-job-company {
        margin: 0;
        color: #718096;
        font-size: 14px;
    }

    .cn-company-separator {
        color: #cbd5e0;
    }

    .cn-job-type-inline {
        font-size: 14px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .cn-job-type-inline.cn-job-type-part-time {
        color: #FF8200;
    }

    .cn-job-type-inline.cn-job-type-contract {
        color: #0275F4;
    }

    .cn-job-type-inline.cn-job-type-full-time {
        color: #17B86A;
    }

    .cn-job-type-inline.cn-job-type-casual {
        color: #101010;
    }

    .cn-job-status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cn-status-filled {
        background: #fed7d7;
        color: #c53030;
    }

    .cn-job-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .cn-job-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        color: #4a5568;
        font-size: 0.875rem;
    }

    .cn-job-meta-item svg {
        flex-shrink: 0;
    }

    .cn-job-meta-item.cn-salary {
        font-weight: 600;
        color: #2d3748;
    }

    .cn-remote-badge {
        display: inline-block;
        background: #c6f6d5;
        color: #22543d;
        padding: 0.125rem 0.5rem;
        border-radius: 3px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.25rem;
    }

    /* Job Type Badges */
    .cn-job-type-badge {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .cn-job-type-part-time {
        color: #FF8200;
    }

    .cn-job-type-contract {
        color: #0275F4;
    }

    .cn-job-type-full-time {
        color: #17B86A;
    }

    .cn-job-type-casual {
        color: #101010;
    }

    .cn-distance-badge {
        display: inline-block;
        background: #bee3f8;
        color: #2c5282;
        padding: 0.125rem 0.5rem;
        border-radius: 3px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.25rem;
    }

    /* Radius Filter Styles */
    .cn-radius-display {
        float: right;
        font-size: 0.85rem;
        color: #0073aa;
        font-weight: 600;
    }

    .cn-filter-hint {
        font-size: 0.75rem;
        color: #718096;
        margin: 0.5rem 0 0 0;
        font-style: italic;
    }

    /* Location Input with Button */
    .cn-location-input-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
    }

    .cn-location-input-wrapper .cn-input-with-icon {
        flex: 1;
        position: relative;
    }

    .cn-input-with-chips {
        padding-right: 3.5rem !important;
    }

    /* Distance Chip inside location input */
    .cn-distance-chip {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: #bee3f8;
        color: #2c5282;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        z-index: 1;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .cn-distance-chip:hover {
        background: #90cdf4;
        color: #2a4365;
    }

    .cn-distance-chip-text {
        display: inline-block;
    }

    .cn-distance-chip-close {
        display: inline-flex;
        align-items: center;
        margin-left: 0.125rem;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .cn-distance-chip-close:hover {
        opacity: 1;
    }

    .cn-get-location-btn {
        flex-shrink: 0;
        padding: 0.625rem;
        background: white;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        min-width: 42px;
        min-height: 42px;
    }

    .cn-get-location-btn:hover {
        background: #f0f8ff;
        border-color: #0073aa;
        color: #0073aa;
    }

    .cn-get-location-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .cn-get-location-btn.cn-location-active {
        background: #c6f6d5;
        color: #22543d;
        border-color: #22543d;
    }

    .cn-get-location-btn svg {
        display: block;
    }

    .cn-get-location-btn.cn-loading {
        opacity: 0.6;
        cursor: wait;
    }

    .cn-get-location-btn.cn-loading svg {
        animation: cn-spin 1s linear infinite;
    }

    .cn-job-excerpt {
        color: #4a5568;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .cn-job-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        margin-top: auto;
    }

    .cn-job-expiry {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .cn-expiry-normal {
        color: #718096;
    }

    .cn-expiry-warning {
        color: #d69e2e;
    }

    .cn-expiry-urgent {
        color: #dd6b20;
    }

    .cn-expiry-expired {
        color: #e53e3e;
    }

    .cn-job-closing-date {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #D83636;
    }

    /* Pagination */
    .cn-pagination {
        margin-top: 3rem;
    }

    .cn-pagination ul {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .cn-pagination li {
        margin: 0;
    }

    .cn-pagination a,
    .cn-pagination span {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        min-width: 2.5rem;
        text-align: center;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        text-decoration: none;
        color: #4a5568;
        transition: all 0.2s;
    }

    .cn-pagination a:hover {
        background: #0073aa;
        color: white;
        border-color: #0073aa;
    }

    .cn-pagination .current {
        background: #0073aa;
        color: white;
        border-color: #0073aa;
        font-weight: 600;
    }

    /* Empty State */
    .cn-jobs-empty {
        text-align: center;
        padding: 4rem 2rem;
        background: #f7fafc;
        border-radius: 8px;
    }

    .cn-empty-icon {
        margin-bottom: 1.5rem;
    }

    .cn-empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 1rem 0;
        color: #2d3748;
    }

    .cn-empty-message {
        font-size: 1rem;
        color: #718096;
        margin: 0 0 2rem 0;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Loading Spinner */
    .cn-loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        z-index: 10;
    }

    .cn-loading-spinner .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #0073aa;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: cn-spin 1s linear infinite;
        margin: 0 auto 1rem;
    }

    @keyframes cn-spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .cn-loading-spinner p {
        color: #4a5568;
        font-weight: 500;
    }

    /* AJAX Error Message */
    .cn-ajax-error {
        background: #fed7d7;
        border: 1px solid #fc8181;
        color: #c53030;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .cn-ajax-error strong {
        font-weight: 600;
    }

    /* View Toggle */
    .cn-view-toggle {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        justify-content: flex-end;
    }

    .cn-view-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1rem;
        background: white;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        color: #4a5568;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .cn-view-toggle-btn:hover {
        background: #f0f8ff;
        border-color: #0073aa;
        color: #0073aa;
    }

    .cn-view-toggle-btn.active {
        background: #0073aa;
        border-color: #0073aa;
        color: white;
    }

    .cn-view-toggle-btn svg {
        width: 20px;
        height: 20px;
    }

    /* Map View Styles */
    .cn-jobs-map-container {
        width: 100%;
        height: 600px;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .cn-jobs-map {
        width: 100%;
        height: 100%;
    }

    /* Map Info Window Styles */
    .cn-map-info-window {
        padding: 0.5rem;
        max-width: 300px;
    }

    .cn-map-info-header {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .cn-map-info-logo {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .cn-map-info-logo-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
        color: #4a5568;
        flex-shrink: 0;
    }

    .cn-map-info-content {
        flex: 1;
        min-width: 0;
    }

    .cn-map-info-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        color: #1a202c;
        line-height: 1.3;
    }

    .cn-map-info-company {
        font-size: 0.875rem;
        color: #718096;
        margin: 0;
    }

    .cn-map-info-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
        color: #4a5568;
    }

    .cn-map-info-meta-item {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .cn-map-info-meta-item svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    .cn-map-info-link {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.75rem;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.2s;
    }

    .cn-map-info-link:hover {
        background: #005a87;
        color: white;
    }

    .cn-map-info-link svg {
        width: 12px;
        height: 12px;
    }

    /* Jobs Main Container Position */
    .cn-jobs-main {
        position: relative;
        min-height: 300px;
    }

    /* Filter Position: Right Sidebar */
    .cn-filter-position-right {
        grid-template-columns: 1fr 280px;
    }

    .cn-filter-position-right .cn-jobs-sidebar {
        order: 2;
    }

    .cn-filter-position-right .cn-jobs-main {
        order: 1;
    }

    /* Filter Position: Top Bar */
    .cn-filter-position-top {
        grid-template-columns: 1fr;
    }

    .cn-filter-position-top .cn-jobs-sidebar {
        position: static;
        max-width: 100%;
    }

    .cn-filter-position-top .cn-filters-wrapper {
        padding: 1.25rem;
    }

    .cn-filter-position-top .cn-jobs-filters {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .cn-filter-position-top .cn-filters-title {
        grid-column: 1 / -1;
        margin-bottom: 1rem;
    }

    .cn-filter-position-top .cn-filter-group {
        margin-bottom: 0;
    }

    .cn-filter-position-top .cn-filter-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: center;
        margin-top: 0.5rem;
    }

    .cn-filter-position-top .cn-filter-actions .cn-btn {
        flex: 0 0 auto;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .cn-jobs-columns-3 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .cn-jobs-page {
            padding: 1rem 0.5rem;
        }

        .cn-jobs-title {
            font-size: 1.75rem;
        }

        .cn-jobs-layout,
        .cn-filter-position-right {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .cn-jobs-columns-2,
        .cn-jobs-columns-3 {
            grid-template-columns: 1fr;
        }

        .cn-jobs-sidebar {
            position: static;
        }

        .cn-filter-position-right .cn-jobs-sidebar {
            order: 1;
        }

        .cn-filter-position-right .cn-jobs-main {
            order: 2;
        }

        .cn-filter-position-top .cn-jobs-filters {
            grid-template-columns: 1fr;
        }

        .cn-filter-position-top .cn-filter-actions {
            flex-direction: column;
        }

        .cn-filters-wrapper {
            padding: 1rem;
        }

        .cn-job-card {
            padding: 1rem;
        }

        .cn-job-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .cn-job-card-meta {
            flex-direction: column;
            gap: 0.75rem;
        }

        .cn-job-card-footer {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .cn-btn-view-job {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php get_footer(); ?>
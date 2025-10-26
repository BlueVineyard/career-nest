<?php

namespace CareerNest\Shortcodes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jobs by Category Shortcode Handler
 * 
 * Handles the [careernest_jobs_by_category] shortcode which displays
 * a tabbed interface with jobs grouped by categories.
 */
class JobsByCategory
{
    /**
     * Register the shortcode
     */
    public static function register(): void
    {
        add_shortcode('careernest_jobs_by_category', [__CLASS__, 'render']);
    }

    /**
     * Render the jobs by category shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render($atts): string
    {
        // Enqueue assets
        self::enqueue_assets();

        // Parse attributes with defaults
        $atts = shortcode_atts([
            'title' => 'Jobs by Category',
            'show_title' => 'no',
            'categories' => '', // Comma-separated category IDs, empty = all
            'jobs_per_category' => '6',
        ], $atts, 'careernest_jobs_by_category');

        // Get categories
        $categories = self::get_categories($atts['categories']);

        if (empty($categories)) {
            return '<div class="cn-jobs-tabs-empty">No categories found.</div>';
        }

        // Get job listings page URL
        $pages = get_option('careernest_pages', []);
        $jobs_page_url = isset($pages['jobs']) ? get_permalink($pages['jobs']) : home_url('/jobs/');

        ob_start();
?>
        <div class="cn-jobs-by-category-wrapper">
            <?php if ($atts['show_title'] === 'yes' && !empty($atts['title'])): ?>
                <h3 class="cn-jobs-tabs-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <!-- Category Tabs -->
            <div class="cn-jobs-tabs">
                <div class="cn-tabs-nav">
                    <!-- All Tab -->
                    <button type="button" class="cn-tab-btn active" data-category="all">
                        All Jobs
                        <span class="cn-tab-count">(<?php echo esc_html(wp_count_posts('job_listing')->publish); ?>)</span>
                    </button>

                    <?php foreach ($categories as $index => $category): ?>
                        <button type="button" class="cn-tab-btn" data-category="<?php echo esc_attr($category->term_id); ?>">
                            <?php echo esc_html($category->name); ?>
                            <span class="cn-tab-count">(<?php echo esc_html($category->count); ?>)</span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="cn-tabs-content">
                    <!-- All Jobs Tab Pane -->
                    <div class="cn-tab-pane active" data-category="all">
                        <?php
                        $all_jobs = self::get_all_jobs((int) $atts['jobs_per_category']);

                        if (!empty($all_jobs)): ?>
                            <div class="cn-jobs-grid">
                                <?php foreach ($all_jobs as $job): ?>
                                    <?php echo self::render_job_card($job); ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="cn-view-more-wrapper">
                                <a href="<?php echo esc_url($jobs_page_url); ?>" class="cn-btn-view-more">
                                    View All Jobs
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="cn-tab-empty">
                                <p>No jobs available.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($categories as $index => $category): ?>
                        <div class="cn-tab-pane" data-category="<?php echo esc_attr($category->term_id); ?>">
                            <?php
                            $jobs = self::get_category_jobs($category->term_id, (int) $atts['jobs_per_category']);

                            if (!empty($jobs)): ?>
                                <div class="cn-jobs-grid">
                                    <?php foreach ($jobs as $job): ?>
                                        <?php echo self::render_job_card($job); ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="cn-view-more-wrapper">
                                    <a href="<?php echo esc_url($jobs_page_url . '?job_category=' . $category->term_id); ?>"
                                        class="cn-btn-view-more">
                                        View More <?php echo esc_html($category->name); ?> Jobs
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="cn-tab-empty">
                                    <p>No jobs available in this category.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Get categories for tabs
     * 
     * @param string $category_ids Comma-separated category IDs
     * @return array Category terms
     */
    private static function get_categories(string $category_ids): array
    {
        $args = [
            'taxonomy' => 'job_category',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
        ];

        // If specific categories specified, filter by IDs
        if (!empty($category_ids)) {
            $ids = array_map('trim', explode(',', $category_ids));
            $ids = array_filter($ids, 'is_numeric');
            if (!empty($ids)) {
                $args['include'] = $ids;
            }
        } else {
            // Limit to top 5 categories by default
            $args['number'] = 5;
        }

        $terms = get_terms($args);

        return (is_wp_error($terms) || empty($terms)) ? [] : $terms;
    }

    /**
     * Get jobs for a specific category
     * 
     * @param int $category_id Category term ID
     * @param int $limit Number of jobs to fetch
     * @return array Job posts
     */
    private static function get_category_jobs(int $category_id, int $limit): array
    {
        $query = new \WP_Query([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'tax_query' => [
                [
                    'taxonomy' => 'job_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_closing_date',
            'meta_type' => 'DATE',
            'order' => 'ASC',
        ]);

        $jobs = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $jobs[] = get_post();
            }
            wp_reset_postdata();
        }

        return $jobs;
    }

    /**
     * Get all jobs (for "All" tab)
     * 
     * @param int $limit Number of jobs to fetch
     * @return array Job posts
     */
    private static function get_all_jobs(int $limit): array
    {
        $query = new \WP_Query([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'meta_value',
            'meta_key' => '_closing_date',
            'meta_type' => 'DATE',
            'order' => 'ASC',
        ]);

        $jobs = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $jobs[] = get_post();
            }
            wp_reset_postdata();
        }

        return $jobs;
    }

    /**
     * Render a single job card
     * 
     * @param \WP_Post $job Job post object
     * @return string HTML output
     */
    private static function render_job_card(\WP_Post $job): string
    {
        $job_id = $job->ID;
        $employer_id = get_post_meta($job_id, '_employer_id', true);
        $employer_id = $employer_id ? (int) $employer_id : 0;

        // Get company info
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

        // Get job type
        $job_types_terms = get_the_terms($job_id, 'job_type');
        $job_type_name = '';
        $job_type_slug = '';
        if ($job_types_terms && !is_wp_error($job_types_terms)) {
            $job_type_name = $job_types_terms[0]->name;
            $job_type_slug = sanitize_title($job_type_name);
        }

        // Calculate expiry
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
            }
        }

        ob_start();
    ?>
        <article class="cn-job-card <?php echo $position_filled ? 'cn-job-filled' : ''; ?>">
            <div class="cn-job-card-header">
                <?php if ($employer_logo): ?>
                    <img src="<?php echo esc_url($employer_logo); ?>" alt="<?php echo esc_attr($company_name); ?>"
                        class="cn-job-logo">
                <?php else: ?>
                    <div class="cn-job-logo-placeholder">
                        <span><?php echo esc_html(substr($company_name ?: $job->post_title, 0, 1)); ?></span>
                    </div>
                <?php endif; ?>

                <div class="cn-job-header-content">
                    <h2 class="cn-job-title">
                        <a href="<?php echo esc_url(get_permalink($job_id)); ?>">
                            <?php echo esc_html($job->post_title); ?>
                        </a>
                    </h2>

                    <?php if ($company_name): ?>
                        <p class="cn-job-company">
                            <?php echo esc_html($company_name); ?>
                            <?php if ($job_type_name): ?>
                                <span class="cn-company-separator"> | </span>
                                <span class="cn-job-type-inline cn-job-type-<?php echo esc_attr($job_type_slug); ?>">
                                    <?php echo esc_html($job_type_name); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($position_filled): ?>
                    <span class="cn-job-status-badge cn-status-filled">Filled</span>
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
                            <span class="cn-remote-badge">Remote</span>
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

            <?php if (!empty($job->post_excerpt)): ?>
                <div class="cn-job-excerpt">
                    <?php echo wp_kses_post(wp_trim_words($job->post_excerpt, 20, '...')); ?>
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
        return ob_get_clean();
    }

    /**
     * Enqueue shortcode assets
     */
    private static function enqueue_assets(): void
    {
        // Enqueue inline JavaScript for tab switching
        wp_add_inline_script(
            'jquery',
            "
            jQuery(document).ready(function($) {
                $('.cn-tab-btn').on('click', function() {
                    const categoryId = $(this).data('category');
                    const wrapper = $(this).closest('.cn-jobs-by-category-wrapper');
                    
                    // Update active tab
                    wrapper.find('.cn-tab-btn').removeClass('active');
                    $(this).addClass('active');
                    
                    // Update active pane
                    wrapper.find('.cn-tab-pane').removeClass('active');
                    wrapper.find('.cn-tab-pane[data-category=\"' + categoryId + '\"]').addClass('active');
                });
            });
            "
        );

        // Inline CSS for the tabbed interface
        $css = "
        .cn-jobs-by-category-wrapper {
            margin: 0;
            padding: 0;
            background: transparent;
        }
        
        .cn-jobs-tabs-title {
            margin: 0 0 2rem 0;
            font-size: 1.75rem;
            color: #2c3e50;
            text-align: center;
        }
        
        /* Tabs Navigation */
        .cn-tabs-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            border-bottom: 2px solid #e4e4e4;
            padding-bottom: 0;
        }
        
        .cn-tab-btn {
            padding: 0.875rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: #666666;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: -2px;
        }
        
        .cn-tab-btn:hover {
            color: #FF8200;
        }
        
        .cn-tab-btn.active {
            color: #FF8200;
            border-bottom-color: #FF8200;
        }
        
        .cn-tab-count {
            font-size: 13px;
            font-weight: 400;
            color: #999999;
            margin-left: 0.25rem;
        }
        
        .cn-tab-btn.active .cn-tab-count {
            color: #FF8200;
        }
        
        /* Tab Content */
        .cn-tabs-content {
            position: relative;
        }
        
        .cn-tab-pane {
            display: none;
        }
        
        .cn-tab-pane.active {
            display: block;
            animation: cn-fadeIn 0.3s ease;
        }
        
        @keyframes cn-fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Jobs Grid */
        .cn-jobs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* View More Button */
        .cn-view-more-wrapper {
            text-align: center;
            margin-top: 2rem;
        }
        
        .cn-btn-view-more {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            background: #FF8200;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cn-btn-view-more:hover {
            background: #e37600;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 130, 0, 0.3);
        }
        
        /* Empty state */
        .cn-tab-empty {
            padding: 3rem;
            text-align: center;
            color: #718096;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .cn-jobs-tabs-empty {
            padding: 2rem;
            text-align: center;
            color: #718096;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        /* Job Card Styles */
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
            color: #FF8200;
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
        
        .cn-job-closing-date {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #D83636;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .cn-jobs-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .cn-tabs-nav {
                overflow-x: auto;
                overflow-y: hidden;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            
            .cn-tab-btn {
                white-space: nowrap;
                padding: 0.75rem 1.25rem;
                font-size: 14px;
            }
            
            .cn-jobs-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cn-job-card {
                padding: 1rem;
            }
        }
        ";

        wp_add_inline_style('wp-block-library', $css);
    }
}

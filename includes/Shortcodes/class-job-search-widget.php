<?php

namespace CareerNest\Shortcodes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job Search Widget Shortcode Handler
 * 
 * Handles the [careernest_job_search] shortcode which displays a compact
 * job search form with keyword search, category filter, and location input.
 */
class JobSearchWidget
{
    /**
     * Register the shortcode
     */
    public static function register(): void
    {
        add_shortcode('careernest_job_search', [__CLASS__, 'render']);
    }

    /**
     * Render the job search widget shortcode
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
            'title' => 'Find Your Next Job',
            'placeholder_search' => 'Job title or keywords...',
            'placeholder_location' => 'Location...',
            'button_text' => 'Search Jobs',
            'show_title' => 'yes',
        ], $atts, 'careernest_job_search');

        // Get job listings page URL
        $pages = get_option('careernest_pages', []);
        $jobs_page_url = isset($pages['jobs']) ? get_permalink($pages['jobs']) : home_url('/jobs/');

        // Get job categories
        $categories = get_terms([
            'taxonomy' => 'job_category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($categories)) {
            $categories = [];
        }

        ob_start();
?>
        <div class="cn-job-search-widget">
            <?php if ($atts['show_title'] === 'yes' && !empty($atts['title'])): ?>
                <h3 class="cn-search-widget-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <form class="cn-search-widget-form" method="get" action="<?php echo esc_url($jobs_page_url); ?>">
                <div class="cn-search-widget-fields">
                    <!-- Search Field -->
                    <div class="cn-search-field-wrapper">
                        <label for="cn-widget-search" class="screen-reader-text">Search Jobs</label>
                        <div class="cn-input-with-icon">
                            <svg class="cn-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="text" id="cn-widget-search" name="job_search" class="cn-search-widget-input"
                                placeholder="<?php echo esc_attr($atts['placeholder_search']); ?>"
                                value="<?php echo esc_attr(get_query_var('job_search')); ?>" />
                        </div>
                    </div>

                    <!-- Category Dropdown -->
                    <div class="cn-search-field-wrapper">
                        <label for="cn-widget-category" class="screen-reader-text">Job Category</label>
                        <div class="cn-custom-select-wrapper" data-icon="folder">
                            <select id="cn-widget-category" name="job_category" class="cn-custom-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>"
                                        <?php selected(get_query_var('job_category'), $category->term_id); ?>>
                                        <?php echo esc_html($category->name); ?>
                                        <?php if ($category->count > 0): ?>
                                            (<?php echo esc_html($category->count); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Location Field -->
                    <div class="cn-search-field-wrapper">
                        <label for="cn-widget-location" class="screen-reader-text">Location</label>
                        <div class="cn-input-with-icon">
                            <svg class="cn-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="text" id="cn-widget-location" name="job_location" class="cn-search-widget-input"
                                placeholder="<?php echo esc_attr($atts['placeholder_location']); ?>"
                                value="<?php echo esc_attr(get_query_var('job_location')); ?>" />
                        </div>
                    </div>

                    <!-- Search Button -->
                    <div class="cn-search-field-wrapper cn-search-button-wrapper">
                        <button type="submit" class="cn-search-widget-button">
                            <span class="cn-search-text"><?php echo esc_html($atts['button_text']); ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Enqueue shortcode assets
     */
    private static function enqueue_assets(): void
    {
        // Enqueue custom dropdown styles
        wp_enqueue_style(
            'careernest-custom-dropdown',
            CAREERNEST_URL . 'assets/css/custom-dropdown.css',
            [],
            CAREERNEST_VERSION
        );

        // Enqueue custom dropdown script
        wp_enqueue_script(
            'careernest-custom-dropdown',
            CAREERNEST_URL . 'assets/js/custom-dropdown.js',
            ['jquery'],
            CAREERNEST_VERSION,
            true
        );

        // Inline CSS for the widget
        $css = "
        .cn-job-search-widget {
            background: #ffffff;
            padding: 28px 32px;
            border-radius: 8px;
            box-shadow: 0px 4px 40px 0px rgba(0, 0, 0, 0.06);
        }
        
        .cn-search-widget-title {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            color: #000000ff;
            text-align: center;
        }
        
        .cn-search-widget-form {
            max-width: 100%;
        }
        
        .cn-search-widget-fields {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .cn-search-field-wrapper {
            display: flex;
            flex-direction: column;
        }
        
        .cn-input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .cn-input-icon {
            position: absolute;
            left: 0.875rem;
            color: #718096;
            pointer-events: none;
            z-index: 1;
        }
        
        .cn-input-with-icon .cn-search-widget-input {
            padding-left: 2.75rem;
        }
        
        .cn-search-widget-input,
        .cn-search-widget-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            transition: border-color 0.3s ease;
        }
        
        .cn-search-widget-input:focus,
        .cn-search-widget-select:focus {
            outline: none;
            border-color: rgba(255, 130, 0, 1);
            box-shadow: 0 0 0 3px rgba(255, 130, 0, 0.1);
        }
        
        .cn-search-widget-select {
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"%3e%3cpolyline points=\"6 9 12 15 18 9\"%3e%3c/polyline%3e%3c/svg%3e');
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }
        
        .cn-search-widget-button {
            width: 100%;
            padding: 0.875rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: #FF8200;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .cn-search-widget-button:hover {
            background: #e37600ff;
        }
        
        .cn-search-widget-button:active {
            transform: translateY(1px);
        }
        
        .cn-search-icon {
            font-size: 1.1rem;
        }
        
        .screen-reader-text {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        /* Tablet and up */
        @media (min-width: 768px) {
            .cn-search-widget-fields {
                grid-template-columns: 2fr 1.5fr 1.5fr auto;
                align-items: end;
            }
            
            .cn-search-widget-button {
                white-space: nowrap;
            }
        }
        
        /* Mobile optimization */
        @media (max-width: 767px) {
            .cn-job-search-widget {
                padding: 1.5rem 1rem;
            }
            
            .cn-search-widget-title {
                font-size: 1.25rem;
            }
            
            .cn-search-text {
                display: inline;
            }
        }
        ";

        wp_add_inline_style('wp-block-library', $css);
    }
}

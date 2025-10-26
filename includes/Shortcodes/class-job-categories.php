<?php

namespace CareerNest\Shortcodes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job Categories Grid Shortcode Handler
 * 
 * Handles the [careernest_job_categories] shortcode which displays
 * a grid of job categories with their job counts.
 */
class JobCategories
{
    /**
     * Register the shortcode
     */
    public static function register(): void
    {
        add_shortcode('careernest_job_categories', [__CLASS__, 'render']);
    }

    /**
     * Render the job categories grid shortcode
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
            'title' => 'Browse by Category',
            'show_title' => 'no',
            'limit' => '6',
            'columns' => '3',
        ], $atts, 'careernest_job_categories');

        // Get categories with job counts
        $categories = self::get_categories_with_counts($atts['limit']);

        if (empty($categories)) {
            return '<div class="cn-categories-empty">No categories found.</div>';
        }

        // Get job listings page URL
        $pages = get_option('careernest_pages', []);
        $jobs_page_url = isset($pages['jobs']) ? get_permalink($pages['jobs']) : home_url('/jobs/');

        ob_start();
?>
        <div class="cn-job-categories-wrapper">
            <?php if ($atts['show_title'] === 'yes' && !empty($atts['title'])): ?>
                <h3 class="cn-categories-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <div class="cn-categories-grid cn-categories-columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($categories as $category): ?>
                    <a href="<?php echo esc_url($jobs_page_url . '?job_category=' . $category['id']); ?>" class="cn-category-card">
                        <div class="cn-category-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3 7C3 5.89543 3.89543 5 5 5H9.58579C9.851 5 10.1054 5.10536 10.2929 5.29289L12 7H19C20.1046 7 21 7.89543 21 9V17C21 18.1046 20.1046 19 19 19H5C3.89543 19 3 18.1046 3 17V7Z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="cn-category-info">
                            <h4 class="cn-category-name"><?php echo esc_html($category['name']); ?></h4>
                            <p class="cn-category-count">
                                <?php
                                if ($category['count'] > 0) {
                                    echo esc_html($category['count'] . ' Job' . ($category['count'] > 1 ? 's' : ''));
                                } else {
                                    echo esc_html('No Jobs');
                                }
                                ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Get categories with job counts
     * 
     * @param int $limit Number of categories to retrieve
     * @return array Array of category data
     */
    private static function get_categories_with_counts(int $limit): array
    {
        // Get all job categories ordered by count
        $terms = get_terms([
            'taxonomy' => 'job_category',
            'hide_empty' => false,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $limit,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $categories = [];
        foreach ($terms as $term) {
            $categories[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'count' => $term->count,
                'slug' => $term->slug,
            ];
        }

        return $categories;
    }

    /**
     * Enqueue shortcode assets
     */
    private static function enqueue_assets(): void
    {
        // Inline CSS for the categories grid
        $css = "
        .cn-job-categories-wrapper {
            margin: 0;
            padding: 0;
            background: transparent;
        }
        
        .cn-categories-title {
            margin: 0 0 2rem 0;
            font-size: 1.75rem;
            color: #2c3e50;
            text-align: center;
        }
        
        .cn-categories-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1fr;
        }
        
        .cn-categories-columns-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .cn-categories-columns-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .cn-categories-columns-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .cn-category-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e4e4e4;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .cn-category-card:hover {
            border-color: #FF8200;
            box-shadow: 0 4px 12px rgba(255, 130, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .cn-category-icon {
            flex-shrink: 0;
            width: 62px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: #ffffff;
            background: #FF8200;
            border: 1px solid #FAB368;
            box-shadow: 0px -4px 8px 0px #FFFFFFA3 inset;
        }
        
        .cn-category-info {
            flex: 1;
            min-width: 0;
        }
        
        .cn-category-name {
            font-size: 16px;
            color: #101010;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        
        .cn-category-count {
            font-size: 14px;
            color: #666666;
            margin: 0;
        }
        
        /* Empty state */
        .cn-categories-empty {
            padding: 2rem;
            text-align: center;
            color: #718096;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        /* Tablet responsiveness */
        @media (max-width: 1024px) {
            .cn-categories-columns-4 {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .cn-categories-columns-2,
            .cn-categories-columns-3,
            .cn-categories-columns-4 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .cn-categories-grid {
                gap: 1rem;
            }
            
            .cn-category-card {
                padding: 1rem;
            }
            
            .cn-category-icon {
                width: 40px;
                height: 40px;
            }
            
            .cn-category-icon svg {
                width: 24px;
                height: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .cn-categories-columns-2,
            .cn-categories-columns-3,
            .cn-categories-columns-4 {
                grid-template-columns: 1fr;
            }
        }
        ";

        wp_add_inline_style('wp-block-library', $css);
    }
}

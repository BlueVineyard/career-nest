<?php

namespace CareerNest\Shortcodes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Employer Carousel Shortcode Handler
 * 
 * Handles the [careernest_employer_carousel] shortcode which displays
 * a carousel of employers with their logos and job counts.
 */
class EmployerCarousel
{
    /**
     * Register the shortcode
     */
    public static function register(): void
    {
        add_shortcode('careernest_employer_carousel', [__CLASS__, 'render']);
    }

    /**
     * Render the employer carousel shortcode
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
            'title' => 'Featured Employers',
            'show_title' => 'no',
            'slides_per_view' => '4',
            'autoplay' => 'yes',
            'autoplay_delay' => '3000',
        ], $atts, 'careernest_employer_carousel');

        // Get all employers with job counts
        $employers = self::get_employers_with_job_counts();

        if (empty($employers)) {
            return '<div class="cn-employer-carousel-empty">No employers found.</div>';
        }

        // Get job listings page URL
        $pages = get_option('careernest_pages', []);
        $jobs_page_url = isset($pages['jobs']) ? get_permalink($pages['jobs']) : home_url('/jobs/');

        ob_start();
?>
        <div class="cn-employer-carousel-wrapper">
            <?php if ($atts['show_title'] === 'yes' && !empty($atts['title'])): ?>
                <h3 class="cn-carousel-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <div class="cn-employer-carousel swiper" data-slides="<?php echo esc_attr($atts['slides_per_view']); ?>"
                data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
                data-autoplay-delay="<?php echo esc_attr($atts['autoplay_delay']); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($employers as $employer): ?>
                        <div class="swiper-slide">
                            <a href="<?php echo esc_url($jobs_page_url . '?employer_id=' . $employer['id']); ?>"
                                class="cn-employer-card">
                                <div class="cn-employer-logo">
                                    <?php if ($employer['logo']): ?>
                                        <img src="<?php echo esc_url($employer['logo']); ?>"
                                            alt="<?php echo esc_attr($employer['name']); ?>">
                                    <?php else: ?>
                                        <div class="cn-employer-logo-placeholder">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M3 21H21M5 21V7L13 3V21M19 21V11L13 8M9 9V9.01M9 12V12.01M9 15V15.01M9 18V18.01"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="cn-employer-info">
                                    <h4 class="cn-employer-name"><?php echo esc_html($employer['name']); ?></h4>
                                    <p class="cn-employer-jobs">
                                        <?php
                                        if ($employer['job_count'] > 0) {
                                            echo esc_html($employer['job_count'] . ' Job' . ($employer['job_count'] > 1 ? 's' : '') . ' Available');
                                        } else {
                                            echo esc_html('No Jobs Available');
                                        }
                                        ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Navigation buttons -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>

                <!-- Pagination -->
                <div class="swiper-pagination"></div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Get employers with their job counts, ordered by job count
     * 
     * @return array Array of employer data
     */
    private static function get_employers_with_job_counts(): array
    {
        // Get all published employers
        $employer_query = new \WP_Query([
            'post_type' => 'employer',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $employers = [];

        if ($employer_query->have_posts()) {
            while ($employer_query->have_posts()) {
                $employer_query->the_post();
                $employer_id = get_the_ID();

                // Get job count for this employer
                $job_count = self::get_employer_job_count($employer_id);

                // Get company logo - try featured image first, then meta field
                $logo_url = '';
                if (has_post_thumbnail($employer_id)) {
                    $logo_url = get_the_post_thumbnail_url($employer_id, 'thumbnail');
                } else {
                    $logo_id = get_post_meta($employer_id, '_company_logo', true);
                    if ($logo_id) {
                        $logo_url = wp_get_attachment_image_url($logo_id, 'thumbnail');
                    }
                }

                $employers[] = [
                    'id' => $employer_id,
                    'name' => get_the_title(),
                    'logo' => $logo_url,
                    'job_count' => $job_count,
                ];
            }
            wp_reset_postdata();
        }

        // Sort employers: those with jobs first, then alphabetically within each group
        usort($employers, function ($a, $b) {
            // First, sort by job count (descending)
            if ($a['job_count'] != $b['job_count']) {
                return $b['job_count'] - $a['job_count'];
            }
            // Then sort alphabetically by name
            return strcmp($a['name'], $b['name']);
        });

        return $employers;
    }

    /**
     * Get job count for an employer
     * 
     * @param int $employer_id Employer post ID
     * @return int Number of active jobs
     */
    private static function get_employer_job_count(int $employer_id): int
    {
        $job_query = new \WP_Query([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_employer_id',
                    'value' => $employer_id,
                    'compare' => '='
                ]
            ],
        ]);

        return $job_query->found_posts;
    }

    /**
     * Enqueue shortcode assets
     */
    private static function enqueue_assets(): void
    {
        // Check if Swiper CSS is already registered/enqueued
        if (!wp_style_is('swiper', 'registered') && !wp_style_is('swiper', 'enqueued')) {
            wp_enqueue_style(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                [],
                '11.0.0'
            );
        } else {
            // If already registered but not enqueued, enqueue it
            if (!wp_style_is('swiper', 'enqueued')) {
                wp_enqueue_style('swiper');
            }
        }

        // Check if Swiper JS is already registered/enqueued
        if (!wp_script_is('swiper', 'registered') && !wp_script_is('swiper', 'enqueued')) {
            wp_enqueue_script(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                [],
                '11.0.0',
                true
            );
        } else {
            // If already registered but not enqueued, enqueue it
            if (!wp_script_is('swiper', 'enqueued')) {
                wp_enqueue_script('swiper');
            }
        }

        // Enqueue carousel initialization script
        wp_add_inline_script(
            'swiper',
            "
            document.addEventListener('DOMContentLoaded', function() {
                const carousels = document.querySelectorAll('.cn-employer-carousel');
                
                carousels.forEach(function(carousel) {
                    const slidesPerView = parseInt(carousel.dataset.slides) || 4;
                    const autoplay = carousel.dataset.autoplay === 'yes';
                    const autoplayDelay = parseInt(carousel.dataset.autoplayDelay) || 3000;
                    
                    new Swiper(carousel, {
                        slidesPerView: 1,
                        spaceBetween: 20,
                        loop: true,
                        autoplay: autoplay ? {
                            delay: autoplayDelay,
                            disableOnInteraction: false,
                        } : false,
                        pagination: {
                            el: carousel.querySelector('.swiper-pagination'),
                            clickable: true,
                        },
                        navigation: {
                            nextEl: carousel.querySelector('.swiper-button-next'),
                            prevEl: carousel.querySelector('.swiper-button-prev'),
                        },
                        breakpoints: {
                            640: {
                                slidesPerView: 2,
                                spaceBetween: 20,
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 24,
                            },
                            1024: {
                                slidesPerView: slidesPerView,
                                spaceBetween: 30,
                            },
                        },
                    });
                });
            });
            "
        );

        // Inline CSS for the carousel
        $css = "
        .cn-employer-carousel-wrapper {
            margin: 0;
            padding: 0;
            background: transparent;
        }
        
        .cn-carousel-title {
            margin: 0 0 2rem 0;
            font-size: 1.75rem;
            color: #2c3e50;
            text-align: center;
        }
        
        .cn-employer-carousel {
            position: relative;
            overflow: hidden;
        }
        
        .cn-employer-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e4e4e4;
            text-decoration: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .cn-employer-card:hover {
            border-color: #FF8200;
            box-shadow: 0 4px 12px rgba(255, 130, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .cn-employer-logo {
            flex-shrink: 0;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .cn-employer-logo img {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        
        .cn-employer-logo-placeholder {
            color: #a0aec0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cn-employer-info {
            flex: 1;
            min-width: 0;
        }
        
        .cn-employer-name {
            font-size: 18px;
            color: #101010;
            font-weight: 600;
            margin-top: 0px;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cn-employer-jobs {
            background-color: #e8fff4;
            color: #17b86a;
            border-radius: 300px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin: 0;
        }
        
        /* Swiper Navigation */
        .cn-employer-carousel .swiper-button-prev,
        .cn-employer-carousel .swiper-button-next {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .cn-employer-carousel .swiper-button-prev:after,
        .cn-employer-carousel .swiper-button-next:after {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .cn-employer-carousel .swiper-button-prev:hover,
        .cn-employer-carousel .swiper-button-next:hover {
            border-color: #FF8200;
            background: #FF8200;
        }
        
        .cn-employer-carousel .swiper-button-prev:hover:after,
        .cn-employer-carousel .swiper-button-next:hover:after {
            color: white;
        }
        
        .cn-employer-carousel .swiper-button-prev.swiper-button-disabled,
        .cn-employer-carousel .swiper-button-next.swiper-button-disabled {
            opacity: 0.35;
        }
        
        /* Swiper Pagination */
        .cn-employer-carousel .swiper-pagination {
            bottom: 0;
        }
        
        .cn-employer-carousel .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
            background: #cbd5e0;
            opacity: 1;
        }
        
        .cn-employer-carousel .swiper-pagination-bullet-active {
            background: #FF8200;
        }
        
        /* Empty state */
        .cn-employer-carousel-empty {
            padding: 2rem;
            text-align: center;
            color: #718096;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        /* Mobile optimization */
        @media (max-width: 767px) {
            .cn-employer-carousel-wrapper {
                padding: 1rem;
            }
            
            .cn-carousel-title {
                font-size: 1.5rem;
            }
            
            .cn-employer-card {
                padding: 1rem;
            }
            
            .cn-employer-logo {
                width: 50px;
                height: 50px;
            }
        }
        ";

        wp_add_inline_style('swiper', $css);
    }
}

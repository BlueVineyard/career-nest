<?php

/**
 * Template: CareerNest — Single Job Listing
 * Modern job listing layout with gradient banner design
 */

defined('ABSPATH') || exit;

// Get job meta data early for Open Graph
$job_id = get_the_ID();
$employer_id = get_post_meta($job_id, '_employer_id', true);
$employer_id = $employer_id ? (int) $employer_id : 0;

// Verify employer exists
$employer_exists = $employer_id && get_post_status($employer_id) === 'publish';
$company_name = $employer_exists ? get_the_title($employer_id) : '';
$company_initial = $company_name ? substr($company_name, 0, 1) : 'J';

// Job details
$location = get_post_meta($job_id, '_job_location', true);
$remote_position = get_post_meta($job_id, '_remote_position', true);
$salary_mode = get_post_meta($job_id, '_salary_mode', true);
$salary_range = get_post_meta($job_id, '_salary_range', true);
$salary_numeric = get_post_meta($job_id, '_salary', true);
$opening_date = get_post_meta($job_id, '_opening_date', true);
$closing_date = get_post_meta($job_id, '_closing_date', true);
$apply_externally = get_post_meta($job_id, '_apply_externally', true);
$external_apply = get_post_meta($job_id, '_external_apply', true);
$position_filled = get_post_meta($job_id, '_position_filled', true);

// Get employer data
$employer_logo = $employer_exists ? get_the_post_thumbnail_url($employer_id, 'medium') : '';
$employer_website = $employer_exists ? get_post_meta($employer_id, '_website', true) : '';
$employer_location = $employer_exists ? get_post_meta($employer_id, '_location', true) : '';
$employer_about = $employer_exists ? get_post_meta($employer_id, '_about', true) : '';
$employer_tagline = $employer_exists ? get_post_meta($employer_id, '_tagline', true) : '';
$company_size = $employer_exists ? get_post_meta($employer_id, '_company_size', true) : '';
$industry_desc = $employer_exists ? get_post_meta($employer_id, '_industry_description', true) : '';
$founded_year = $employer_exists ? get_post_meta($employer_id, '_founded_year', true) : '';

// Check employer profile completeness for linking
$employer_completeness = $employer_exists ? \CareerNest\Profile_Helper::calculate_employer_completeness($employer_id) : ['percentage' => 0];
$employer_percentage = $employer_completeness['percentage'];
$can_link_to_profile = $employer_exists && $employer_percentage >= 70;

// Check if current user has already applied
$user_already_applied = false;
$application_date = '';
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    $existing_application = new WP_Query([
        'post_type' => 'job_application',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_job_id',
                'value' => $job_id,
                'compare' => '='
            ],
            [
                'key' => '_user_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ]);

    if ($existing_application->have_posts()) {
        $user_already_applied = true;
        $application_post = $existing_application->posts[0];
        $application_date = get_post_meta($application_post->ID, '_application_date', true);
        if (!$application_date) {
            $application_date = $application_post->post_date;
        }
    }
    wp_reset_postdata();
}

// Job content sections
$job_overview = get_post_meta($job_id, '_job_overview', true);
$who_we_are = get_post_meta($job_id, '_job_who_we_are', true);
$what_we_offer = get_post_meta($job_id, '_job_what_we_offer', true);
$responsibilities = get_post_meta($job_id, '_job_responsibilities', true);
$how_to_apply = get_post_meta($job_id, '_job_how_to_apply', true);

// Get taxonomies
$job_categories = get_the_terms($job_id, 'job_category');
$job_types = get_the_terms($job_id, 'job_type');

// Add Open Graph meta tags for LinkedIn and social sharing
add_action('wp_head', function () use ($job_id, $employer_id, $company_name, $location, $job_types) {
    $job_title = get_the_title($job_id);
    $job_url = get_permalink($job_id);
    $employer_logo_large = get_the_post_thumbnail_url($employer_id, 'large');
    $job_type = ($job_types && !is_wp_error($job_types)) ? $job_types[0]->name : '';

    // Create custom share description
    $share_desc = $job_title;
    if ($company_name) {
        $share_desc .= ' at ' . $company_name;
    }
    if ($location) {
        $share_desc .= ' | ' . $location;
    }
    if ($job_type) {
        $share_desc .= ' | ' . $job_type;
    }
?>
    <meta property="og:title" content="<?php echo esc_attr($job_title); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($share_desc); ?>" />
    <meta property="og:url" content="<?php echo esc_url($job_url); ?>" />
    <meta property="og:type" content="website" />
    <?php if ($employer_logo_large): ?>
        <meta property="og:image" content="<?php echo esc_url($employer_logo_large); ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?php echo esc_attr($job_title); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr($share_desc); ?>" />
    <?php if ($employer_logo_large): ?>
        <meta name="twitter:image" content="<?php echo esc_url($employer_logo_large); ?>" />
<?php endif;
}, 5);

get_header();
?>

<main id="primary" class="site-main cn-job-listing-page">
    <div class="cn-job-container">

        <!-- Job Body -->
        <div class="cn-job-body">
            <div class="cn-job-main">
                <!-- Banner -->
                <div class="cn-job-banner"></div>

                <!-- Job Info Section -->
                <div class="cn-job-info-section">
                    <div class="cn-job-header-row">
                        <?php if ($employer_logo): ?>
                            <div class="cn-company-logo-badge">
                                <?php if ($can_link_to_profile): ?>
                                    <a href="<?php echo esc_url(get_permalink($employer_id)); ?>">
                                        <img src="<?php echo esc_url($employer_logo); ?>"
                                            alt="<?php echo esc_attr($company_name); ?>">
                                    </a>
                                <?php else: ?>
                                    <img src="<?php echo esc_url($employer_logo); ?>"
                                        alt="<?php echo esc_attr($company_name); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="cn-share-dropdown">
                            <button class="cn-share-btn" id="cn-share-toggle">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 6.65685 16.3431 8 18 8Z"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M6 15C7.65685 15 9 13.6569 9 12C9 10.3431 7.65685 9 6 9C4.34315 9 3 10.3431 3 12C3 13.6569 4.34315 15 6 15Z"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M18 22C19.6569 22 21 20.6569 21 19C21 17.3431 19.6569 16 18 16C16.3431 16 15 17.3431 15 19C15 20.6569 16.3431 22 18 22Z"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M8.59 13.51L15.42 17.49" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M15.41 6.51L8.59 10.49" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="cn-share-menu" id="cn-share-menu">
                                <?php
                                $job_url = get_permalink();
                                $job_title = get_the_title();
                                $share_text = $job_title;
                                if ($company_name) {
                                    $share_text .= ' at ' . $company_name;
                                }
                                if ($location) {
                                    $share_text .= ' | ' . $location;
                                }
                                $share_text_encoded = urlencode($share_text);
                                $job_url_encoded = urlencode($job_url);
                                ?>
                                <a href="#" class="cn-share-option" data-action="copy">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path
                                            d="M5 15H4C2.89543 15 2 14.1046 2 13V4C2 2.89543 2.89543 2 4 2H13C14.1046 2 15 2.89543 15 4V5"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>Copy Link</span>
                                </a>
                                <a href="mailto:?subject=<?php echo esc_attr($share_text); ?>&body=Check out this job: <?php echo esc_url($job_url); ?>"
                                    class="cn-share-option">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                                            stroke="currentColor" stroke-width="2" />
                                        <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>Email</span>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $job_url_encoded; ?>"
                                    target="_blank" rel="noopener" class="cn-share-option">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"
                                            stroke="currentColor" stroke-width="2" />
                                        <rect x="2" y="9" width="4" height="12" stroke="currentColor"
                                            stroke-width="2" />
                                        <circle cx="4" cy="4" r="2" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>LinkedIn</span>
                                </a>
                                <a href="https://twitter.com/intent/tweet?text=<?php echo $share_text_encoded; ?>&url=<?php echo $job_url_encoded; ?>"
                                    target="_blank" rel="noopener" class="cn-share-option">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>Twitter</span>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $job_url_encoded; ?>"
                                    target="_blank" rel="noopener" class="cn-share-option">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>Facebook</span>
                                </a>
                                <a href="https://wa.me/?text=<?php echo $share_text_encoded; ?>%20<?php echo $job_url_encoded; ?>"
                                    target="_blank" rel="noopener" class="cn-share-option">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <h1 class="cn-job-title">
                        <?php echo esc_html(get_the_title()); ?>
                        <span class="cn-applicants-count">
                            <?php
                            // Get applicant count for this job
                            $applicant_count = new WP_Query([
                                'post_type' => 'job_application',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'fields' => 'ids',
                                'meta_query' => [
                                    [
                                        'key' => '_job_id',
                                        'value' => $job_id,
                                        'compare' => '='
                                    ]
                                ]
                            ]);
                            $count = $applicant_count->found_posts;
                            wp_reset_postdata();
                            ?>
                            • <?php echo esc_html($count . ' of 15 filled'); ?>
                        </span>
                    </h1>

                    <?php if ($company_name): ?>
                        <div class="cn-job-meta-item cn-company-meta-row">
                            <span>
                                <?php if ($can_link_to_profile): ?>
                                    <a href="<?php echo esc_url(get_permalink($employer_id)); ?>"
                                        class="cn-company-link"><?php echo esc_html($company_name); ?></a>
                                <?php elseif ($employer_website): ?>
                                    <a href="<?php echo esc_url($employer_website); ?>" target="_blank"
                                        rel="noopener noreferrer"
                                        class="cn-company-link"><?php echo esc_html($company_name); ?></a>
                                <?php else: ?>
                                    <span class="cn-company-link"><?php echo esc_html($company_name); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php
                            $pages = get_option('careernest_pages', []);
                            $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                            if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'):
                                $jobs_url = add_query_arg('employer_id', $employer_id, get_permalink($jobs_page_id));
                            ?>
                                <a href="<?php echo esc_url($jobs_url); ?>" class="cn-view-all-jobs-link">View all jobs</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($location): ?>
                        <div class="cn-job-meta-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4 10.1433C4 5.64588 7.58172 2 12 2C16.4183 2 20 5.64588 20 10.1433C20 14.6055 17.4467 19.8124 13.4629 21.6744C12.5343 22.1085 11.4657 22.1085 10.5371 21.6744C6.55332 19.8124 4 14.6055 4 10.1433Z"
                                    stroke="currentColor" stroke-width="1.5" />
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <span>
                                <?php echo esc_html($location); ?>
                                <?php if ($remote_position): ?>
                                    <span class="cn-remote-badge">Remote</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($job_types && !is_wp_error($job_types)):
                        $type_slug = $job_types[0]->slug;
                        $type_color = '#17B86A'; // Default green for full-time
                        switch ($type_slug) {
                            case 'part-time':
                                $type_color = '#856404';
                                break;
                            case 'contract':
                                $type_color = '#0c5460';
                                break;
                            case 'temporary':
                                $type_color = '#721c24';
                                break;
                            case 'internship':
                                $type_color = '#383d41';
                                break;
                            case 'freelance':
                                $type_color = '#6c5ce7';
                                break;
                        }
                    ?>
                        <div class="cn-job-meta-item" style="color: <?php echo esc_attr($type_color); ?>;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.97883 9.68508C2.99294 8.89073 2 8.49355 2 8C2 7.50645 2.99294 7.10927 4.97883 6.31492L7.7873 5.19153C9.77318 4.39718 10.7661 4 12 4C13.2339 4 14.2268 4.39718 16.2127 5.19153L19.0212 6.31492C21.0071 7.10927 22 7.50645 22 8C22 8.49355 21.0071 8.89073 19.0212 9.68508L16.2127 10.8085C14.2268 11.6028 13.2339 12 12 12C10.7661 12 9.77318 11.6028 7.7873 10.8085L4.97883 9.68508Z"
                                    stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <span><?php echo esc_html($job_types[0]->name); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($closing_date): ?>
                        <div class="cn-job-meta-item" style="color: #D83636;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" />
                                <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <span><?php echo esc_html(date('j F Y', strtotime($closing_date))); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($salary_mode === 'numeric' && $salary_numeric): ?>
                        <div class="cn-job-meta-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" />
                                <path
                                    d="M12 6v12M15 10c0-1.5-1.34-2.5-3-2.5S9 8.5 9 10c0 1.5 1.34 2.5 3 2.5s3 1 3 2.5-1.34 2.5-3 2.5-3-1-3-2.5"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <span>$<?php echo esc_html(number_format($salary_numeric)); ?> per year</span>
                        </div>
                    <?php elseif ($salary_range): ?>
                        <div class="cn-job-meta-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" />
                                <path
                                    d="M12 6v12M15 10c0-1.5-1.34-2.5-3-2.5S9 8.5 9 10c0 1.5 1.34 2.5 3 2.5s3 1 3 2.5-1.34 2.5-3 2.5-3-1-3-2.5"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <span><?php echo esc_html($salary_range); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Apply Button -->
                    <?php if (!$user_already_applied && !$position_filled): ?>
                        <?php
                        $pages = get_option('careernest_pages', []);
                        $apply_page_id = isset($pages['apply-job']) ? (int) $pages['apply-job'] : 0;
                        ?>
                        <?php if ($apply_externally && $external_apply): ?>
                            <?php if (filter_var($external_apply, FILTER_VALIDATE_EMAIL)): ?>
                                <a href="mailto:<?php echo esc_attr($external_apply); ?>?subject=Application for <?php echo esc_attr(get_the_title()); ?>"
                                    class="cn-quick-apply-btn">
                                    Apply via Email
                                </a>
                            <?php else: ?>
                                <a href="<?php echo esc_url($external_apply); ?>" target="_blank" rel="noopener noreferrer"
                                    class="cn-quick-apply-btn">
                                    Apply Externally
                                </a>
                            <?php endif; ?>
                        <?php elseif ($apply_page_id && get_post_status($apply_page_id) === 'publish'): ?>
                            <?php $apply_url = add_query_arg('job_id', $job_id, get_permalink($apply_page_id)); ?>
                            <a href="<?php echo esc_url($apply_url); ?>" class="cn-quick-apply-btn">
                                Quick Apply Now
                            </a>
                        <?php endif; ?>
                    <?php elseif ($position_filled): ?>
                        <div class="cn-position-filled-notice">
                            ✓ Position Filled
                        </div>
                    <?php elseif ($user_already_applied): ?>
                        <div class="cn-application-status-badge">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#10B981" />
                                <path d="M8 12l3 3 5-6" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span>
                                <strong>Applied</strong>
                                <?php if ($application_date): ?>
                                    on <?php echo esc_html(date('M j, Y', strtotime($application_date))); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($job_overview): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">Job Overview</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($job_overview)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($responsibilities): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">Key Responsibilities</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($responsibilities)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($what_we_offer): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">What We Offer</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($what_we_offer)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($who_we_are): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">About the Company</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($who_we_are)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($how_to_apply): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">How to Apply</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($how_to_apply)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (get_the_content()): ?>
                    <div class="cn-job-section">
                        <h2 class="cn-section-title">Additional Information</h2>
                        <div class="cn-section-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bottom Apply Section -->
                <?php if (!$user_already_applied && !$position_filled): ?>
                    <div class="cn-bottom-apply-section">
                        <?php
                        $pages = get_option('careernest_pages', []);
                        $apply_page_id = isset($pages['apply-job']) ? (int) $pages['apply-job'] : 0;
                        ?>
                        <?php if ($apply_externally && $external_apply): ?>
                            <?php if (filter_var($external_apply, FILTER_VALIDATE_EMAIL)): ?>
                                <a href="mailto:<?php echo esc_attr($external_apply); ?>?subject=Application for <?php echo esc_attr(get_the_title()); ?>"
                                    class="cn-apply-btn">
                                    Apply via Email
                                </a>
                            <?php else: ?>
                                <a href="<?php echo esc_url($external_apply); ?>" target="_blank" rel="noopener noreferrer"
                                    class="cn-apply-btn">
                                    Apply on External Site
                                </a>
                            <?php endif; ?>
                        <?php elseif ($apply_page_id && get_post_status($apply_page_id) === 'publish'): ?>
                            <?php $apply_url = add_query_arg('job_id', $job_id, get_permalink($apply_page_id)); ?>
                            <a href="<?php echo esc_url($apply_url); ?>" class="cn-apply-btn">
                                Quick Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Job Navigation -->
                <div class="cn-job-navigation">
                    <?php
                    $prev_job = get_previous_post(true, '', 'job_category');
                    $next_job = get_next_post(true, '', 'job_category');
                    $pages = get_option('careernest_pages', []);
                    $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                    ?>

                    <div class="cn-nav-links">
                        <?php if ($prev_job): ?>
                            <a href="<?php echo esc_url(get_permalink($prev_job->ID)); ?>" class="cn-nav-prev">
                                ← Previous Job
                            </a>
                        <?php endif; ?>

                        <?php if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'): ?>
                            <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>" class="cn-nav-all">
                                All Jobs
                            </a>
                        <?php endif; ?>

                        <?php if ($next_job): ?>
                            <a href="<?php echo esc_url(get_permalink($next_job->ID)); ?>" class="cn-nav-next">
                                Next Job →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="cn-job-sidebar">
                <?php if ($employer_exists): ?>
                    <!-- Company Info Card -->
                    <div class="cn-sidebar-card cn-company-card">
                        <h3 class="cn-sidebar-title">About the Company</h3>

                        <div class="cn-company-header">
                            <?php if ($employer_logo): ?>
                                <?php if ($can_link_to_profile): ?>
                                    <a href="<?php echo esc_url(get_permalink($employer_id)); ?>">
                                        <img class="cn-company-logo-thumb" src="<?php echo esc_url($employer_logo); ?>"
                                            alt="<?php echo esc_attr($company_name); ?>">
                                    </a>
                                <?php else: ?>
                                    <img class="cn-company-logo-thumb" src="<?php echo esc_url($employer_logo); ?>"
                                        alt="<?php echo esc_attr($company_name); ?>">
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="cn-company-name-section">
                                <h4>
                                    <?php if ($can_link_to_profile): ?>
                                        <a href="<?php echo esc_url(get_permalink($employer_id)); ?>"
                                            class="cn-company-name-link"><?php echo esc_html($company_name); ?></a>
                                    <?php else: ?>
                                        <?php echo esc_html($company_name); ?>
                                    <?php endif; ?>
                                </h4>
                                <?php if ($employer_tagline): ?>
                                    <p class="cn-company-tagline"><?php echo esc_html($employer_tagline); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($employer_about): ?>
                            <div class="cn-company-description">
                                <?php echo wp_kses_post(wp_trim_words(wpautop($employer_about), 30, '...')); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Company Quick Facts -->
                        <?php if ($company_size || $industry_desc || $employer_location || $founded_year): ?>
                            <div class="cn-company-facts">
                                <?php if ($industry_desc): ?>
                                    <div class="cn-fact-item">
                                        <span class="cn-fact-label">Industry</span>
                                        <span class="cn-fact-value"><?php echo esc_html($industry_desc); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($company_size): ?>
                                    <div class="cn-fact-item">
                                        <span class="cn-fact-label">Company Size</span>
                                        <span class="cn-fact-value"><?php echo esc_html($company_size); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($employer_location): ?>
                                    <div class="cn-fact-item">
                                        <span class="cn-fact-label">Location</span>
                                        <span class="cn-fact-value"><?php echo esc_html($employer_location); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($founded_year): ?>
                                    <div class="cn-fact-item">
                                        <span class="cn-fact-label">Founded</span>
                                        <span class="cn-fact-value"><?php echo esc_html($founded_year); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Company Actions -->
                        <div class="cn-company-actions">
                            <?php if ($can_link_to_profile): ?>
                                <a href="<?php echo esc_url(get_permalink($employer_id)); ?>" class="cn-company-profile-btn">
                                    View Company Profile
                                </a>
                            <?php elseif ($employer_website): ?>
                                <a href="<?php echo esc_url($employer_website); ?>" target="_blank" rel="noopener noreferrer"
                                    class="cn-company-profile-btn">
                                    Visit Company Website
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Related Jobs -->
                <div class="cn-sidebar-card cn-related-jobs-card">
                    <h3 class="cn-sidebar-title">Related Jobs</h3>
                    <?php
                    // Get related jobs from same employer or same category
                    $related_args = [
                        'post_type' => 'job_listing',
                        'post_status' => 'publish',
                        'posts_per_page' => 3,
                        'post__not_in' => [$job_id],
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ];

                    // Try to get jobs from same employer first
                    if ($employer_exists) {
                        $related_args['meta_query'] = [
                            [
                                'key' => '_employer_id',
                                'value' => $employer_id,
                                'compare' => '='
                            ]
                        ];
                    }

                    $related_jobs = new WP_Query($related_args);

                    // If no jobs from same employer, try same category
                    if (!$related_jobs->have_posts() && $job_categories && !is_wp_error($job_categories)) {
                        $category_ids = wp_list_pluck($job_categories, 'term_id');
                        $related_args['tax_query'] = [
                            [
                                'taxonomy' => 'job_category',
                                'field' => 'term_id',
                                'terms' => $category_ids
                            ]
                        ];
                        unset($related_args['meta_query']);
                        $related_jobs = new WP_Query($related_args);
                    }

                    if ($related_jobs->have_posts()): ?>
                        <div class="cn-related-jobs-list">
                            <?php while ($related_jobs->have_posts()): $related_jobs->the_post();
                                $rel_job_id = get_the_ID();
                                $rel_location = get_post_meta($rel_job_id, '_job_location', true);
                                $rel_types = get_the_terms($rel_job_id, 'job_type');
                                $rel_salary_mode = get_post_meta($rel_job_id, '_salary_mode', true);
                                $rel_salary_numeric = get_post_meta($rel_job_id, '_salary', true);
                                $rel_salary_range = get_post_meta($rel_job_id, '_salary_range', true);
                                $rel_closing = get_post_meta($rel_job_id, '_closing_date', true);

                                // Calculate expiry
                                $expiry_text = '';
                                if ($rel_closing) {
                                    $closing_timestamp = strtotime($rel_closing . ' 23:59:59');
                                    $current_timestamp = current_time('timestamp');
                                    $days_diff = ceil(($closing_timestamp - $current_timestamp) / DAY_IN_SECONDS);

                                    if ($days_diff > 0) {
                                        $expiry_text = 'Expires in ' . $days_diff . ' day' . ($days_diff > 1 ? 's' : '');
                                    } elseif ($days_diff === 0) {
                                        $expiry_text = 'Expires today';
                                    } else {
                                        $expiry_text = 'Expired';
                                    }
                                }
                            ?>
                                <div class="cn_related_job_card">
                                    <div class="cn_related_job_card-top">
                                        <?php if ($employer_logo): ?>
                                            <img class="cn_related_job_card__img" src="<?php echo esc_url($employer_logo); ?>"
                                                alt="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php else: ?>
                                            <div class="cn_related_job_card__img-placeholder">
                                                <span><?php echo esc_html(substr($company_name ?: 'Job', 0, 1)); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h4 class="cn_related_job_card__title">
                                                <a
                                                    href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                                            </h4>
                                            <span
                                                class="cn_related_job_card__company"><?php echo esc_html($company_name); ?></span>
                                            <?php if ($rel_location): ?>
                                                <span style="color: #CACACA; font-size: 14px;"> | </span>
                                                <span
                                                    class="cn_related_job_card__location"><?php echo esc_html($rel_location); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($rel_salary_mode === 'numeric' && $rel_salary_numeric): ?>
                                        <div class="cn_related_job_card__salary">
                                            <svg width="20" height="21" viewBox="0 0 20 21" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="10" cy="10.4998" r="8.33333" stroke="#3D3935" stroke-width="1.5">
                                                </circle>
                                                <path d="M10 5.5V15.5" stroke="#3D3935" stroke-width="1.5" stroke-linecap="round">
                                                </path>
                                                <path
                                                    d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                                    stroke="#3D3935" stroke-width="1.5" stroke-linecap="round"></path>
                                            </svg>
                                            <span>$ <?php echo esc_html(number_format($rel_salary_numeric)); ?></span>
                                        </div>
                                    <?php elseif ($rel_salary_range): ?>
                                        <div class="cn_related_job_card__salary">
                                            <svg width="20" height="21" viewBox="0 0 20 21" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="10" cy="10.4998" r="8.33333" stroke="#3D3935" stroke-width="1.5">
                                                </circle>
                                                <path d="M10 5.5V15.5" stroke="#3D3935" stroke-width="1.5" stroke-linecap="round">
                                                </path>
                                                <path
                                                    d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                                    stroke="#3D3935" stroke-width="1.5" stroke-linecap="round"></path>
                                            </svg>
                                            <span><?php echo esc_html($rel_salary_range); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <hr>

                                    <div class="cn_related_job_card-bottom">
                                        <div class="cn_related_job_card__published"></div>
                                        <?php if ($expiry_text): ?>
                                            <span class="cn_related_job_card__modified"><?php echo esc_html($expiry_text); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else: ?>
                        <p class="cn-no-related">No related jobs available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const shareToggle = document.getElementById('cn-share-toggle');
        const shareMenu = document.getElementById('cn-share-menu');
        const shareOptions = document.querySelectorAll('.cn-share-option');

        if (shareToggle && shareMenu) {
            // Toggle dropdown on button click
            shareToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                shareMenu.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!shareMenu.contains(e.target) && e.target !== shareToggle) {
                    shareMenu.classList.remove('active');
                }
            });

            // Handle share options
            shareOptions.forEach(function(option) {
                option.addEventListener('click', function(e) {
                    const action = this.getAttribute('data-action');

                    if (action === 'copy') {
                        e.preventDefault();
                        const jobUrl = '<?php echo esc_js(get_permalink()); ?>';

                        // Copy to clipboard
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(jobUrl).then(function() {
                                // Change text temporarily to show success
                                const originalText = option.querySelector('span')
                                    .textContent;
                                option.querySelector('span').textContent = 'Link Copied!';
                                option.style.background = '#d4edda';

                                setTimeout(function() {
                                    option.querySelector('span').textContent =
                                        originalText;
                                    option.style.background = '';
                                    shareMenu.classList.remove('active');
                                }, 2000);
                            }).catch(function() {
                                alert('Failed to copy link');
                            });
                        } else {
                            // Fallback for older browsers
                            const textArea = document.createElement('textarea');
                            textArea.value = jobUrl;
                            textArea.style.position = 'fixed';
                            textArea.style.left = '-9999px';
                            document.body.appendChild(textArea);
                            textArea.select();

                            try {
                                document.execCommand('copy');
                                const originalText = option.querySelector('span').textContent;
                                option.querySelector('span').textContent = 'Link Copied!';
                                option.style.background = '#d4edda';

                                setTimeout(function() {
                                    option.querySelector('span').textContent = originalText;
                                    option.style.background = '';
                                    shareMenu.classList.remove('active');
                                }, 2000);
                            } catch (err) {
                                alert('Failed to copy link');
                            }

                            document.body.removeChild(textArea);
                        }
                    } else {
                        // For social share links, close menu after click
                        setTimeout(function() {
                            shareMenu.classList.remove('active');
                        }, 100);
                    }
                });
            });
        }
    });
</script>

<?php
get_footer();

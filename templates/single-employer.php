<?php

/**
 * Template: CareerNest — Single Employer Profile
 * Modern profile layout with gradient banner design
 */

defined('ABSPATH') || exit;

get_header();

// Get employer data
$employer_id = get_the_ID();
$email = get_post_meta($employer_id, '_contact_email', true);
$phone = get_post_meta($employer_id, '_phone', true);
$website = get_post_meta($employer_id, '_website', true);
$location = get_post_meta($employer_id, '_location', true);
$tagline = get_post_meta($employer_id, '_tagline', true);
$industry_desc = get_post_meta($employer_id, '_industry_description', true);
$about = get_post_meta($employer_id, '_about', true);
$mission = get_post_meta($employer_id, '_mission', true);
$spotlight = get_post_meta($employer_id, '_spotlight', true);
$interested = get_post_meta($employer_id, '_interested_in_working', true);
$specialities = get_post_meta($employer_id, '_specialities', true);
$company_size = get_post_meta($employer_id, '_company_size', true);
$founded_year = get_post_meta($employer_id, '_founded_year', true);
$employer_logo = get_the_post_thumbnail_url($employer_id, 'medium');

// Get company name
$company_name = get_the_title($employer_id);
$company_initial = substr($company_name, 0, 1);

// Check if current user is owner
$is_owner = false;
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
    $owner_id = (int) get_post_meta($employer_id, '_user_id', true);
    $is_owner = ($user_employer_id === $employer_id && $owner_id === $current_user->ID);
}

// Check profile completeness and restrict access if below 70%
$profile_completeness = \CareerNest\Profile_Helper::calculate_employer_completeness($employer_id);
$percentage = $profile_completeness['percentage'];

// If profile is less than 70% complete and user is not owner, show restricted access message
if ($percentage < 70 && !$is_owner) {
?>
    <main id="primary" class="site-main cn-employer-profile">
        <div class="cn-employer-container">
            <div class="cn-profile-restricted">
                <div class="cn-restricted-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#666" stroke-width="2" />
                        <path d="M12 8v4M12 16h.01" stroke="#666" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
                <h1><?php echo esc_html__('Company Profile Not Available', 'careernest'); ?></h1>
                <p><?php echo esc_html__('This company profile is currently being updated and is not available for public viewing.', 'careernest'); ?>
                </p>
                <?php
                $pages = get_option('careernest_pages', []);
                $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'):
                ?>
                    <a href="<?php echo esc_url(get_permalink($jobs_page_id)); ?>" class="cn-btn cn-btn-primary">
                        <?php echo esc_html__('Browse Available Jobs', 'careernest'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <style>
        .cn-profile-restricted {
            text-align: center;
            padding: 4rem 2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .cn-restricted-icon {
            margin: 0 auto 2rem;
            opacity: 0.5;
        }

        .cn-profile-restricted h1 {
            font-size: 1.75rem;
            color: #333;
            margin: 0 0 1rem 0;
        }

        .cn-profile-restricted p {
            color: #666;
            font-size: 1.1rem;
            margin: 0 0 2rem 0;
            line-height: 1.6;
        }

        .cn-btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: var(--cn-primary-btn, #0073aa);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cn-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: white;
        }
    </style>
<?php
    get_footer();
    return;
}
?>

<main id="primary" class="site-main cn-employer-profile">
    <div class="cn-employer-container">

        <!-- Modern Profile Header -->
        <div class="cn-profile-header-wrapper">
            <div class="cn-profile-banner"></div>

            <div class="cn-profile-header-content">
                <div class="cn-profile-left-section">
                    <div class="cn-company-logo-wrapper">
                        <?php if ($employer_logo): ?>
                            <img class="cn-company-logo" src="<?php echo esc_url($employer_logo); ?>"
                                alt="<?php echo esc_attr($company_name); ?>">
                        <?php else: ?>
                            <div class="cn-company-logo-placeholder">
                                <span><?php echo esc_html($company_initial); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="cn-company-info">
                        <h1 class="cn-company-name">
                            <?php echo esc_html($company_name); ?>
                            <?php if ($is_owner): ?>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'edit-profile', 'employer_id' => $employer_id], get_permalink($employer_id))); ?>"
                                    class="cn-owner-edit-btn"
                                    title="<?php echo esc_attr__('Edit Company Profile', 'careernest'); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </h1>

                        <div class="cn-company-meta">
                            <?php if ($location): ?>
                                <span class="cn-location-item">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4 10.1433C4 5.64588 7.58172 2 12 2C16.4183 2 20 5.64588 20 10.1433C20 14.6055 17.4467 19.8124 13.4629 21.6744C12.5343 22.1085 11.4657 22.1085 10.5371 21.6744C6.55332 19.8124 4 14.6055 4 10.1433Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                    <?php echo esc_html($location); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="cn-profile-contact-section">
                    <?php if ($email): ?>
                        <div class="cn-contact-card">
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="cn-contact-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                            <div class="cn-contact-info">
                                <span class="cn-contact-label">Email Address</span>
                                <a href="mailto:<?php echo esc_attr($email); ?>" class="cn-contact-value">
                                    <?php echo esc_html($email); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($phone): ?>
                        <div class="cn-contact-card">
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="cn-contact-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                            <div class="cn-contact-info">
                                <span class="cn-contact-label">Phone Number</span>
                                <a href="tel:<?php echo esc_attr($phone); ?>" class="cn-contact-value">
                                    <?php echo esc_html($phone); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($website): ?>
                        <div class="cn-contact-card">
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer"
                                class="cn-contact-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <line x1="2" y1="12" x2="22" y2="12" stroke="white" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                            <div class="cn-contact-info">
                                <span class="cn-contact-label">Website</span>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer"
                                    class="cn-contact-value">
                                    Visit Website
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Body -->
        <div class="cn-profile-body">
            <div class="cn-profile-main">
                <?php if ($about): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">About</h2>
                        <div class="cn-section-content cn-expandable-content">
                            <?php
                            $about_text = wp_kses_post(wpautop($about));
                            $is_long = strlen(strip_tags($about)) > 400;

                            if ($is_long):
                                $short_about = wp_trim_words($about, 60, '');
                            ?>
                                <div class="cn-summary-short">
                                    <?php echo wp_kses_post(wpautop($short_about)); ?>
                                    <a href="#" class="cn-read-more-link">...see more</a>
                                </div>
                                <div class="cn-summary-full" style="display: none;">
                                    <?php echo $about_text; ?>
                                    <a href="#" class="cn-read-less-link">see less</a>
                                </div>
                            <?php else: ?>
                                <?php echo $about_text; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mission): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Our Mission Statement</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($mission)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($spotlight): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Company Spotlight</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($spotlight)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($interested): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Interested in Working for Us?</h2>
                        <div class="cn-section-content">
                            <?php echo wp_kses_post(wpautop($interested)); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (get_the_content()): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Additional Information</h2>
                        <div class="cn-section-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$about && !$mission && !$spotlight && !$interested && !get_the_content()): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Company Information</h2>
                        <div class="cn-section-content">
                            <p><em>No additional company information available.</em></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cn-profile-sidebar">
                <!-- Profile Completeness (Only for Owner) -->
                <?php if ($is_owner):
                    $profile_completeness = \CareerNest\Profile_Helper::calculate_employer_completeness($employer_id);
                    $percentage = $profile_completeness['percentage'];
                    $color = \CareerNest\Profile_Helper::get_completion_color($percentage);
                    $status = \CareerNest\Profile_Helper::get_completion_status($percentage);
                ?>
                    <div class="cn-sidebar-card cn-profile-completeness">
                        <h3 class="cn-sidebar-title"><?php echo esc_html__('Profile Strength', 'careernest'); ?></h3>

                        <div class="cn-completeness-meter">
                            <div class="cn-meter-bar">
                                <div class="cn-meter-fill"
                                    style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($color); ?>;">
                                </div>
                            </div>
                            <div class="cn-meter-text">
                                <span class="cn-percentage"><?php echo esc_html($percentage); ?>%</span>
                                <span class="cn-status"
                                    style="color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($status); ?></span>
                            </div>
                        </div>

                        <?php if ($percentage < 100): ?>
                            <div class="cn-profile-tips">
                                <p class="cn-tips-header"><?php echo esc_html__('Complete your profile:', 'careernest'); ?></p>
                                <ul class="cn-missing-fields">
                                    <?php foreach (array_slice($profile_completeness['missing_fields'], 0, 3) as $field): ?>
                                        <li>• <?php echo esc_html($field); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p class="cn-profile-complete">
                                ✓ <?php echo esc_html__('Your company profile is complete!', 'careernest'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="cn-sidebar-card">
                    <h3 class="cn-sidebar-title">Company Overview</h3>

                    <?php if ($industry_desc): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.5 24.5H24.5M5.83333 24.5V10.5L11.6667 15.1667V10.5L17.5 15.1667H22.1667"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M22.1667 24.5V15.1667L20.4913 3.997C20.4706 3.85869 20.4009 3.73242 20.2949 3.64119C20.1889 3.54997 20.0537 3.49986 19.9138 3.5H18.578C18.4397 3.49979 18.3059 3.5487 18.2003 3.63802C18.0947 3.72734 18.0243 3.85126 18.0017 3.98767L16.3333 14M10.5 19.8333H11.6667M16.3333 19.8333H17.5"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Industry</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($industry_desc); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($company_size): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M9.33333 24.5V23.3333C9.33333 22.7145 9.57917 22.121 10.0168 21.6834C10.4543 21.2458 11.0478 21 11.6667 21H16.3333C16.9522 21 17.5457 21.2458 17.9832 21.6834C18.4208 22.121 18.6667 22.7145 18.6667 23.3333V24.5M19.8333 11.6667H22.1667C22.7855 11.6667 23.379 11.9125 23.8166 12.3501C24.2542 12.7877 24.5 13.3812 24.5 14V15.1667M3.5 15.1667V14C3.5 13.3812 3.74583 12.7877 4.18342 12.3501C4.621 11.9125 5.21449 11.6667 5.83333 11.6667H8.16667M11.6667 15.1667C11.6667 15.7855 11.9125 16.379 12.3501 16.8166C12.7877 17.2542 13.3812 17.5 14 17.5C14.6188 17.5 15.2123 17.2542 15.6499 16.8166C16.0875 16.379 16.3333 15.7855 16.3333 15.1667C16.3333 14.5478 16.0875 13.9543 15.6499 13.5168C15.2123 13.0792 14.6188 12.8333 14 12.8333C13.3812 12.8333 12.7877 13.0792 12.3501 13.5168C11.9125 13.9543 11.6667 14.5478 11.6667 15.1667ZM17.5 5.83333C17.5 6.45217 17.7458 7.04566 18.1834 7.48325C18.621 7.92083 19.2145 8.16667 19.8333 8.16667C20.4522 8.16667 21.0457 7.92083 21.4832 7.48325C21.9208 7.04566 22.1667 6.45217 22.1667 5.83333C22.1667 5.21449 21.9208 4.621 21.4832 4.18342C21.0457 3.74583 20.4522 3.5 19.8333 3.5C19.2145 3.5 18.621 3.74583 18.1834 4.18342C17.7458 4.621 17.5 5.21449 17.5 5.83333ZM5.83333 5.83333C5.83333 6.45217 6.07917 7.04566 6.51675 7.48325C6.95434 7.92083 7.54783 8.16667 8.16667 8.16667C8.7855 8.16667 9.379 7.92083 9.81658 7.48325C10.2542 7.04566 10.5 6.45217 10.5 5.83333C10.5 5.21449 10.2542 4.621 9.81658 4.18342C9.379 3.74583 8.7855 3.5 8.16667 3.5C7.54783 3.5 6.95434 3.74583 6.51675 4.18342C6.07917 4.621 5.83333 5.21449 5.83333 5.83333Z"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Company Size</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($company_size); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($location): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M10.5 12.8335C10.5 13.7618 10.8687 14.652 11.5251 15.3084C12.1815 15.9647 13.0717 16.3335 14 16.3335C14.9283 16.3335 15.8185 15.9647 16.4749 15.3084C17.1313 14.652 17.5 13.7618 17.5 12.8335C17.5 11.9052 17.1313 11.015 16.4749 10.3586C15.8185 9.70224 14.9283 9.3335 14 9.3335C13.0717 9.3335 12.1815 9.70224 11.5251 10.3586C10.8687 11.015 10.5 11.9052 10.5 12.8335Z"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M20.6002 19.4333L15.65 24.3835C15.2125 24.8206 14.6193 25.0661 14.0009 25.0661C13.3825 25.0661 12.7893 24.8206 12.3518 24.3835L7.4005 19.4333C6.09525 18.128 5.20639 16.465 4.8463 14.6545C4.48621 12.844 4.67107 10.9674 5.3775 9.262C6.08393 7.55658 7.28021 6.09894 8.81506 5.07341C10.3499 4.04787 12.1544 3.50049 14.0003 3.50049C15.8463 3.50049 17.6508 4.04787 19.1856 5.07341C20.7204 6.09894 21.9167 7.55658 22.6232 9.262C23.3296 10.9674 23.5144 12.844 23.1544 14.6545C22.7943 16.465 21.9054 18.128 20.6002 19.4333Z"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Headquarters</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($location); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($founded_year): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M13.417 24.5H7.00033C6.38149 24.5 5.78799 24.2542 5.35041 23.8166C4.91282 23.379 4.66699 22.7855 4.66699 22.1667V8.16667C4.66699 7.54783 4.91282 6.95434 5.35041 6.51675C5.78799 6.07917 6.38149 5.83333 7.00033 5.83333H21.0003C21.6192 5.83333 22.2127 6.07917 22.6502 6.51675C23.0878 6.95434 23.3337 7.54783 23.3337 8.16667V15.1667M18.667 3.5V8.16667M9.33366 3.5V8.16667M4.66699 12.8333H23.3337M17.5003 22.1667L19.8337 24.5L24.5003 19.8333"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Founded</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($founded_year); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($specialities): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M14.0003 20.7086L6.79963 24.4944L8.17513 16.4759L2.3418 10.7978L10.3918 9.6311L13.9921 2.33594L17.5925 9.6311L25.6425 10.7978L19.8091 16.4759L21.1846 24.4944L14.0003 20.7086Z"
                                        stroke="#101010" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Specialities</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($specialities); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Current Openings -->
                <div class="cn-sidebar-card">
                    <h3 class="cn-sidebar-title">Current Openings</h3>
                    <?php
                    $employer_jobs = new WP_Query([
                        'post_type' => 'job_listing',
                        'post_status' => 'publish',
                        'posts_per_page' => 5,
                        'meta_query' => [
                            [
                                'key' => '_employer_id',
                                'value' => $employer_id,
                                'compare' => '='
                            ]
                        ],
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ]);

                    if ($employer_jobs->have_posts()): ?>
                        <div class="cn-employer-jobs">
                            <?php while ($employer_jobs->have_posts()): $employer_jobs->the_post();
                                $job_id = get_the_ID();
                                $job_location = get_post_meta($job_id, '_job_location', true);
                                $job_salary_range = get_post_meta($job_id, '_salary_range', true);
                                $job_salary_numeric = get_post_meta($job_id, '_salary', true);
                                $job_salary_mode = get_post_meta($job_id, '_salary_mode', true);
                                $job_closing_date = get_post_meta($job_id, '_closing_date', true);

                                $expiry_text = '';
                                if ($job_closing_date) {
                                    $closing_timestamp = strtotime($job_closing_date . ' 23:59:59');
                                    $current_timestamp = current_time('timestamp');
                                    $days_diff = ceil(($closing_timestamp - $current_timestamp) / DAY_IN_SECONDS);

                                    if ($days_diff > 0) {
                                        $expiry_text = 'Expires in ' . $days_diff . ' day' . ($days_diff > 1 ? 's' : '');
                                    } elseif ($days_diff === 0) {
                                        $expiry_text = 'Expires today';
                                    } else {
                                        $expiry_text = 'Expired';
                                    }
                                } else {
                                    $expiry_text = 'No closing date';
                                }
                            ?>
                                <div class="cn-related-job-card">
                                    <div class="cn-related-job-card-top">
                                        <?php if ($employer_logo): ?>
                                            <img class="cn-related-job-card__img" src="<?php echo esc_url($employer_logo); ?>"
                                                alt="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php else: ?>
                                            <div class="cn-related-job-card__img-placeholder">
                                                <span><?php echo esc_html($company_initial); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h4 class="cn-related-job-card__title">
                                                <a
                                                    href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                                            </h4>
                                            <span
                                                class="cn-related-job-card__company"><?php echo esc_html($company_name); ?></span>
                                            <?php if ($job_location): ?>
                                                <span style="color: #CACACA; font-size: 14px;"> | </span>
                                                <span
                                                    class="cn-related-job-card__location"><?php echo esc_html($job_location); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($job_salary_mode === 'numeric' && $job_salary_numeric): ?>
                                        <div class="cn-related-job-card__salary">
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
                                            <span>$ <?php echo esc_html(number_format($job_salary_numeric)); ?></span>
                                        </div>
                                    <?php elseif ($job_salary_range): ?>
                                        <div class="cn-related-job-card__salary">
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
                                            <span><?php echo esc_html($job_salary_range); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <hr>

                                    <div class="cn-related-job-card-bottom">
                                        <div class="cn-related-job-card__published"></div>
                                        <span class="cn-related-job-card__modified"><?php echo esc_html($expiry_text); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>

                        <?php
                        $pages = get_option('careernest_pages', []);
                        $jobs_page_id = isset($pages['jobs']) ? (int) $pages['jobs'] : 0;
                        if ($jobs_page_id && get_post_status($jobs_page_id) === 'publish'):
                            $jobs_url = add_query_arg('employer_id', $employer_id, get_permalink($jobs_page_id));
                        ?>
                            <a class="cn-view-more-jobs" href="<?php echo esc_url($jobs_url); ?>">View
                                More Jobs</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-current-jobs">No current openings available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle read more/less functionality
        const readMoreLinks = document.querySelectorAll('.cn-read-more-link');
        const readLessLinks = document.querySelectorAll('.cn-read-less-link');

        readMoreLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const shortDiv = this.closest('.cn-summary-short');
                const fullDiv = shortDiv.nextElementSibling;

                if (shortDiv && fullDiv) {
                    shortDiv.style.display = 'none';
                    fullDiv.style.display = 'block';
                }
            });
        });

        readLessLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const fullDiv = this.closest('.cn-summary-full');
                const shortDiv = fullDiv.previousElementSibling;

                if (shortDiv && fullDiv) {
                    fullDiv.style.display = 'none';
                    shortDiv.style.display = 'block';
                }
            });
        });
    });
</script>

<?php
get_footer();

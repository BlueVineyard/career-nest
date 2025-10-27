<?php

/**
 * Template: CareerNest — Single Applicant Profile
 */

defined('ABSPATH') || exit;

get_header();

// Get applicant data
$applicant_id = get_the_ID();
$user_id = get_post_meta($applicant_id, '_user_id', true);
$prof_title = get_post_meta($applicant_id, '_professional_title', true);
$right_to_work = get_post_meta($applicant_id, '_right_to_work', true);
$work_types = get_post_meta($applicant_id, '_work_types', true);
$location = get_post_meta($applicant_id, '_location', true);
$available_for_work = get_post_meta($applicant_id, '_available_for_work', true);
$resume_id = get_post_meta($applicant_id, '_resume_attachment_id', true);
$phone = get_post_meta($applicant_id, '_phone', true);
$linkedin_url = get_post_meta($applicant_id, '_linkedin_url', true);
$skills = get_post_meta($applicant_id, '_skills', true);
$education = get_post_meta($applicant_id, '_education', true);
$experience = get_post_meta($applicant_id, '_experience', true);
$licenses = get_post_meta($applicant_id, '_licenses', true);
$links = get_post_meta($applicant_id, '_links', true);

// Get user email if linked
$contact_email = '';
if ($user_id) {
    $user = get_user_by('id', $user_id);
    if ($user) {
        $contact_email = $user->user_email;
    }
}

// Get applicant name and photo
$applicant_name = get_the_title($applicant_id);
$applicant_photo = get_the_post_thumbnail_url($applicant_id, 'medium');

// Get resume file info
$resume_url = '';
$resume_title = '';
if ($resume_id) {
    $resume_url = wp_get_attachment_url($resume_id);
    $resume_title = get_the_title($resume_id);
}
?>

<main id="primary" class="site-main cn-applicant-profile">
    <div class="cn-applicant-container">

        <!-- Modern Profile Header -->
        <div class="cn-profile-header-wrapper">
            <div class="cn-profile-banner"></div>

            <div class="cn-profile-header-content">
                <div class="cn-profile-left-section">
                    <div class="cn-profile-photo-wrapper">
                        <?php if ($applicant_photo): ?>
                            <img class="cn-profile-photo" src="<?php echo esc_url($applicant_photo); ?>"
                                alt="<?php echo esc_attr($applicant_name); ?>">
                        <?php else: ?>
                            <div class="cn-profile-photo-placeholder">
                                <span><?php echo esc_html(substr($applicant_name, 0, 1)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="cn-profile-info">
                        <h1 class="cn-profile-name">
                            <?php echo esc_html($applicant_name); ?>
                            <?php if (current_user_can('edit_post', $applicant_id)): ?>
                                <a href="<?php echo esc_url(get_edit_post_link($applicant_id)); ?>"
                                    class="cn-edit-profile-btn" title="Edit Profile">
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

                        <div class="cn-profile-meta">
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

                            <?php if ($available_for_work): ?>
                                <span class="cn-availability-badge">
                                    <span class="cn-availability-dot"></span>
                                    Available for Work
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="cn-profile-contact-section">
                    <?php if ($contact_email): ?>
                        <div class="cn-contact-card">
                            <a href="mailto:<?php echo esc_attr($contact_email); ?>" class="cn-contact-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                            <div class="cn-contact-info">
                                <span class="cn-contact-label">Email Address</span>
                                <a href="mailto:<?php echo esc_attr($contact_email); ?>" class="cn-contact-value">
                                    <?php echo esc_html($contact_email); ?>
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

                    <?php if ($linkedin_url): ?>
                        <div class="cn-contact-card">
                            <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="cn-contact-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="4" cy="4" r="2" stroke="white" stroke-width="2" />
                                </svg>
                            </a>
                            <div class="cn-contact-info">
                                <span class="cn-contact-label">LinkedIn</span>
                                <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer"
                                    class="cn-contact-value">
                                    View Profile
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
                <!-- Personal Summary -->
                <?php if (get_the_content()): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Personal Summary</h2>
                        <div class="cn-section-content cn-expandable-content">
                            <?php
                            $content = get_the_content();
                            $content = apply_filters('the_content', $content);
                            $content = str_replace(']]>', ']]&gt;', $content);

                            // Check if content is long (more than 400 characters)
                            $is_long = strlen(strip_tags($content)) > 400;

                            if ($is_long):
                                $short_content = wp_trim_words($content, 60, '');
                            ?>
                                <div class="cn-summary-short">
                                    <?php echo wp_kses_post($short_content); ?>
                                    <a href="#" class="cn-read-more-link">...see more</a>
                                </div>
                                <div class="cn-summary-full" style="display: none;">
                                    <?php echo wp_kses_post($content); ?>
                                    <a href="#" class="cn-read-less-link">see less</a>
                                </div>
                            <?php else: ?>
                                <?php echo wp_kses_post($content); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Career History -->
                <?php if ($experience && is_array($experience) && !empty($experience)): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Career History</h2>
                        <div class="cn-section-content">
                            <?php foreach ($experience as $index => $exp):
                                $company = isset($exp['company']) ? $exp['company'] : '';
                                $title = isset($exp['title']) ? $exp['title'] : '';
                                $start_date = isset($exp['start_date']) ? $exp['start_date'] : '';
                                $end_date = isset($exp['end_date']) ? $exp['end_date'] : '';
                                $notes = isset($exp['notes']) ? $exp['notes'] : '';
                                $current = isset($exp['current']) ? $exp['current'] : false;

                                $date_range = '';
                                if ($start_date) {
                                    $date_range = date('F Y', strtotime($start_date));
                                    if ($current) {
                                        $date_range .= ' - Present';
                                    } elseif ($end_date) {
                                        $date_range .= ' - ' . date('F Y', strtotime($end_date));
                                    }
                                }
                            ?>
                                <div class="cn-experience-item">
                                    <div class="cn-experience-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z"
                                                stroke="#101010" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M16 21V5C16 4.46957 15.7893 3.96086 15.4142 3.58579C15.0391 3.21071 14.5304 3 14 3H10C9.46957 3 8.96086 3.21071 8.58579 3.58579C8.21071 3.96086 8 4.46957 8 5V21"
                                                stroke="#101010" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="cn-experience-details">
                                        <h3><?php echo esc_html($title); ?></h3>
                                        <p class="cn-company-name"><?php echo esc_html($company); ?></p>
                                        <?php if ($date_range): ?>
                                            <p class="cn-date-range"><?php echo esc_html($date_range); ?></p>
                                        <?php endif; ?>
                                        <?php if ($notes): ?>
                                            <div class="cn-notes"><?php echo wp_kses_post(wpautop($notes)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Education -->
                <?php if ($education && is_array($education) && !empty($education)): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Education</h2>
                        <div class="cn-section-content">
                            <?php foreach ($education as $index => $edu):
                                $institution = isset($edu['institution']) ? $edu['institution'] : '';
                                $certification = isset($edu['certification']) ? $edu['certification'] : '';
                                $start_date = isset($edu['start_date']) ? $edu['start_date'] : '';
                                $end_date = isset($edu['end_date']) ? $edu['end_date'] : '';
                                $notes = isset($edu['notes']) ? $edu['notes'] : '';
                                $complete = isset($edu['complete']) ? $edu['complete'] : false;

                                $date_display = '';
                                if ($end_date) {
                                    $date_display = date('F Y', strtotime($end_date));
                                } elseif ($start_date) {
                                    $date_display = date('F Y', strtotime($start_date));
                                }
                            ?>
                                <div class="cn-education-item">
                                    <div class="cn-education-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M22 10L12 5L2 10L12 15L22 10Z" stroke="#101010" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M6 12V17C6 17 8 19 12 19C16 19 18 17 18 17V12" stroke="#101010"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="cn-education-details">
                                        <h3><?php echo esc_html($certification); ?></h3>
                                        <p class="cn-institution-name"><?php echo esc_html($institution); ?></p>
                                        <?php if ($date_display): ?>
                                            <p class="cn-date-range"><?php echo esc_html($date_display); ?></p>
                                        <?php endif; ?>
                                        <?php if ($notes): ?>
                                            <div class="cn-notes"><?php echo wp_kses_post(wpautop($notes)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Licenses & Certifications -->
                <?php if ($licenses && is_array($licenses) && !empty($licenses)): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Licenses & Certifications</h2>
                        <div class="cn-section-content">
                            <?php foreach ($licenses as $index => $license):
                                $name = isset($license['name']) ? $license['name'] : '';
                                $issuer = isset($license['issuer']) ? $license['issuer'] : '';
                                $expiry_date = isset($license['expiry_date']) ? $license['expiry_date'] : '';
                                $notes = isset($license['notes']) ? $license['notes'] : '';

                                $expiry_display = '';
                                if ($expiry_date) {
                                    $expiry_display = 'Expires: ' . date('F Y', strtotime($expiry_date));
                                }
                            ?>
                                <div class="cn-license-item">
                                    <div class="cn-license-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z"
                                                stroke="#101010" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M14 2V8H20" stroke="#101010" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M9 15L11 17L15 13" stroke="#101010" stroke -width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="cn-license-details">
                                        <h3><?php echo esc_html($name); ?></h3>
                                        <p class="cn-issuer-name"><?php echo esc_html($issuer); ?></p>
                                        <?php if ($expiry_display): ?>
                                            <p class="cn-date-range"><?php echo esc_html($expiry_display); ?></p>
                                        <?php endif; ?>
                                        <?php if ($notes): ?>
                                            <div class="cn-notes"><?php echo wp_kses_post(wpautop($notes)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Professional Skills -->
                <?php if ($skills && is_array($skills) && !empty($skills)): ?>
                    <div class="cn-profile-section">
                        <h2 class="cn-section-title">Professional Skills</h2>
                        <div class="cn-section-content">
                            <div class="cn-skills-list">
                                <?php foreach ($skills as $skill): ?>
                                    <span class="cn-skill-tag"><?php echo esc_html($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cn-profile-sidebar">
                <!-- More Informations -->
                <div class="cn-sidebar-card">
                    <h3 class="cn-sidebar-title">More Informations</h3>

                    <?php if ($prof_title): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="13.9997" cy="7.00016" r="4.66667" stroke="#101010" stroke-width="1.8" />
                                    <path
                                        d="M17.5003 15.5481C16.4195 15.302 15.238 15.1665 14.0003 15.1665C8.84567 15.1665 4.66699 17.517 4.66699 20.4165C4.66699 23.316 4.66699 25.6665 14.0003 25.6665C20.6357 25.6665 22.5538 24.4785 23.1082 22.7498"
                                        stroke="#101010" stroke-width="1.8" />
                                    <circle cx="20.9997" cy="18.6667" r="4.66667" stroke="#101010" stroke-width="1.8" />
                                    <path d="M21 17.1111V20.2222" stroke="#101010" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M19.4443 18.6667L22.5554 18.6667" stroke="#101010" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Role</span>
                                <span class="cn-sidebar-value"><?php echo esc_html($prof_title); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($right_to_work): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M3.5 12.1527C3.5 8.42217 3.5 6.55691 3.94043 5.9294C4.38087 5.30188 6.13471 4.70154 9.6424 3.50084L10.3107 3.27209C12.1391 2.6462 13.0534 2.33325 14 2.33325C14.9466 2.33325 15.8609 2.6462 17.6893 3.27209L18.3576 3.50084C21.8653 4.70154 23.6191 5.30188 24.0596 5.9294C24.5 6.55691 24.5 8.42217 24.5 12.1527C24.5 12.7162 24.5 13.3272 24.5 13.9898C24.5 20.5676 19.5545 23.7597 16.4517 25.1151C15.61 25.4827 15.1891 25.6666 14 25.6666C12.8109 25.6666 12.39 25.4827 11.5483 25.1151C8.44546 23.7597 3.5 20.5676 3.5 13.9898C3.5 13.3272 3.5 12.7162 3.5 12.1527Z"
                                        stroke="#101010" stroke-width="1.8" />
                                    <circle cx="14.0003" cy="10.4998" r="2.33333" stroke="#101010" stroke-width="1.8" />
                                    <path
                                        d="M18.6663 17.4998C18.6663 18.7885 18.6663 19.8332 13.9997 19.8332C9.33301 19.8332 9.33301 18.7885 9.33301 17.4998C9.33301 16.2112 11.4223 15.1665 13.9997 15.1665C16.577 15.1665 18.6663 16.2112 18.6663 17.4998Z"
                                        stroke="#101010" stroke-width="1.8" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Right to Work</span>
                                <span
                                    class="cn-sidebar-value"><?php echo esc_html($right_to_work === 'australian' ? 'Australian Citizen' : 'Foreign Citizen'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($work_types && is_array($work_types) && !empty($work_types)): ?>
                        <div class="cn-sidebar-item">
                            <div class="cn-sidebar-icon">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="10.5003" cy="10.4998" r="2.33333" stroke="#101010" stroke-width="1.8" />
                                    <path
                                        d="M15.1663 17.4998C15.1663 18.7885 15.1663 19.8332 10.4997 19.8332C5.83301 19.8332 5.83301 18.7885 5.83301 17.4998C5.83301 16.2112 7.92235 15.1665 10.4997 15.1665C13.077 15.1665 15.1663 16.2112 15.1663 17.4998Z"
                                        stroke="#101010" stroke-width="1.8" />
                                    <path
                                        d="M2.33301 13.9998C2.33301 9.60006 2.33301 7.40017 3.69984 6.03334C5.06668 4.6665 7.26657 4.6665 11.6663 4.6665H16.333C20.7328 4.6665 22.9327 4.6665 24.2995 6.03334C25.6663 7.40017 25.6663 9.60006 25.6663 13.9998C25.6663 18.3996 25.6663 20.5995 24.2995 21.9663C22.9327 23.3332 20.7328 23.3332 16.333 23.3332H11.6663C7.26657 23.3332 5.06668 23.3332 3.69984 21.9663C2.33301 20.5995 2.33301 18.3996 2.33301 13.9998Z"
                                        stroke="#101010" stroke-width="1.8" />
                                    <path d="M22.167 14H17.5003" stroke="#101010" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <path d="M22.167 10.5H16.3337" stroke="#101010" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <path d="M22.167 17.5H18.667" stroke="#101010" stroke-width="1.8"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <div class="cn-sidebar-info">
                                <span class="cn-sidebar-label">Preferred Work Types</span>
                                <span
                                    class="cn-sidebar-value"><?php echo esc_html(implode(', ', array_map('ucfirst', str_replace('_', ' ', $work_types)))); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resume Download -->
                <?php if ($resume_url): ?>
                    <div class="cn-sidebar-card">
                        <h3 class="cn-sidebar-title">Resume</h3>
                        <a href="<?php echo esc_url($resume_url); ?>" target="_blank" class="cn-download-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Download Resume
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Additional Links -->
                <?php if ($links && is_array($links) && !empty($links)): ?>
                    <div class="cn-sidebar-card">
                        <h3 class="cn-sidebar-title">Additional Links</h3>
                        <div class="cn-links-list">
                            <?php foreach ($links as $link):
                                $label = isset($link['label']) ? $link['label'] : '';
                                $url = isset($link['url']) ? $link['url'] : '';
                                $notes = isset($link['notes']) ? $link['notes'] : '';

                                if ($url):
                                    // Detect link type based on URL or label
                                    $link_type = 'website'; // default
                                    $label_lower = strtolower($label);
                                    $url_lower = strtolower($url);

                                    if (strpos($url_lower, 'github.com') !== false || strpos($label_lower, 'github') !== false) {
                                        $link_type = 'github';
                                    } elseif (strpos($url_lower, 'twitter.com') !== false || strpos($url_lower, 'x.com') !== false || strpos($label_lower, 'twitter') !== false) {
                                        $link_type = 'twitter';
                                    } elseif (strpos($url_lower, 'facebook.com') !== false || strpos($label_lower, 'facebook') !== false) {
                                        $link_type = 'facebook';
                                    } elseif (strpos($url_lower, 'instagram.com') !== false || strpos($label_lower, 'instagram') !== false) {
                                        $link_type = 'instagram';
                                    } elseif (strpos($label_lower, 'portfolio') !== false || strpos($label_lower, 'website') !== false) {
                                        $link_type = 'portfolio';
                                    }
                            ?>
                                    <div class="cn-link-item">
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"
                                            class="cn-link-with-icon">
                                            <?php if ($link_type === 'github'): ?>
                                                <svg class="cn-link-icon cn-link-github" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                                </svg>
                                            <?php elseif ($link_type === 'twitter'): ?>
                                                <svg class="cn-link-icon cn-link-twitter" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                                </svg>
                                            <?php elseif ($link_type === 'facebook'): ?>
                                                <svg class="cn-link-icon cn-link-facebook" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                </svg>
                                            <?php elseif ($link_type === 'instagram'): ?>
                                                <svg class="cn-link-icon cn-link-instagram" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z" />
                                                </svg>
                                            <?php elseif ($link_type === 'portfolio'): ?>
                                                <svg class="cn-link-icon cn-link-portfolio" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="cn-link-icon cn-link-website" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path
                                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            <?php endif; ?>
                                            <strong><?php echo esc_html($label ?: 'Website'); ?></strong>
                                        </a>
                                        <?php if ($notes): ?>
                                            <p class="cn-link-notes"><?php echo esc_html($notes); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class Admin_Menus
{
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menus']);
        add_filter('parent_file', [$this, 'highlight_parent']);
        add_filter('submenu_file', [$this, 'highlight_submenu'], 10, 2);
    }

    public function register_menus(): void
    {
        // Top-level menu
        add_menu_page(
            __('CareerNest', 'careernest'),
            __('CareerNest', 'careernest'),
            'manage_careernest',
            'careernest',
            [$this, 'render_welcome'],
            'dashicons-filter',
            25
        );

        // Section: Jobs (heading)
        add_submenu_page('careernest', __('Jobs', 'careernest'), __('Jobs', 'careernest'), 'manage_careernest', 'careernest-section-jobs', '__return_null');
        // Jobs items
        add_submenu_page('careernest', __('All Jobs', 'careernest'), __('All Jobs', 'careernest'), 'manage_careernest', 'edit.php?post_type=job_listing');
        add_submenu_page('careernest', __('Add New Job', 'careernest'), __('Add New Job', 'careernest'), 'manage_careernest', 'post-new.php?post_type=job_listing');
        add_submenu_page('careernest', __('Job Categories', 'careernest'), __('Job Categories', 'careernest'), 'manage_careernest', 'edit-tags.php?taxonomy=job_category&post_type=job_listing');
        add_submenu_page('careernest', __('Job Types', 'careernest'), __('Job Types', 'careernest'), 'manage_careernest', 'edit-tags.php?taxonomy=job_type&post_type=job_listing');
        add_submenu_page('careernest', __('Applications', 'careernest'), __('Applications', 'careernest'), 'manage_careernest', 'edit.php?post_type=job_application');

        // Section: Employers (heading)
        add_submenu_page('careernest', __('Employers', 'careernest'), __('Employers', 'careernest'), 'manage_careernest', 'careernest-section-employers', '__return_null');
        add_submenu_page('careernest', __('All Employers', 'careernest'), __('All Employers', 'careernest'), 'manage_careernest', 'edit.php?post_type=employer');
        add_submenu_page('careernest', __('Account Requests', 'careernest'), __('Account Requests', 'careernest'), 'manage_careernest', 'employer-requests', ['\CareerNest\Admin\Employer_Requests', 'render_requests_page_static']);
        add_submenu_page('careernest', __('Employee Requests', 'careernest'), __('Employee Requests', 'careernest'), 'manage_careernest', 'employee-requests', ['\CareerNest\Admin\Employee_Requests', 'render_requests_page_static']);
        add_submenu_page('careernest', __('Deletion Requests', 'careernest'), __('Deletion Requests', 'careernest'), 'manage_careernest', 'deletion-requests', ['\CareerNest\Admin\Deletion_Requests', 'render_requests_page_static']);

        // Section: Applicants (heading)
        add_submenu_page('careernest', __('Applicants', 'careernest'), __('Applicants', 'careernest'), 'manage_careernest', 'careernest-section-applicants', '__return_null');
        add_submenu_page('careernest', __('All Applicants', 'careernest'), __('All Applicants', 'careernest'), 'manage_careernest', 'edit.php?post_type=applicant');

        // Section: Settings (heading)
        add_submenu_page('careernest', __('Settings', 'careernest'), __('Settings', 'careernest'), 'manage_settings', 'careernest-section-settings', '__return_null');
        add_submenu_page('careernest', __('Settings', 'careernest'), __('Settings', 'careernest'), 'manage_settings', 'careernest-settings', [$this, 'render_settings_placeholder']);
    }

    public function render_welcome(): void
    {
        if (! current_user_can('manage_careernest')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'careernest'));
        }

        // Get platform branding
        $platform_name = function_exists('cn_get_platform_name') ? cn_get_platform_name() : 'CareerNest';

        echo '<div class="wrap careernest-dashboard">';
        echo '<h1>' . esc_html($platform_name) . ' ' . esc_html__('Overview', 'careernest') . '</h1>';

        // Branding Preview & Setup Checklist
        echo '<div class="cn-dashboard-top-row">';

        // Branding Preview
        echo '<div class="cn-branding-preview">';
        echo '<h3>' . esc_html__('Your Platform', 'careernest') . '</h3>';

        $branding = get_option('careernest_branding', []);
        $logo_id = isset($branding['platform_logo']) ? (int) $branding['platform_logo'] : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

        if ($logo_url) {
            echo '<div class="cn-logo-display">';
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($platform_name) . '" />';
            echo '</div>';
        }

        echo '<div class="cn-platform-info">';
        echo '<div class="cn-platform-name">' . esc_html($platform_name) . '</div>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=careernest-settings&tab=branding')) . '" class="button button-secondary">';
        echo '<span class="dashicons dashicons-edit"></span> ' . esc_html__('Edit Branding', 'careernest');
        echo '</a>';
        echo '</div>';
        echo '</div>';

        // Setup Checklist
        echo '<div class="cn-setup-checklist">';
        echo '<h3>' . esc_html__('Setup Status', 'careernest') . '</h3>';

        // Check branding
        $has_custom_name = !empty($branding['platform_name']) && $branding['platform_name'] !== 'CareerNest';
        $has_logo = !empty($branding['platform_logo']);
        $branding_configured = $has_custom_name || $has_logo;

        // Check Google Maps
        $options = get_option('careernest_options', []);
        $maps_key = isset($options['maps_api_key']) ? trim($options['maps_api_key']) : '';
        $maps_configured = !empty($maps_key);

        // Check email templates
        $email_templates = get_option('careernest_email_templates', []);
        $templates_customized = !empty($email_templates);

        echo '<div class="cn-checklist-items">';

        // Branding item
        echo '<div class="cn-checklist-item ' . ($branding_configured ? 'cn-check-complete' : 'cn-check-incomplete') . '">';
        echo '<span class="cn-check-icon dashicons ' . ($branding_configured ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
        echo '<span class="cn-check-text">';
        if ($branding_configured) {
            echo esc_html__('Branding configured', 'careernest');
        } else {
            echo esc_html__('Branding not customized', 'careernest');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=careernest-settings&tab=branding')) . '">' . esc_html__('Configure now', 'careernest') . '</a>';
        }
        echo '</span>';
        echo '</div>';

        // Google Maps item
        echo '<div class="cn-checklist-item ' . ($maps_configured ? 'cn-check-complete' : 'cn-check-incomplete') . '">';
        echo '<span class="cn-check-icon dashicons ' . ($maps_configured ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
        echo '<span class="cn-check-text">';
        if ($maps_configured) {
            echo esc_html__('Google Maps API configured', 'careernest');
        } else {
            echo esc_html__('Google Maps not set up', 'careernest');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=careernest-settings&tab=general')) . '">' . esc_html__('Add API key', 'careernest') . '</a>';
        }
        echo '</span>';
        echo '</div>';

        // Email templates item
        echo '<div class="cn-checklist-item ' . ($templates_customized ? 'cn-check-complete' : 'cn-check-incomplete') . '">';
        echo '<span class="cn-check-icon dashicons ' . ($templates_customized ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
        echo '<span class="cn-check-text">';
        if ($templates_customized) {
            echo esc_html__('Email templates customized', 'careernest');
        } else {
            echo esc_html__('Using default email templates', 'careernest');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=careernest-settings&tab=email-templates')) . '">' . esc_html__('Customize', 'careernest') . '</a>';
        }
        echo '</span>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>';

        // Get pending request counts
        $pending_employers = $this->get_pending_employer_requests();
        $pending_employees = $this->get_pending_employee_requests();
        $pending_deletions = $this->get_pending_deletion_requests();
        $total_pending = $pending_employers + $pending_employees + $pending_deletions;

        // Pending Requests Banner
        if ($total_pending > 0) {
            echo '<div class="cn-pending-requests-banner">';
            echo '<div class="cn-banner-icon"><span class="dashicons dashicons-bell"></span></div>';
            echo '<div class="cn-banner-content">';
            echo '<h3>' . esc_html__('Pending Requests Require Attention', 'careernest') . '</h3>';
            echo '<div class="cn-request-links">';

            if ($pending_employers > 0) {
                echo '<a href="' . esc_url(admin_url('admin.php?page=employer-requests')) . '" class="cn-request-badge cn-badge-primary">';
                echo '<span class="cn-badge-count">' . esc_html($pending_employers) . '</span> ';
                echo esc_html__('Account Request', 'careernest') . ($pending_employers > 1 ? 's' : '');
                echo '</a>';
            }

            if ($pending_employees > 0) {
                echo '<a href="' . esc_url(admin_url('admin.php?page=employee-requests')) . '" class="cn-request-badge cn-badge-warning">';
                echo '<span class="cn-badge-count">' . esc_html($pending_employees) . '</span> ';
                echo esc_html__('Employee Request', 'careernest') . ($pending_employees > 1 ? 's' : '');
                echo '</a>';
            }

            if ($pending_deletions > 0) {
                echo '<a href="' . esc_url(admin_url('admin.php?page=deletion-requests')) . '" class="cn-request-badge cn-badge-danger">';
                echo '<span class="cn-badge-count">' . esc_html($pending_deletions) . '</span> ';
                echo esc_html__('Deletion Request', 'careernest') . ($pending_deletions > 1 ? 's' : '');
                echo '</a>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        // Main statistics cards
        $cards = [
            [
                'title' => __('Jobs', 'careernest'),
                'type'  => 'job_listing',
                'icon'  => 'dashicons-portfolio',
                'manage_link' => admin_url('edit.php?post_type=job_listing'),
                'add_link'    => admin_url('post-new.php?post_type=job_listing'),
            ],
            [
                'title' => __('Employers', 'careernest'),
                'type'  => 'employer',
                'icon'  => 'dashicons-store',
                'manage_link' => admin_url('edit.php?post_type=employer'),
                'add_link'    => admin_url('post-new.php?post_type=employer'),
            ],
            [
                'title' => __('Applicants', 'careernest'),
                'type'  => 'applicant',
                'icon'  => 'dashicons-id-alt',
                'manage_link' => admin_url('edit.php?post_type=applicant'),
                'add_link'    => admin_url('post-new.php?post_type=applicant'),
            ],
            [
                'title' => __('Applications', 'careernest'),
                'type'  => 'job_application',
                'icon'  => 'dashicons-clipboard',
                'manage_link' => admin_url('edit.php?post_type=job_application'),
                'add_link'    => admin_url('post-new.php?post_type=job_application'),
            ],
        ];

        echo '<div class="cn-cards">';
        foreach ($cards as $card) {
            $count = $this->get_post_type_count($card['type']);
            echo '<div class="cn-card">';
            echo '<span class="dashicons ' . esc_attr($card['icon']) . '"></span>';
            echo '<div class="cn-card-meta">';
            echo '<h2>' . esc_html($card['title']) . '</h2>';
            echo '<p class="cn-count"><strong>' . esc_html(number_format_i18n($count)) . '</strong></p>';
            echo '<p class="cn-actions">';
            echo '<a class="button button-primary" href="' . esc_url($card['manage_link']) . '">' . esc_html__('Manage', 'careernest') . '</a> ';
            echo '<a class="button" href="' . esc_url($card['add_link']) . '">' . esc_html__('Add New', 'careernest') . '</a>';
            echo '</p>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        // Platform Statistics Section
        echo '<div class="cn-dashboard-section">';
        echo '<h2>' . esc_html__('Platform Statistics', 'careernest') . '</h2>';
        echo '<div class="cn-stats-grid">';

        // Get statistics
        $stats = $this->get_platform_statistics();

        echo '<div class="cn-stat-item">';
        echo '<div class="cn-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>';
        echo '<div class="cn-stat-data">';
        echo '<div class="cn-stat-value">' . esc_html($stats['active_jobs']) . '</div>';
        echo '<div class="cn-stat-label">' . esc_html__('Active Jobs', 'careernest') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="cn-stat-item">';
        echo '<div class="cn-stat-icon"><span class="dashicons dashicons-groups"></span></div>';
        echo '<div class="cn-stat-data">';
        echo '<div class="cn-stat-value">' . esc_html($stats['new_applications_month']) . '</div>';
        echo '<div class="cn-stat-label">' . esc_html__('Applications This Month', 'careernest') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="cn-stat-item">';
        echo '<div class="cn-stat-icon"><span class="dashicons dashicons-businessman"></span></div>';
        echo '<div class="cn-stat-data">';
        echo '<div class="cn-stat-value">' . esc_html($stats['new_employers_week']) . '</div>';
        echo '<div class="cn-stat-label">' . esc_html__('New Employers This Week', 'careernest') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="cn-stat-item">';
        echo '<div class="cn-stat-icon"><span class="dashicons dashicons-admin-users"></span></div>';
        echo '<div class="cn-stat-data">';
        echo '<div class="cn-stat-value">' . esc_html($stats['new_applicants_week']) . '</div>';
        echo '<div class="cn-stat-label">' . esc_html__('New Applicants This Week', 'careernest') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Recent Activity Feed
        echo '<div class="cn-dashboard-section">';
        echo '<div class="cn-section-header">';
        echo '<h2>' . esc_html__('Recent Activity', 'careernest') . '</h2>';
        echo '<div class="cn-activity-filters">';
        echo '<button class="cn-filter-btn active" data-filter="all">' . esc_html__('All', 'careernest') . '</button>';
        echo '<button class="cn-filter-btn" data-filter="jobs">' . esc_html__('Jobs', 'careernest') . '</button>';
        echo '<button class="cn-filter-btn" data-filter="applications">' . esc_html__('Applications', 'careernest') . '</button>';
        echo '<button class="cn-filter-btn" data-filter="employers">' . esc_html__('Employers', 'careernest') . '</button>';
        echo '<button class="cn-filter-btn" data-filter="employees">' . esc_html__('Team', 'careernest') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '<div class="cn-activity-feed">';

        $activities = $this->get_recent_activities(10);

        if (!empty($activities)) {
            foreach ($activities as $activity) {
                $activity_type = $activity['type'] ?? 'unknown';
                echo '<div class="cn-activity-item" data-activity-type="' . esc_attr($activity_type) . '">';
                echo '<div class="cn-activity-icon" style="background: ' . esc_attr($activity['color']) . '">';
                echo '<span class="dashicons ' . esc_attr($activity['icon']) . '"></span>';
                echo '</div>';
                echo '<div class="cn-activity-content">';
                echo '<div class="cn-activity-text">' . wp_kses_post($activity['text']) . '</div>';
                echo '<div class="cn-activity-time">' . esc_html($activity['time']) . '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p class="cn-no-activity">' . esc_html__('No recent activity to display.', 'careernest') . '</p>';
        }

        echo '</div>';

        // Add filtering JavaScript
        echo '<script>
        jQuery(document).ready(function($) {
            $(".cn-filter-btn").on("click", function() {
                const filter = $(this).data("filter");
                
                // Update button states
                $(".cn-filter-btn").removeClass("active");
                $(this).addClass("active");
                
                // Filter activities
                if (filter === "all") {
                    $(".cn-activity-item").show();
                } else {
                    $(".cn-activity-item").hide();
                    $(".cn-activity-item[data-activity-type^=\"" + filter + "\"]").show();
                }
                
                // Show/hide no activity message
                const visibleCount = $(".cn-activity-item:visible").length;
                if (visibleCount === 0) {
                    if (!$(".cn-no-filtered").length) {
                        $(".cn-activity-feed").append("<p class=\"cn-no-activity cn-no-filtered\">" + 
                            "' . esc_js(__('No activities match this filter.', 'careernest')) . '</p>");
                    }
                } else {
                    $(".cn-no-filtered").remove();
                }
            });
        });
        </script>';

        echo '</div>';

        echo '</div>';
    }

    private function get_post_type_count(string $post_type): int
    {
        $counts = wp_count_posts($post_type);
        if (! $counts) {
            return 0;
        }
        $sum = 0;
        foreach (['publish', 'private', 'pending', 'draft', 'future'] as $st) {
            if (isset($counts->{$st})) {
                $sum += (int) $counts->{$st};
            }
        }
        return $sum;
    }

    /**
     * Get count of pending employer requests
     */
    private function get_pending_employer_requests(): int
    {
        $pending = new \WP_Query([
            'post_type' => 'employer',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
        ]);
        return $pending->found_posts;
    }

    /**
     * Get count of pending employee requests
     */
    private function get_pending_employee_requests(): int
    {
        $pending = get_users([
            'meta_key' => '_pending_employee_request',
            'meta_compare' => 'EXISTS',
            'count_total' => true,
            'fields' => 'ID',
        ]);
        return is_array($pending) ? count($pending) : 0;
    }

    /**
     * Get count of pending deletion requests
     */
    private function get_pending_deletion_requests(): int
    {
        $pending = get_users([
            'meta_key' => '_pending_deletion_request',
            'meta_compare' => 'EXISTS',
            'role' => 'employer_team',
            'count_total' => true,
            'fields' => 'ID',
        ]);
        return is_array($pending) ? count($pending) : 0;
    }

    /**
     * Get platform statistics
     */
    private function get_platform_statistics(): array
    {
        // Active jobs (published, not expired, not filled)
        $active_jobs = new \WP_Query([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_closing_date',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_closing_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ]);

        // Filter out filled positions manually
        $active_count = 0;
        if ($active_jobs->have_posts()) {
            foreach ($active_jobs->posts as $job_id) {
                $position_filled = get_post_meta($job_id, '_position_filled', true);
                if (!$position_filled || $position_filled !== '1') {
                    $active_count++;
                }
            }
        }

        // Applications this month
        $first_of_month = date('Y-m-01');
        $apps_month = new \WP_Query([
            'post_type' => 'job_application',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'after' => $first_of_month,
                    'inclusive' => true,
                ],
            ],
        ]);

        // New employers this week
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $employers_week = new \WP_Query([
            'post_type' => 'employer',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'after' => $week_ago,
                    'inclusive' => true,
                ],
            ],
        ]);

        // New applicants this week
        $applicants_week = new \WP_Query([
            'post_type' => 'applicant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'after' => $week_ago,
                    'inclusive' => true,
                ],
            ],
        ]);

        return [
            'active_jobs' => $active_count,
            'new_applications_month' => $apps_month->found_posts,
            'new_employers_week' => $employers_week->found_posts,
            'new_applicants_week' => $applicants_week->found_posts,
        ];
    }

    /**
     * Get recent platform activities
     */
    private function get_recent_activities(int $limit = 10): array
    {
        $activities = [];

        // Get logged activities (employee added/deleted)
        $logged_activities = get_option('careernest_recent_activity', []);
        foreach ($logged_activities as $activity) {
            // Fix legacy type names (migrate old data)
            if (isset($activity['type'])) {
                if ($activity['type'] === 'employee_added') {
                    $activity['type'] = 'employees_added';
                } elseif ($activity['type'] === 'employee_deleted') {
                    $activity['type'] = 'employees_deleted';
                }
            }

            // Add time formatting
            $activity['time'] = human_time_diff($activity['timestamp'], current_time('timestamp')) . ' ago';
            $activities[] = $activity;
        }

        // Get recent jobs
        $recent_jobs = new \WP_Query([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($recent_jobs->have_posts()) {
            foreach ($recent_jobs->posts as $job) {
                $employer_id = get_post_meta($job->ID, '_employer_id', true);
                $employer_name = $employer_id ? get_the_title($employer_id) : 'Unknown';

                $activities[] = [
                    'type' => 'jobs',
                    'text' => '<strong>' . esc_html($employer_name) . '</strong> posted a new job: <strong>' . esc_html($job->post_title) . '</strong>',
                    'time' => human_time_diff(strtotime($job->post_date), current_time('timestamp')) . ' ago',
                    'icon' => 'dashicons-portfolio',
                    'color' => '#0073aa',
                    'timestamp' => strtotime($job->post_date),
                ];
            }
        }

        // Get recent applications
        $recent_apps = new \WP_Query([
            'post_type' => 'job_application',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($recent_apps->have_posts()) {
            foreach ($recent_apps->posts as $app) {
                $job_id = get_post_meta($app->ID, '_job_id', true);
                $job_title = $job_id ? get_the_title($job_id) : 'Unknown Job';
                $applicant_name = get_post_meta($app->ID, '_applicant_name', true) ?: 'Applicant';

                $activities[] = [
                    'type' => 'applications',
                    'text' => '<strong>' . esc_html($applicant_name) . '</strong> applied for: <strong>' . esc_html($job_title) . '</strong>',
                    'time' => human_time_diff(strtotime($app->post_date), current_time('timestamp')) . ' ago',
                    'icon' => 'dashicons-clipboard',
                    'color' => '#10B981',
                    'timestamp' => strtotime($app->post_date),
                ];
            }
        }

        // Get recent employers
        $recent_employers = new \WP_Query([
            'post_type' => 'employer',
            'post_status' => 'publish',
            'posts_per_page' => 2,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($recent_employers->have_posts()) {
            foreach ($recent_employers->posts as $employer) {
                $activities[] = [
                    'type' => 'employers',
                    'text' => 'New employer joined: <strong>' . esc_html($employer->post_title) . '</strong>',
                    'time' => human_time_diff(strtotime($employer->post_date), current_time('timestamp')) . ' ago',
                    'icon' => 'dashicons-store',
                    'color' => '#f39c12',
                    'timestamp' => strtotime($employer->post_date),
                ];
            }
        }

        // Sort by timestamp descending
        usort($activities, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // Limit to requested number
        return array_slice($activities, 0, $limit);
    }

    public function render_settings_placeholder(): void
    {
        if (! current_user_can('manage_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'careernest'));
        }

        // Get active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CareerNest Settings', 'careernest') . '</h1>';

        // Tabs
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=careernest-settings&tab=general" class="nav-tab ' . ($active_tab === 'general' ? 'nav-tab-active' : '') . '">' . esc_html__('General', 'careernest') . '</a>';
        echo '<a href="?page=careernest-settings&tab=branding" class="nav-tab ' . ($active_tab === 'branding' ? 'nav-tab-active' : '') . '">' . esc_html__('Branding', 'careernest') . '</a>';
        echo '<a href="?page=careernest-settings&tab=appearance" class="nav-tab ' . ($active_tab === 'appearance' ? 'nav-tab-active' : '') . '">' . esc_html__('Appearance', 'careernest') . '</a>';
        echo '<a href="?page=careernest-settings&tab=email-templates" class="nav-tab ' . ($active_tab === 'email-templates' ? 'nav-tab-active' : '') . '">' . esc_html__('Email Templates', 'careernest') . '</a>';
        echo '<a href="?page=careernest-settings&tab=employer-dashboard" class="nav-tab ' . ($active_tab === 'employer-dashboard' ? 'nav-tab-active' : '') . '">' . esc_html__('Employer Dashboard', 'careernest') . '</a>';
        echo '</h2>';

        echo '<form method="post" action="options.php">';

        if ($active_tab === 'branding') {
            // Enqueue media scripts for logo upload
            wp_enqueue_media();

            settings_fields('careernest_branding_group');
            do_settings_sections('careernest_branding');
        } elseif ($active_tab === 'email-templates') {
            settings_fields('careernest_email_templates_group');
            do_settings_sections('careernest_email_templates');
        } elseif ($active_tab === 'employer-dashboard') {
            settings_fields('careernest_employer_dashboard_group');
            do_settings_sections('careernest_employer_dashboard');
        } elseif ($active_tab === 'appearance') {
            // Enqueue color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            settings_fields('careernest_appearance_group');
            do_settings_sections('careernest_appearance');

            // Initialize color picker
            echo '<script>
            jQuery(document).ready(function($) {
                $(".cn-color-picker").wpColorPicker();
            });
            </script>';
        } else {
            settings_fields('careernest_options_group');
            do_settings_sections('careernest_settings');
        }

        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Ensure the CareerNest top-level menu stays highlighted on CPT screens.
     */
    public function highlight_parent(string $parent_file): string
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen) {
            return $parent_file;
        }
        $cpts = ['job_listing', 'employer', 'applicant', 'job_application'];
        $taxes = ['job_category', 'job_type'];

        if (! empty($screen->post_type) && in_array($screen->post_type, $cpts, true)) {
            return 'careernest';
        }
        if ($screen->base === 'edit-tags' || $screen->base === 'term') {
            $tax = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
            if (in_array($tax, $taxes, true)) {
                return 'careernest';
            }
        }
        return $parent_file;
    }

    /**
     * Ensure the correct CareerNest submenu item is highlighted.
     */
    public function highlight_submenu(?string $submenu_file, string $parent_file): ?string
    {
        if ($parent_file !== 'careernest') {
            return $submenu_file;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen) {
            return $submenu_file;
        }

        // Map post type screens
        $pt = $screen->post_type ?? '';
        if ($pt === 'job_listing') {
            // Highlight Add New when creating, else All Jobs
            if (in_array($screen->base, ['post-new'], true)) {
                return 'post-new.php?post_type=job_listing';
            }
            return 'edit.php?post_type=job_listing';
        }
        if ($pt === 'employer') {
            if (in_array($screen->base, ['post-new'], true)) {
                return 'post-new.php?post_type=employer';
            }
            return 'edit.php?post_type=employer';
        }
        if ($pt === 'applicant') {
            if (in_array($screen->base, ['post-new'], true)) {
                return 'post-new.php?post_type=applicant';
            }
            return 'edit.php?post_type=applicant';
        }
        if ($pt === 'job_application') {
            return 'edit.php?post_type=job_application';
        }

        // Map taxonomy screens under Jobs
        if ($screen->base === 'edit-tags' || $screen->base === 'term') {
            $tax = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
            if ($tax === 'job_category') {
                return 'edit-tags.php?taxonomy=job_category&post_type=job_listing';
            }
            if ($tax === 'job_type') {
                return 'edit-tags.php?taxonomy=job_type&post_type=job_listing';
            }
        }

        return $submenu_file;
    }
}

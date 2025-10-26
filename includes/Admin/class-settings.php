<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class Settings
{
    public function hooks(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('option_page_capability_careernest_options_group', [$this, 'set_custom_capability']);
        add_filter('option_page_capability_careernest_email_templates_group', [$this, 'set_custom_capability']);
        add_filter('option_page_capability_careernest_appearance_group', [$this, 'set_custom_capability']);
        add_filter('option_page_capability_careernest_employer_dashboard_group', [$this, 'set_custom_capability']);
        add_filter('option_page_capability_careernest_branding_group', [$this, 'set_custom_capability']);
    }

    /**
     * Set custom capability for settings pages
     */
    public function set_custom_capability(string $capability): string
    {
        return 'manage_careernest';
    }

    public function register_settings(): void
    {
        // Register settings with custom capability (manage_careernest instead of default manage_options)
        register_setting('careernest_options_group', 'careernest_options', [
            'sanitize_callback' => [$this, 'sanitize_options'],
            'capability' => 'manage_careernest',
        ]);

        register_setting('careernest_email_templates_group', 'careernest_email_templates', [
            'sanitize_callback' => [$this, 'sanitize_email_templates'],
            'capability' => 'manage_careernest',
        ]);

        register_setting('careernest_appearance_group', 'careernest_appearance', [
            'sanitize_callback' => [$this, 'sanitize_appearance'],
            'capability' => 'manage_careernest',
        ]);

        register_setting('careernest_employer_dashboard_group', 'careernest_employer_dashboard', [
            'sanitize_callback' => [$this, 'sanitize_employer_dashboard'],
            'capability' => 'manage_careernest',
        ]);

        register_setting('careernest_branding_group', 'careernest_branding', [
            'sanitize_callback' => [$this, 'sanitize_branding'],
            'capability' => 'manage_careernest',
        ]);

        // Pages Section
        add_settings_section(
            'careernest_pages_section',
            __('CareerNest Pages', 'careernest'),
            [$this, 'render_pages_section_description'],
            'careernest_settings'
        );

        // General Section
        add_settings_section(
            'careernest_general_section',
            __('General', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Configure general options for CareerNest.', 'careernest') . '</p>';
            },
            'careernest_settings'
        );

        add_settings_field(
            'maps_api_key',
            __('Google Maps API Key', 'careernest'),
            [$this, 'render_maps_api_field'],
            'careernest_settings',
            'careernest_general_section',
            ['label_for' => 'careernest_maps_api_key']
        );

        // Job Filters Section
        add_settings_section(
            'careernest_filters_section',
            __('Job Listing Filters', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Configure filter options for the job listing page.', 'careernest') . '</p>';
            },
            'careernest_settings'
        );

        // Filter Position
        add_settings_field(
            'filter_position',
            __('Filter Position', 'careernest'),
            [$this, 'render_filter_position_field'],
            'careernest_settings',
            'careernest_filters_section',
            ['label_for' => 'careernest_filter_position']
        );

        // Job Listing Columns
        add_settings_field(
            'job_listing_columns',
            __('Job Listing Columns', 'careernest'),
            [$this, 'render_job_columns_field'],
            'careernest_settings',
            'careernest_filters_section',
            ['label_for' => 'careernest_job_listing_columns']
        );

        // Sortable filter list
        add_settings_field(
            'filters_order',
            __('Filter Order & Visibility', 'careernest'),
            [$this, 'render_sortable_filters'],
            'careernest_settings',
            'careernest_filters_section',
            ['label_for' => 'careernest_filters_order']
        );

        // Email Templates Section
        add_settings_section(
            'careernest_email_section',
            __('Email Templates', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Customize email templates sent by CareerNest. Use template variables like {{user_name}}, {{company_name}}, etc.', 'careernest') . '</p>';
            },
            'careernest_email_templates'
        );

        // Get all email templates
        $templates = new \CareerNest\Email\Templates();
        $all_templates = $templates->get_all_templates();

        foreach ($all_templates as $template_key => $template_data) {
            add_settings_field(
                'email_template_' . $template_key,
                $template_data['name'],
                [$this, 'render_email_template_field'],
                'careernest_email_templates',
                'careernest_email_section',
                [
                    'template_key' => $template_key,
                    'template_data' => $template_data,
                ]
            );
        }

        // Appearance Settings Section
        add_settings_section(
            'careernest_appearance_section',
            __('Appearance & Branding', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Customize the look and feel of CareerNest pages.', 'careernest') . '</p>';
            },
            'careernest_appearance'
        );

        // Primary Button Color
        add_settings_field(
            'primary_btn_color',
            __('Primary Button Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'primary_btn_color', 'default' => '#0073aa', 'label_for' => 'careernest_primary_btn_color', 'description' => 'Main action buttons (Post Job, Publish, etc.)']
        );

        // Secondary Button Color
        add_settings_field(
            'secondary_btn_color',
            __('Secondary Button Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'secondary_btn_color', 'default' => '#6c757d', 'label_for' => 'careernest_secondary_btn_color', 'description' => 'Secondary action buttons (Save Draft, Cancel, etc.)']
        );

        // Primary Text Color
        add_settings_field(
            'primary_text_color',
            __('Primary Text Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'primary_text_color', 'default' => '#333333', 'label_for' => 'careernest_primary_text_color', 'description' => 'Main text color (headings, labels, etc.)']
        );

        // Secondary Text Color  
        add_settings_field(
            'secondary_text_color',
            __('Secondary Text Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'secondary_text_color', 'default' => '#666666', 'label_for' => 'careernest_secondary_text_color', 'description' => 'Secondary text (descriptions, meta info, etc.)']
        );

        // Success Badge Color
        add_settings_field(
            'success_badge_color',
            __('Success Badge Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'success_badge_color', 'default' => '#10B981', 'label_for' => 'careernest_success_badge_color', 'description' => 'Active jobs, hired status, success messages']
        );

        // Warning Badge Color
        add_settings_field(
            'warning_badge_color',
            __('Warning Badge Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'warning_badge_color', 'default' => '#f39c12', 'label_for' => 'careernest_warning_badge_color', 'description' => 'Draft jobs, reviewed applications, warnings']
        );

        // Danger Badge Color
        add_settings_field(
            'danger_badge_color',
            __('Danger Badge Color', 'careernest'),
            [$this, 'render_color_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['option_key' => 'danger_badge_color', 'default' => '#dc3545', 'label_for' => 'careernest_danger_badge_color', 'description' => 'Expired jobs, rejected applications, delete buttons']
        );

        // Container Width
        add_settings_field(
            'container_width',
            __('Container Width', 'careernest'),
            [$this, 'render_container_width_field'],
            'careernest_appearance',
            'careernest_appearance_section',
            ['label_for' => 'careernest_container_width']
        );

        // Branding Settings Section
        add_settings_section(
            'careernest_branding_section',
            __('White-Label Branding', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Customize platform branding to match your business identity. All settings apply site-wide.', 'careernest') . '</p>';
            },
            'careernest_branding'
        );

        // Platform Name
        add_settings_field(
            'platform_name',
            __('Platform Name', 'careernest'),
            [$this, 'render_text_field'],
            'careernest_branding',
            'careernest_branding_section',
            ['option_group' => 'careernest_branding', 'option_key' => 'platform_name', 'default' => 'CareerNest', 'label_for' => 'platform_name', 'description' => 'Your platform name (e.g., "Blue Vineyard Careers"). This replaces "CareerNest" throughout the platform.', 'placeholder' => 'CareerNest']
        );

        // Platform Logo
        add_settings_field(
            'platform_logo',
            __('Platform Logo', 'careernest'),
            [$this, 'render_logo_field'],
            'careernest_branding',
            'careernest_branding_section',
            ['label_for' => 'platform_logo']
        );

        // Email From Name
        add_settings_field(
            'email_from_name',
            __('Email From Name', 'careernest'),
            [$this, 'render_text_field'],
            'careernest_branding',
            'careernest_branding_section',
            ['option_group' => 'careernest_branding', 'option_key' => 'email_from_name', 'default' => '', 'label_for' => 'email_from_name', 'description' => 'Name shown as sender in emails (e.g., "AES Team"). Leave empty to use "The [Platform Name] Team".', 'placeholder' => 'The CareerNest Team']
        );

        // Email From Address
        add_settings_field(
            'email_from_address',
            __('Email From Address', 'careernest'),
            [$this, 'render_text_field'],
            'careernest_branding',
            'careernest_branding_section',
            ['option_group' => 'careernest_branding', 'option_key' => 'email_from_address', 'default' => '', 'label_for' => 'email_from_address', 'description' => 'Email address shown as sender (e.g., "no-reply@adventistemployment.org.au"). Leave empty to use WordPress admin email.', 'placeholder' => 'noreply@example.com', 'type' => 'email']
        );

        // Support Email
        add_settings_field(
            'support_email',
            __('Support Email', 'careernest'),
            [$this, 'render_text_field'],
            'careernest_branding',
            'careernest_branding_section',
            ['option_group' => 'careernest_branding', 'option_key' => 'support_email', 'default' => '', 'label_for' => 'support_email', 'description' => 'Support contact email for users (used in email content). Leave empty to use WordPress admin email.', 'placeholder' => 'support@example.com', 'type' => 'email']
        );

        // Employer Dashboard Settings Section
        add_settings_section(
            'careernest_employer_dash_section',
            __('Employer Dashboard Configuration', 'careernest'),
            function () {
                echo '<p class="description">' . esc_html__('Configure employer dashboard display options and behavior.', 'careernest') . '</p>';
            },
            'careernest_employer_dashboard'
        );

        // Recent Jobs Count
        add_settings_field(
            'recent_jobs_count',
            __('Recent Jobs to Display', 'careernest'),
            [$this, 'render_number_field'],
            'careernest_employer_dashboard',
            'careernest_employer_dash_section',
            ['option_key' => 'recent_jobs_count', 'default' => '5', 'min' => '3', 'max' => '20', 'label_for' => 'recent_jobs_count', 'description' => 'Number of recent jobs to show on dashboard (3-20)']
        );

        // Recent Applications Count
        add_settings_field(
            'recent_apps_count',
            __('Recent Applications to Display', 'careernest'),
            [$this, 'render_number_field'],
            'careernest_employer_dashboard',
            'careernest_employer_dash_section',
            ['option_key' => 'recent_apps_count', 'default' => '5', 'min' => '3', 'max' => '20', 'label_for' => 'recent_apps_count', 'description' => 'Number of recent applications to show on dashboard (3-20)']
        );

        // Welcome Message
        add_settings_field(
            'welcome_message',
            __('Dashboard Welcome Message', 'careernest'),
            [$this, 'render_textarea_field'],
            'careernest_employer_dashboard',
            'careernest_employer_dash_section',
            ['option_key' => 'welcome_message', 'label_for' => 'welcome_message', 'description' => 'Custom message shown at top of employer dashboard (leave empty for default)']
        );
    }

    /**
     * Render pages section description
     */
    public function render_pages_section_description(): void
    {
        $pages = get_option('careernest_pages', []);

        echo '<p class="description">' . esc_html__('These pages were automatically created by CareerNest. You can view and edit them below.', 'careernest') . '</p>';

        if (empty($pages)) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('No pages found. Try deactivating and reactivating the plugin.', 'careernest') . '</p></div>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 1rem;">';
        echo '<thead><tr>';
        echo '<th style="width: 25%;">' . esc_html__('Page', 'careernest') . '</th>';
        echo '<th style="width: 40%;">' . esc_html__('URL', 'careernest') . '</th>';
        echo '<th style="width: 15%;">' . esc_html__('Status', 'careernest') . '</th>';
        echo '<th style="width: 20%;">' . esc_html__('Actions', 'careernest') . '</th>';
        echo '</tr></thead><tbody>';

        $page_labels = [
            'jobs' => __('Job Listings', 'careernest'),
            'login' => __('Login', 'careernest'),
            'forgot-password' => __('Forgot Password', 'careernest'),
            'employer-dashboard' => __('Employer Dashboard', 'careernest'),
            'applicant-dashboard' => __('Applicant Dashboard', 'careernest'),
            'register-employer' => __('Employer Registration', 'careernest'),
            'register-employee' => __('Employee Registration', 'careernest'),
            'register-applicant' => __('Applicant Registration', 'careernest'),
            'apply-job' => __('Apply for Job', 'careernest'),
        ];

        foreach ($pages as $slug => $page_id) {
            $page_id = (int) $page_id;
            $page = get_post($page_id);

            if (!$page) {
                continue;
            }

            $label = isset($page_labels[$slug]) ? $page_labels[$slug] : ucwords(str_replace('-', ' ', $slug));
            $permalink = get_permalink($page_id);
            $edit_link = get_edit_post_link($page_id);
            $status = get_post_status($page_id);
            $status_label = $status === 'publish' ? '<span style="color: #46b450;">●</span> ' . __('Published', 'careernest') : '<span style="color: #dc3232;">●</span> ' . ucfirst($status);

            echo '<tr>';
            echo '<td><strong>' . esc_html($label) . '</strong></td>';
            echo '<td><code>' . esc_html($permalink) . '</code></td>';
            echo '<td>' . $status_label . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($permalink) . '" class="button button-small" target="_blank">' . esc_html__('View', 'careernest') . '</a> ';
            echo '<a href="' . esc_url($edit_link) . '" class="button button-small">' . esc_html__('Edit', 'careernest') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<p class="description" style="margin-top: 1rem;">';
        echo '<strong>' . esc_html__('Note:', 'careernest') . '</strong> ';
        echo esc_html__('These pages are automatically managed by CareerNest. Avoid deleting them as they are required for the plugin to function properly.', 'careernest');
        echo '</p>';
    }

    public function sanitize_options($opts)
    {
        $opts = is_array($opts) ? $opts : [];
        $out  = [];

        // Sanitize text fields
        $out['maps_api_key'] = isset($opts['maps_api_key']) ? sanitize_text_field($opts['maps_api_key']) : '';

        // Sanitize filter position
        $out['filter_position'] = isset($opts['filter_position']) && in_array($opts['filter_position'], ['left', 'right', 'top'], true) ? $opts['filter_position'] : 'left';

        // Sanitize job listing columns
        $out['job_listing_columns'] = isset($opts['job_listing_columns']) && in_array($opts['job_listing_columns'], ['1', '2', '3'], true) ? $opts['job_listing_columns'] : '1';

        // Sanitize filter order
        if (isset($opts['filter_order']) && is_array($opts['filter_order'])) {
            $out['filter_order'] = array_map('sanitize_text_field', $opts['filter_order']);
        }

        // Sanitize checkbox fields (filters)
        $filters = [
            'filter_category',
            'filter_job_type',
            'filter_location',
            'filter_employer',
            'filter_salary',
            'filter_date_posted',
            'filter_sort',
        ];

        foreach ($filters as $filter) {
            $out[$filter] = isset($opts[$filter]) && $opts[$filter] === '1' ? '1' : '0';
        }

        // Preserve other keys if present
        $existing = get_option('careernest_options', []);
        if (is_array($existing)) {
            $out = array_merge($existing, $out);
        }
        return $out;
    }

    public function render_maps_api_field(array $args): void
    {
        $opts = get_option('careernest_options', []);
        $val  = isset($opts['maps_api_key']) ? (string) $opts['maps_api_key'] : '';
        echo '<input type="text" id="careernest_maps_api_key" name="careernest_options[maps_api_key]" class="regular-text" value="' . esc_attr($val) . '" placeholder="' . esc_attr__('AIzaSy...', 'careernest') . '" />';
        echo '<p class="description">' . esc_html__('Used for Google Maps features (e.g., location autocomplete).', 'careernest') . '</p>';
    }

    public function render_checkbox_field(array $args): void
    {
        $opts = get_option('careernest_options', []);
        $key  = $args['option_key'];
        // Default to enabled (1) if not set
        $checked = isset($opts[$key]) ? $opts[$key] === '1' : true;

        echo '<label>';
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="careernest_options[' . esc_attr($key) . ']" value="1" ' . checked($checked, true, false) . ' />';
        echo ' ' . esc_html__('Enable this filter', 'careernest');
        echo '</label>';
    }

    public function render_sortable_filters(array $args): void
    {
        $opts = get_option('careernest_options', []);

        // Default filter order
        $default_filters = [
            'search' => ['label' => __('Search', 'careernest'), 'enabled' => true, 'fixed' => true],
            'filter_category' => ['label' => __('Category', 'careernest'), 'enabled' => true],
            'filter_job_type' => ['label' => __('Job Type', 'careernest'), 'enabled' => true],
            'filter_location' => ['label' => __('Location', 'careernest'), 'enabled' => true],
            'filter_employer' => ['label' => __('Employer', 'careernest'), 'enabled' => true],
            'filter_salary' => ['label' => __('Salary Range', 'careernest'), 'enabled' => true],
            'filter_date_posted' => ['label' => __('Date Posted', 'careernest'), 'enabled' => true],
            'filter_sort' => ['label' => __('Sort By', 'careernest'), 'enabled' => true],
        ];

        // Get saved order
        $saved_order = isset($opts['filter_order']) ? $opts['filter_order'] : array_keys($default_filters);

        // Build ordered filters array
        $ordered_filters = [];
        foreach ($saved_order as $key) {
            if (isset($default_filters[$key])) {
                $filter = $default_filters[$key];
                $filter['key'] = $key;
                $filter['enabled'] = isset($opts[$key]) ? $opts[$key] === '1' : ($filter['enabled'] ?? true);
                $ordered_filters[] = $filter;
            }
        }

        // Add any missing filters
        foreach ($default_filters as $key => $filter) {
            if (!in_array($key, $saved_order, true)) {
                $filter['key'] = $key;
                $filter['enabled'] = isset($opts[$key]) ? $opts[$key] === '1' : ($filter['enabled'] ?? true);
                $ordered_filters[] = $filter;
            }
        }

        echo '<div class="cn-sortable-filters-container">';
        echo '<p class="description" style="margin-bottom: 1rem;">' . esc_html__('Drag to reorder filters. Toggle checkboxes to enable/disable. Search filter is always visible.', 'careernest') . '</p>';
        echo '<ul id="cn-sortable-filters" class="cn-sortable-filters">';

        foreach ($ordered_filters as $filter) {
            $is_fixed = isset($filter['fixed']) && $filter['fixed'];
            $checked = $filter['enabled'];
            $key = $filter['key'];

            echo '<li class="cn-filter-item' . ($is_fixed ? ' cn-filter-fixed' : '') . '" data-filter="' . esc_attr($key) . '">';

            if (!$is_fixed) {
                echo '<span class="cn-drag-handle dashicons dashicons-menu"></span>';
            } else {
                echo '<span class="cn-drag-handle dashicons dashicons-lock" style="opacity: 0.3;"></span>';
            }

            echo '<label>';
            if (!$is_fixed) {
                echo '<input type="checkbox" name="careernest_options[' . esc_attr($key) . ']" value="1" ' . checked($checked, true, false) . ' />';
            } else {
                echo '<input type="checkbox" checked disabled style="opacity: 0.5;" />';
                echo '<input type="hidden" name="careernest_options[' . esc_attr($key) . ']" value="1" />';
            }
            echo ' <strong>' . esc_html($filter['label']) . '</strong>';
            if ($is_fixed) {
                echo ' <em style="color: #666; font-size: 0.9em;">(' . esc_html__('Always visible', 'careernest') . ')</em>';
            }
            echo '</label>';
            echo '<input type="hidden" class="filter-order-input" name="careernest_options[filter_order][]" value="' . esc_attr($key) . '" />';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';

        // Output styles and script
?>
        <style>
            .cn-sortable-filters {
                list-style: none;
                margin: 0;
                padding: 0;
                max-width: 600px;
            }

            .cn-filter-item {
                background: white;
                border: 1px solid #ddd;
                padding: 12px 15px;
                margin-bottom: 8px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: move;
                transition: all 0.2s;
            }

            .cn-filter-item:hover {
                background: #f9f9f9;
                border-color: #0073aa;
            }

            .cn-filter-item.cn-dragging {
                opacity: 0.5;
                transform: scale(1.02);
            }

            .cn-filter-item.cn-filter-fixed {
                cursor: default;
                background: #f5f5f5;
            }

            .cn-filter-item.cn-filter-fixed:hover {
                background: #f5f5f5;
                border-color: #ddd;
            }

            .cn-drag-handle {
                color: #999;
                cursor: move;
            }

            .cn-filter-fixed .cn-drag-handle {
                cursor: default;
            }

            .cn-filter-item label {
                flex: 1;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }

            .cn-filter-fixed label {
                cursor: default;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                var list = document.getElementById('cn-sortable-filters');
                if (!list) return;

                list.addEventListener('dragstart', function(e) {
                    if (e.target.classList.contains('cn-filter-fixed')) {
                        e.preventDefault();
                        return;
                    }
                    e.target.classList.add('cn-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                list.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    var draggingEl = document.querySelector('.cn-dragging');
                    var afterEl = getDragAfterElement(list, e.clientY);
                    if (afterEl == null) {
                        list.appendChild(draggingEl);
                    } else {
                        list.insertBefore(draggingEl, afterEl);
                    }
                });

                list.addEventListener('dragend', function(e) {
                    e.target.classList.remove('cn-dragging');
                });

                var items = list.querySelectorAll('.cn-filter-item:not(.cn-filter-fixed)');
                items.forEach(function(item) {
                    item.setAttribute('draggable', 'true');
                });

                function getDragAfterElement(container, y) {
                    var els = Array.from(container.querySelectorAll('.cn-filter-item:not(.cn-dragging)'));
                    return els.reduce(function(closest, child) {
                        var box = child.getBoundingClientRect();
                        var offset = y - box.top - box.height / 2;
                        if (offset < 0 && offset > closest.offset) {
                            return {
                                offset: offset,
                                element: child
                            };
                        } else {
                            return closest;
                        }
                    }, {
                        offset: Number.NEGATIVE_INFINITY
                    }).element;
                }
            });
        </script>
    <?php
    }

    public function render_filter_position_field(array $args): void
    {
        $opts = get_option('careernest_options', []);
        $position = isset($opts['filter_position']) ? $opts['filter_position'] : 'left';

        echo '<select id="' . esc_attr($args['label_for']) . '" name="careernest_options[filter_position]">';
        echo '<option value="left" ' . selected($position, 'left', false) . '>' . esc_html__('Left Sidebar', 'careernest') . '</option>';
        echo '<option value="right" ' . selected($position, 'right', false) . '>' . esc_html__('Right Sidebar', 'careernest') . '</option>';
        echo '<option value="top" ' . selected($position, 'top', false) . '>' . esc_html__('Top Bar', 'careernest') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Choose where to display the job filters on the listing page.', 'careernest') . '</p>';
    }

    public function render_job_columns_field(array $args): void
    {
        $opts = get_option('careernest_options', []);
        $columns = isset($opts['job_listing_columns']) ? $opts['job_listing_columns'] : '1';

        echo '<select id="' . esc_attr($args['label_for']) . '" name="careernest_options[job_listing_columns]">';
        echo '<option value="1" ' . selected($columns, '1', false) . '>' . esc_html__('1 Column', 'careernest') . '</option>';
        echo '<option value="2" ' . selected($columns, '2', false) . '>' . esc_html__('2 Columns', 'careernest') . '</option>';
        echo '<option value="3" ' . selected($columns, '3', false) . '>' . esc_html__('3 Columns', 'careernest') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Number of columns for job listings. Automatically adjusts for mobile devices.', 'careernest') . '</p>';
    }

    /**
     * Render email template field
     */
    public function render_email_template_field(array $args): void
    {
        $template_key = $args['template_key'];
        $template_data = $args['template_data'];

        $templates = new \CareerNest\Email\Templates();
        $current_template = $templates->get_template($template_key);

        $subject = $current_template['subject'] ?? '';
        $body = $current_template['body'] ?? '';

        echo '<div class="cn-email-template-editor" style="margin-bottom: 2rem;">';

        // Subject field
        echo '<div style="margin-bottom: 1rem;">';
        echo '<label style="display: block; margin-bottom: 0.5rem;"><strong>' . esc_html__('Subject:', 'careernest') . '</strong></label>';
        echo '<input type="text" name="careernest_email_templates[' . esc_attr($template_key) . '][subject]" value="' . esc_attr($subject) . '" class="large-text" style="width: 100%;" />';
        echo '</div>';

        // Body field
        echo '<div style="margin-bottom: 1rem;">';
        echo '<label style="display: block; margin-bottom: 0.5rem;"><strong>' . esc_html__('Email Body:', 'careernest') . '</strong></label>';
        wp_editor(
            $body,
            'careernest_email_template_' . $template_key,
            [
                'textarea_name' => 'careernest_email_templates[' . $template_key . '][body]',
                'textarea_rows' => 12,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            ]
        );
        echo '</div>';

        // Available variables
        if (!empty($template_data['variables'])) {
            echo '<div style="margin-bottom: 1rem;">';
            echo '<p class="description"><strong>' . esc_html__('Available Variables:', 'careernest') . '</strong> ';
            $vars = array_map(function ($var) {
                return '{{' . $var . '}}';
            }, $template_data['variables']);
            echo esc_html(implode(', ', $vars));
            echo '</p>';
        }

        // Reset button
        echo '<p>';
        echo '<button type="button" class="button cn-reset-template" data-template="' . esc_attr($template_key) . '">';
        echo esc_html__('Reset to Default', 'careernest');
        echo '</button>';
        echo '</p>';

        echo '</div>';
    }

    /**
     * Sanitize email templates
     */
    public function sanitize_email_templates($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];

        foreach ($input as $template_key => $template) {
            if (!is_array($template)) {
                continue;
            }

            $sanitized[$template_key] = [
                'subject' => isset($template['subject']) ? sanitize_text_field($template['subject']) : '',
                'body' => isset($template['body']) ? wp_kses_post($template['body']) : '',
            ];
        }

        return $sanitized;
    }

    /**
     * Render color picker field
     */
    public function render_color_field(array $args): void
    {
        $opts = get_option('careernest_appearance', []);
        $key = $args['option_key'];
        $default = $args['default'] ?? '#0073aa';
        $description = $args['description'] ?? '';
        $value = isset($opts[$key]) ? $opts[$key] : $default;

        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="careernest_appearance[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="cn-color-picker" data-default-color="' . esc_attr($default) . '" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render container width field
     */
    public function render_container_width_field(array $args): void
    {
        $opts = get_option('careernest_appearance', []);
        $width = isset($opts['container_width']) ? $opts['container_width'] : '1200';

        echo '<select id="' . esc_attr($args['label_for']) . '" name="careernest_appearance[container_width]">';
        echo '<option value="1140" ' . selected($width, '1140', false) . '>1140px (Bootstrap Default)</option>';
        echo '<option value="1200" ' . selected($width, '1200', false) . '>1200px (Recommended)</option>';
        echo '<option value="1320" ' . selected($width, '1320', false) . '>1320px (Wide)</option>';
        echo '<option value="1400" ' . selected($width, '1400', false) . '>1400px (Extra Wide)</option>';
        echo '<option value="100" ' . selected($width, '100', false) . '>100% (Full Width)</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Maximum width for CareerNest pages and dashboards.', 'careernest') . '</p>';
    }

    /**
     * Sanitize appearance settings
     */
    public function sanitize_appearance($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];

        // Color fields with defaults
        $color_fields = [
            'primary_btn_color' => '#0073aa',
            'secondary_btn_color' => '#6c757d',
            'primary_text_color' => '#333333',
            'secondary_text_color' => '#666666',
            'success_badge_color' => '#10B981',
            'warning_badge_color' => '#f39c12',
            'danger_badge_color' => '#dc3545',
        ];

        foreach ($color_fields as $field => $default) {
            if (isset($input[$field])) {
                $color = sanitize_text_field($input[$field]);
                // Validate hex color
                if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                    $sanitized[$field] = $color;
                } else {
                    $sanitized[$field] = $default;
                }
            }
        }

        // Sanitize container width
        if (isset($input['container_width'])) {
            $width = sanitize_text_field($input['container_width']);
            $allowed = ['1140', '1200', '1320', '1400', '100'];
            $sanitized['container_width'] = in_array($width, $allowed, true) ? $width : '1200';
        }

        return $sanitized;
    }

    /**
     * Render number input field
     */
    public function render_number_field(array $args): void
    {
        $opts = get_option('careernest_employer_dashboard', []);
        $key = $args['option_key'];
        $default = $args['default'] ?? '5';
        $min = $args['min'] ?? '1';
        $max = $args['max'] ?? '99';
        $description = $args['description'] ?? '';
        $value = isset($opts[$key]) ? $opts[$key] : $default;

        echo '<input type="number" id="' . esc_attr($args['label_for']) . '" name="careernest_employer_dashboard[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="small-text" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render textarea field
     */
    public function render_textarea_field(array $args): void
    {
        $opts = get_option('careernest_employer_dashboard', []);
        $key = $args['option_key'];
        $description = $args['description'] ?? '';
        $value = isset($opts[$key]) ? $opts[$key] : '';

        echo '<textarea id="' . esc_attr($args['label_for']) . '" name="careernest_employer_dashboard[' . esc_attr($key) . ']" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Sanitize employer dashboard settings
     */
    public function sanitize_employer_dashboard($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];

        // Sanitize number fields
        if (isset($input['recent_jobs_count'])) {
            $count = (int) $input['recent_jobs_count'];
            $sanitized['recent_jobs_count'] = max(3, min(20, $count));
        }

        if (isset($input['recent_apps_count'])) {
            $count = (int) $input['recent_apps_count'];
            $sanitized['recent_apps_count'] = max(3, min(20, $count));
        }

        // Sanitize welcome message
        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = wp_kses_post($input['welcome_message']);
        }

        return $sanitized;
    }

    /**
     * Render generic text field
     */
    public function render_text_field(array $args): void
    {
        $option_group = $args['option_group'] ?? 'careernest_branding';
        $opts = get_option($option_group, []);
        $key = $args['option_key'];
        $default = $args['default'] ?? '';
        $description = $args['description'] ?? '';
        $placeholder = $args['placeholder'] ?? '';
        $type = $args['type'] ?? 'text';
        $value = isset($opts[$key]) ? $opts[$key] : $default;

        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($option_group) . '[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render logo upload field
     */
    public function render_logo_field(array $args): void
    {
        $opts = get_option('careernest_branding', []);
        $logo_id = isset($opts['platform_logo']) ? (int) $opts['platform_logo'] : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

        echo '<div class="cn-logo-upload-wrapper">';

        // Preview
        echo '<div class="cn-logo-preview" style="margin-bottom: 1rem;">';
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr__('Platform Logo', 'careernest') . '" style="max-width: 200px; max-height: 100px; display: block; border: 1px solid #ddd; padding: 10px; background: white;" />';
        } else {
            echo '<div style="padding: 2rem; border: 2px dashed #ddd; text-align: center; color: #999; max-width: 200px;">' . esc_html__('No logo uploaded', 'careernest') . '</div>';
        }
        echo '</div>';

        // Hidden input for logo ID
        echo '<input type="hidden" id="platform_logo" name="careernest_branding[platform_logo]" value="' . esc_attr($logo_id) . '" />';

        // Buttons
        echo '<button type="button" class="button cn-upload-logo-btn">' . esc_html__('Upload Logo', 'careernest') . '</button>';
        if ($logo_url) {
            echo ' <button type="button" class="button cn-remove-logo-btn">' . esc_html__('Remove Logo', 'careernest') . '</button>';
        }

        echo '<p class="description">' . esc_html__('Recommended size: 200x100px or similar aspect ratio. Used in emails and dashboards.', 'careernest') . '</p>';
        echo '</div>';

        // Add media uploader script
    ?>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;

                $('.cn-upload-logo-btn').on('click', function(e) {
                    e.preventDefault();

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: '<?php echo esc_js(__('Choose Platform Logo', 'careernest')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('Use this logo', 'careernest')); ?>'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#platform_logo').val(attachment.id);
                        $('.cn-logo-preview').html('<img src="' + attachment.url +
                            '" style="max-width: 200px; max-height: 100px; display: block; border: 1px solid #ddd; padding: 10px; background: white;" />'
                        );

                        // Add remove button if not present
                        if (!$('.cn-remove-logo-btn').length) {
                            $('.cn-upload-logo-btn').after(
                                ' <button type="button" class="button cn-remove-logo-btn"><?php echo esc_js(__('Remove Logo', 'careernest')); ?></button>'
                            );
                        }
                    });

                    mediaUploader.open();
                });

                $(document).on('click', '.cn-remove-logo-btn', function(e) {
                    e.preventDefault();
                    $('#platform_logo').val('');
                    $('.cn-logo-preview').html(
                        '<div style="padding: 2rem; border: 2px dashed #ddd; text-align: center; color: #999; max-width: 200px;"><?php echo esc_js(__('No logo uploaded', 'careernest')); ?></div>'
                    );
                    $(this).remove();
                });
            });
        </script>
<?php
    }

    /**
     * Sanitize branding settings
     */
    public function sanitize_branding($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];

        // Platform name
        if (isset($input['platform_name'])) {
            $sanitized['platform_name'] = sanitize_text_field($input['platform_name']);
            if (empty($sanitized['platform_name'])) {
                $sanitized['platform_name'] = 'CareerNest';
            }
        }

        // Platform logo (attachment ID)
        if (isset($input['platform_logo'])) {
            $sanitized['platform_logo'] = (int) $input['platform_logo'];
        }

        // Email from name
        if (isset($input['email_from_name'])) {
            $sanitized['email_from_name'] = sanitize_text_field($input['email_from_name']);
        }

        // Email from address
        if (isset($input['email_from_address'])) {
            $email = sanitize_email($input['email_from_address']);
            $sanitized['email_from_address'] = is_email($email) ? $email : '';
        }

        // Support email
        if (isset($input['support_email'])) {
            $email = sanitize_email($input['support_email']);
            $sanitized['support_email'] = is_email($email) ? $email : '';
        }

        return $sanitized;
    }
}

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
    }

    public function register_settings(): void
    {
        register_setting('careernest_options_group', 'careernest_options', [$this, 'sanitize_options']);

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
                echo '<p class="description">' . esc_html__('Enable or disable specific filters on the job listing page.', 'careernest') . '</p>';
            },
            'careernest_settings'
        );

        $filters = [
            'filter_category' => __('Category Filter', 'careernest'),
            'filter_job_type' => __('Job Type Filter', 'careernest'),
            'filter_location' => __('Location Filter', 'careernest'),
            'filter_employer' => __('Employer Filter', 'careernest'),
            'filter_salary' => __('Salary Range Filter', 'careernest'),
            'filter_date_posted' => __('Date Posted Filter', 'careernest'),
            'filter_sort' => __('Sort By Options', 'careernest'),
        ];

        foreach ($filters as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [$this, 'render_checkbox_field'],
                'careernest_settings',
                'careernest_filters_section',
                [
                    'label_for' => 'careernest_' . $key,
                    'option_key' => $key,
                ]
            );
        }
    }

    public function sanitize_options($opts)
    {
        $opts = is_array($opts) ? $opts : [];
        $out  = [];

        // Sanitize text fields
        $out['maps_api_key'] = isset($opts['maps_api_key']) ? sanitize_text_field($opts['maps_api_key']) : '';

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
}

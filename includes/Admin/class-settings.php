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

        // Sortable filter list
        add_settings_field(
            'filters_order',
            __('Filter Order & Visibility', 'careernest'),
            [$this, 'render_sortable_filters'],
            'careernest_settings',
            'careernest_filters_section',
            ['label_for' => 'careernest_filters_order']
        );
    }

    public function sanitize_options($opts)
    {
        $opts = is_array($opts) ? $opts : [];
        $out  = [];

        // Sanitize text fields
        $out['maps_api_key'] = isset($opts['maps_api_key']) ? sanitize_text_field($opts['maps_api_key']) : '';

        // Sanitize filter position
        $out['filter_position'] = isset($opts['filter_position']) && in_array($opts['filter_position'], ['left', 'right', 'top'], true) ? $opts['filter_position'] : 'left';

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
}

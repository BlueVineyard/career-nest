<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import/Export Class
 * 
 * Handles CSV import and export for Jobs and Employers
 */
class Import_Export
{
    /**
     * Hook into WordPress
     */
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page'], 30);
        add_action('admin_post_cn_download_job_template', [$this, 'download_job_template']);
        add_action('admin_post_cn_export_jobs', [$this, 'export_jobs']);
        add_action('admin_post_cn_import_jobs', [$this, 'import_jobs']);
        add_action('wp_ajax_cn_get_employer_team', [$this, 'ajax_get_employer_team']);
    }

    /**
     * AJAX handler to get team members for an employer
     */
    public function ajax_get_employer_team(): void
    {
        if (!current_user_can('manage_careernest')) {
            wp_send_json_error(['message' => 'Access denied']);
        }

        check_ajax_referer('cn_import_export_nonce', 'nonce');

        $employer_id = isset($_POST['employer_id']) ? absint($_POST['employer_id']) : 0;

        if (!$employer_id) {
            wp_send_json_error(['message' => 'Invalid employer ID']);
        }

        // Get team members for this employer
        $team_users = get_users([
            'role' => 'employer_team',
            'meta_key' => '_employer_id',
            'meta_value' => $employer_id,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        $options = [];
        foreach ($team_users as $user) {
            $is_owner = get_user_meta($user->ID, '_is_owner', true);
            $label = $user->display_name;
            if ($is_owner) {
                $label .= ' (Owner)';
            }

            $options[] = [
                'id' => $user->ID,
                'name' => $label,
                'is_owner' => (bool) $is_owner
            ];
        }

        wp_send_json_success(['team_members' => $options]);
    }

    /**
     * Add admin menu page
     */
    public function add_menu_page(): void
    {
        add_submenu_page(
            'careernest',
            __('Import/Export', 'careernest'),
            __('Import/Export', 'careernest'),
            'manage_careernest',
            'careernest-import-export',
            [$this, 'render_page']
        );
    }

    /**
     * Render import/export page
     */
    public function render_page(): void
    {
        if (!current_user_can('manage_careernest')) {
            wp_die(__('Access denied.', 'careernest'));
        }

        // Handle any messages from redirects
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

?>
        <div class="wrap">
            <h1><?php echo esc_html__('Import/Export', 'careernest'); ?></h1>

            <?php if ($message): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html(urldecode($message)); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html(urldecode($error)); ?></p>
                </div>
            <?php endif; ?>

            <div class="cn-import-export-container">
                <!-- Jobs Section -->
                <div class="cn-ie-section">
                    <h2><?php echo esc_html__('Job Listings', 'careernest'); ?></h2>

                    <!-- Download Template -->
                    <div class="cn-ie-card">
                        <h3><?php echo esc_html__('1. Download CSV Template', 'careernest'); ?></h3>
                        <p><?php echo esc_html__('Download the CSV template to see the required format for importing jobs.', 'careernest'); ?>
                        </p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('cn_download_job_template', 'cn_template_nonce'); ?>
                            <input type="hidden" name="action" value="cn_download_job_template">
                            <button type="submit" class="button button-secondary">
                                <?php echo esc_html__('Download Job Template', 'careernest'); ?>
                            </button>
                        </form>
                    </div>

                    <!-- Export Jobs -->
                    <div class="cn-ie-card">
                        <h3><?php echo esc_html__('2. Export Existing Jobs', 'careernest'); ?></h3>
                        <p><?php echo esc_html__('Export all published job listings to a CSV file.', 'careernest'); ?></p>
                        <?php
                        $job_count = wp_count_posts('job_listing');
                        $published_jobs = $job_count->publish;
                        ?>
                        <p><strong><?php echo sprintf(__('%d published jobs available for export', 'careernest'), $published_jobs); ?></strong>
                        </p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('cn_export_jobs', 'cn_export_nonce'); ?>
                            <input type="hidden" name="action" value="cn_export_jobs">
                            <button type="submit" class="button button-secondary"
                                <?php echo $published_jobs == 0 ? 'disabled' : ''; ?>>
                                <?php echo esc_html__('Export Jobs to CSV', 'careernest'); ?>
                            </button>
                        </form>
                    </div>

                    <!-- Import Jobs -->
                    <div class="cn-ie-card">
                        <h3><?php echo esc_html__('3. Import Jobs from CSV', 'careernest'); ?></h3>
                        <p><?php echo esc_html__('Upload a CSV file to bulk import job listings. All jobs in the file will be assigned to the selected employer and status.', 'careernest'); ?>
                        </p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                            enctype="multipart/form-data">
                            <?php wp_nonce_field('cn_import_jobs', 'cn_import_nonce'); ?>
                            <input type="hidden" name="action" value="cn_import_jobs">

                            <p>
                                <label
                                    for="import_employer_id"><strong><?php echo esc_html__('Select Employer', 'careernest'); ?></strong></label><br>
                                <select name="import_employer_id" id="import_employer_id" required style="min-width: 300px;">
                                    <option value=""><?php echo esc_html__('-- Select Employer --', 'careernest'); ?></option>
                                    <?php
                                    $employers = get_posts([
                                        'post_type' => 'employer',
                                        'post_status' => 'publish',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC'
                                    ]);
                                    foreach ($employers as $employer):
                                    ?>
                                        <option value="<?php echo esc_attr($employer->ID); ?>">
                                            <?php echo esc_html($employer->post_title); ?> (ID:
                                            <?php echo esc_html($employer->ID); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <p id="posted_by_container" style="display: none;">
                                <label
                                    for="import_posted_by"><strong><?php echo esc_html__('Posted By', 'careernest'); ?></strong></label><br>
                                <select name="import_posted_by" id="import_posted_by" required style="min-width: 300px;">
                                    <option value=""><?php echo esc_html__('-- Select Team Member --', 'careernest'); ?>
                                    </option>
                                </select>
                            </p>

                            <p>
                                <label
                                    for="import_job_status"><strong><?php echo esc_html__('Job Status', 'careernest'); ?></strong></label><br>
                                <select name="import_job_status" id="import_job_status" required style="min-width: 300px;">
                                    <option value="publish"><?php echo esc_html__('Published', 'careernest'); ?></option>
                                    <option value="draft"><?php echo esc_html__('Draft', 'careernest'); ?></option>
                                </select>
                            </p>

                            <p>
                                <label
                                    for="import_file"><strong><?php echo esc_html__('CSV File', 'careernest'); ?></strong></label><br>
                                <input type="file" name="import_file" id="import_file" accept=".csv" required>
                            </p>

                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Import Jobs', 'careernest'); ?>
                            </button>
                        </form>

                        <script>
                            jQuery(document).ready(function($) {
                                $('#import_employer_id').on('change', function() {
                                    const employerId = $(this).val();
                                    const postedByContainer = $('#posted_by_container');
                                    const postedBySelect = $('#import_posted_by');

                                    if (!employerId) {
                                        postedByContainer.hide();
                                        postedBySelect.html('<option value="">-- Select Team Member --</option>');
                                        return;
                                    }

                                    // Show loading
                                    postedBySelect.html('<option value="">Loading...</option>');
                                    postedByContainer.show();

                                    // Fetch team members
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'cn_get_employer_team',
                                            employer_id: employerId,
                                            nonce: '<?php echo wp_create_nonce('cn_import_export_nonce'); ?>'
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                let options =
                                                    '<option value="">-- Select Team Member --</option>';
                                                response.data.team_members.forEach(function(member) {
                                                    options += '<option value="' + member.id +
                                                        '">' + member.name + '</option>';
                                                });
                                                postedBySelect.html(options);
                                            } else {
                                                postedBySelect.html(
                                                    '<option value="">No team members found</option>'
                                                );
                                            }
                                        },
                                        error: function() {
                                            postedBySelect.html(
                                                '<option value="">Error loading team members</option>'
                                            );
                                        }
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>

                <!-- Future: Employers Section -->
                <div class="cn-ie-section" style="opacity: 0.5;">
                    <h2><?php echo esc_html__('Employers (Coming Soon)', 'careernest'); ?></h2>
                    <p><?php echo esc_html__('Employer import/export functionality will be available in a future update.', 'careernest'); ?>
                    </p>
                </div>
            </div>
        </div>

        <style>
            .cn-import-export-container {
                max-width: 1200px;
            }

            .cn-ie-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }

            .cn-ie-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #ccd0d4;
            }

            .cn-ie-card {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 20px;
                margin: 15px 0;
            }

            .cn-ie-card h3 {
                margin-top: 0;
                color: #0073aa;
            }

            .cn-ie-card p {
                margin: 10px 0;
            }

            .cn-ie-card form {
                margin-top: 15px;
            }
        </style>
<?php
    }

    /**
     * Download job CSV template
     */
    public function download_job_template(): void
    {
        if (!current_user_can('manage_careernest')) {
            wp_die(__('Access denied.', 'careernest'));
        }

        check_admin_referer('cn_download_job_template', 'cn_template_nonce');

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=job-import-template.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // CSV Headers (employer_id, job_status, job_location removed - set via dropdowns/employer on import)
        $headers = [
            'job_title',
            'job_category_id',
            'job_type_id',
            'salary_range',
            'opening_date',
            'closing_date',
            'remote_position',
            'apply_externally',
            'external_apply',
            'overview',
            'who_we_are',
            'what_we_offer',
            'key_responsibilities',
            'how_to_apply'
        ];

        fputcsv($output, $headers);

        // Example row
        $example = [
            'Senior Software Engineer',
            '1',
            '2',
            '$80,000 - $120,000 per year',
            '2025-11-01',
            '2025-12-31',
            '0',
            '0',
            '',
            'We are looking for an experienced software engineer to join our team...',
            'We are a fast-growing tech company focused on innovation...',
            'Competitive salary, flexible hours, health benefits, professional development...',
            'Design and develop software solutions. Collaborate with team members. Write clean code...',
            'Submit your resume and cover letter through our application system.'
        ];

        fputcsv($output, $example);

        fclose($output);
        exit;
    }

    /**
     * Export jobs to CSV
     */
    public function export_jobs(): void
    {
        if (!current_user_can('manage_careernest')) {
            wp_die(__('Access denied.', 'careernest'));
        }

        check_admin_referer('cn_export_jobs', 'cn_export_nonce');

        // Get all published jobs
        $jobs = get_posts([
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if (empty($jobs)) {
            wp_redirect(add_query_arg([
                'page' => 'careernest-import-export',
                'error' => urlencode('No jobs found to export.')
            ], admin_url('admin.php')));
            exit;
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=jobs-export-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // CSV Headers
        $headers = [
            'job_id',
            'job_title',
            'job_category_id',
            'job_type_id',
            'job_location',
            'salary_range',
            'opening_date',
            'closing_date',
            'remote_position',
            'apply_externally',
            'external_apply',
            'overview',
            'who_we_are',
            'what_we_offer',
            'key_responsibilities',
            'how_to_apply',
            'employer_id',
            'job_status'
        ];

        fputcsv($output, $headers);

        // Export each job
        foreach ($jobs as $job) {
            // Get taxonomy term IDs
            $category_id = '';
            $category_terms = get_the_terms($job->ID, 'job_category');
            if ($category_terms && !is_wp_error($category_terms)) {
                $category_id = $category_terms[0]->term_id;
            }

            $type_id = '';
            $type_terms = get_the_terms($job->ID, 'job_type');
            if ($type_terms && !is_wp_error($type_terms)) {
                $type_id = $type_terms[0]->term_id;
            }

            $row = [
                $job->ID,
                $job->post_title,
                $category_id,
                $type_id,
                get_post_meta($job->ID, '_job_location', true),
                get_post_meta($job->ID, '_salary_range', true),
                get_post_meta($job->ID, '_opening_date', true),
                get_post_meta($job->ID, '_closing_date', true),
                get_post_meta($job->ID, '_remote_position', true) ? '1' : '0',
                get_post_meta($job->ID, '_apply_externally', true) ? '1' : '0',
                get_post_meta($job->ID, '_external_apply', true),
                wp_strip_all_tags(get_post_meta($job->ID, '_job_overview', true)),
                wp_strip_all_tags(get_post_meta($job->ID, '_job_who_we_are', true)),
                wp_strip_all_tags(get_post_meta($job->ID, '_job_what_we_offer', true)),
                wp_strip_all_tags(get_post_meta($job->ID, '_job_responsibilities', true)),
                wp_strip_all_tags(get_post_meta($job->ID, '_job_how_to_apply', true)),
                get_post_meta($job->ID, '_employer_id', true),
                $job->post_status
            ];

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Import jobs from CSV
     */
    public function import_jobs(): void
    {
        if (!current_user_can('manage_careernest')) {
            wp_die(__('Access denied.', 'careernest'));
        }

        check_admin_referer('cn_import_jobs', 'cn_import_nonce');

        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg([
                'page' => 'careernest-import-export',
                'error' => urlencode('Please select a CSV file to import.')
            ], admin_url('admin.php')));
            exit;
        }

        $file = $_FILES['import_file'];

        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            wp_redirect(add_query_arg([
                'page' => 'careernest-import-export',
                'error' => urlencode('Invalid file type. Please upload a CSV file.')
            ], admin_url('admin.php')));
            exit;
        }

        // Process CSV
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            wp_redirect(add_query_arg([
                'page' => 'careernest-import-export',
                'error' => urlencode('Failed to read CSV file.')
            ], admin_url('admin.php')));
            exit;
        }

        $headers = fgetcsv($handle);
        $imported = 0;
        $errors = [];

        while (($data = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }

            // Map CSV columns to array
            $job_data = array_combine($headers, $data);

            // Validate required fields
            if (empty($job_data['job_title'])) {
                $errors[] = 'Row skipped: Missing job title';
                continue;
            }

            if (empty($job_data['overview'])) {
                $errors[] = 'Row skipped: Missing overview for "' . $job_data['job_title'] . '"';
                continue;
            }

            // Get employer ID and status from form dropdowns
            $employer_id = isset($_POST['import_employer_id']) ? absint($_POST['import_employer_id']) : 0;
            $job_status = isset($_POST['import_job_status']) ? sanitize_text_field($_POST['import_job_status']) : 'publish';

            // Validate employer
            if (!$employer_id || !get_post($employer_id)) {
                $errors[] = 'Row skipped: Invalid employer selected';
                continue;
            }

            // Create job post
            $post_data = [
                'post_type' => 'job_listing',
                'post_title' => sanitize_text_field($job_data['job_title']),
                'post_status' => $job_status,
                'post_author' => get_current_user_id()
            ];

            $job_id = wp_insert_post($post_data);

            if (is_wp_error($job_id)) {
                $errors[] = 'Failed to create job: ' . $job_data['job_title'];
                continue;
            }

            // Save employer ID
            update_post_meta($job_id, '_employer_id', $employer_id);

            // Get posted_by from dropdown
            $posted_by_user_id = isset($_POST['import_posted_by']) ? absint($_POST['import_posted_by']) : 0;

            // Set posted_by to selected user, or fallback to admin
            if ($posted_by_user_id) {
                update_post_meta($job_id, '_posted_by', $posted_by_user_id);
            } else {
                update_post_meta($job_id, '_posted_by', get_current_user_id());
            }

            // Use employer's location for job location
            $employer_location = get_post_meta($employer_id, '_location', true);
            if ($employer_location) {
                update_post_meta($job_id, '_job_location', $employer_location);
            }

            // Set taxonomies
            if (!empty($job_data['job_category_id'])) {
                wp_set_post_terms($job_id, [absint($job_data['job_category_id'])], 'job_category');
            }

            if (!empty($job_data['job_type_id'])) {
                wp_set_post_terms($job_id, [absint($job_data['job_type_id'])], 'job_type');
            }

            // Save basic meta fields (job_location now comes from employer, not CSV)
            if (!empty($job_data['salary_range'])) {
                update_post_meta($job_id, '_salary_range', sanitize_text_field($job_data['salary_range']));
            }

            if (!empty($job_data['opening_date'])) {
                update_post_meta($job_id, '_opening_date', sanitize_text_field($job_data['opening_date']));
            }

            if (!empty($job_data['closing_date'])) {
                update_post_meta($job_id, '_closing_date', sanitize_text_field($job_data['closing_date']));
            }

            // Save boolean fields
            update_post_meta($job_id, '_remote_position', !empty($job_data['remote_position']) && $job_data['remote_position'] === '1' ? 1 : 0);
            update_post_meta($job_id, '_apply_externally', !empty($job_data['apply_externally']) && $job_data['apply_externally'] === '1' ? 1 : 0);

            // Save external apply URL/email
            if (!empty($job_data['external_apply'])) {
                update_post_meta($job_id, '_external_apply', sanitize_text_field($job_data['external_apply']));
            }

            // Save rich text content fields
            update_post_meta($job_id, '_job_overview', wp_kses_post($job_data['overview']));

            if (!empty($job_data['who_we_are'])) {
                update_post_meta($job_id, '_job_who_we_are', wp_kses_post($job_data['who_we_are']));
            }

            if (!empty($job_data['what_we_offer'])) {
                update_post_meta($job_id, '_job_what_we_offer', wp_kses_post($job_data['what_we_offer']));
            }

            if (!empty($job_data['key_responsibilities'])) {
                update_post_meta($job_id, '_job_responsibilities', wp_kses_post($job_data['key_responsibilities']));
            }

            if (!empty($job_data['how_to_apply'])) {
                update_post_meta($job_id, '_job_how_to_apply', wp_kses_post($job_data['how_to_apply']));
            }

            $imported++;
        }

        fclose($handle);

        // Redirect with success message
        $message = sprintf(__('%d job(s) imported successfully.', 'careernest'), $imported);
        if (!empty($errors)) {
            $message .= ' ' . sprintf(__('%d error(s) occurred.', 'careernest'), count($errors));
        }

        wp_redirect(add_query_arg([
            'page' => 'careernest-import-export',
            'message' => urlencode($message)
        ], admin_url('admin.php')));
        exit;
    }
}

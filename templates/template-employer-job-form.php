<?php

/**
 * Template: CareerNest — Employer Job Form (Add/Edit)
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('employer_team', (array) $current_user->roles, true)) {
    wp_die(__('Access denied.', 'careernest'));
}

$employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
if (!$employer_id) {
    wp_die(__('No employer linked.', 'careernest'));
}

$employer = get_post($employer_id);
$company_name = $employer ? $employer->post_title : '';
$company_logo = get_the_post_thumbnail_url($employer_id, 'medium');
$company_website = get_post_meta($employer_id, '_website', true);

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add-job';
$job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
$is_edit = ($action === 'edit-job' && $job_id > 0);

if ($is_edit && (int) get_post_meta($job_id, '_employer_id', true) !== $employer_id) {
    wp_die(__('Permission denied.', 'careernest'));
}

$pages = get_option('careernest_pages', []);
$dashboard_url = isset($pages['employer-dashboard']) ? get_permalink($pages['employer-dashboard']) : home_url();

$job_data = [];
if ($is_edit) {
    $job = get_post($job_id);
    $job_data = [
        'title' => $job->post_title,
        'location' => get_post_meta($job_id, '_job_location', true),
        'remote' => (bool) get_post_meta($job_id, '_remote_position', true),
        'opening_date' => get_post_meta($job_id, '_opening_date', true),
        'closing_date' => get_post_meta($job_id, '_closing_date', true),
        'salary_range' => get_post_meta($job_id, '_salary_range', true),
        'apply_externally' => (bool) get_post_meta($job_id, '_apply_externally', true),
        'external_apply' => get_post_meta($job_id, '_external_apply', true),
        'overview' => get_post_meta($job_id, '_job_overview', true),
        'who_we_are' => get_post_meta($job_id, '_job_who_we_are', true),
        'what_we_offer' => get_post_meta($job_id, '_job_what_we_offer', true),
        'key_responsibilities' => get_post_meta($job_id, '_job_responsibilities', true),
        'how_to_apply' => get_post_meta($job_id, '_job_how_to_apply', true),
    ];
}

// FORM PROCESSING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_job_nonce'])) {
    if (!wp_verify_nonce($_POST['cn_job_nonce'], 'cn_submit_job')) {
        $error_message = 'Security check failed.';
    } else {
        $job_title = sanitize_text_field($_POST['job_title'] ?? '');

        if (empty($job_title)) {
            $error_message = 'Job title is required.';
        } else {
            // Get the action from hidden field set by JavaScript
            $submit_action = isset($_POST['submit_action']) ? sanitize_text_field($_POST['submit_action']) : 'publish';
            $status = ($submit_action === 'draft') ? 'draft' : 'publish';

            $job_data_to_save = [
                'post_type' => 'job_listing',
                'post_title' => $job_title,
                'post_status' => $status,
                'post_author' => $current_user->ID,
            ];

            if ($is_edit) {
                $job_data_to_save['ID'] = $job_id;
                $result = wp_update_post($job_data_to_save);
            } else {
                $result = wp_insert_post($job_data_to_save);
                if (!is_wp_error($result)) {
                    $job_id = $result;
                    update_post_meta($job_id, '_employer_id', $employer_id);
                    update_post_meta($job_id, '_posted_by', $current_user->ID);
                }
            }

            if (!is_wp_error($result)) {
                // Save taxonomies
                if (isset($_POST['job_category']) && !empty($_POST['job_category'])) {
                    $category_id = absint($_POST['job_category']);
                    wp_set_post_terms($job_id, [$category_id], 'job_category');
                }

                if (isset($_POST['job_type']) && !empty($_POST['job_type'])) {
                    $type_id = absint($_POST['job_type']);
                    wp_set_post_terms($job_id, [$type_id], 'job_type');
                }

                // Save meta
                update_post_meta($job_id, '_job_location', sanitize_text_field($_POST['job_location'] ?? ''));
                update_post_meta($job_id, '_remote_position', !empty($_POST['remote_position']) ? 1 : 0);
                update_post_meta($job_id, '_opening_date', sanitize_text_field($_POST['opening_date'] ?? ''));
                update_post_meta($job_id, '_closing_date', sanitize_text_field($_POST['closing_date'] ?? ''));
                update_post_meta($job_id, '_salary_range', sanitize_text_field($_POST['salary_range'] ?? ''));
                update_post_meta($job_id, '_apply_externally', !empty($_POST['apply_externally']) ? 1 : 0);

                $ext = sanitize_text_field($_POST['external_apply'] ?? '');
                $ext ? update_post_meta($job_id, '_external_apply', $ext) : delete_post_meta($job_id, '_external_apply');

                $ov = wp_kses_post($_POST['overview'] ?? '');
                $ov ? update_post_meta($job_id, '_job_overview', $ov) : delete_post_meta($job_id, '_job_overview');

                $wwa = wp_kses_post($_POST['who_we_are'] ?? '');
                $wwa ? update_post_meta($job_id, '_job_who_we_are', $wwa) : delete_post_meta($job_id, '_job_who_we_are');

                $wwo = wp_kses_post($_POST['what_we_offer'] ?? '');
                $wwo ? update_post_meta($job_id, '_job_what_we_offer', $wwo) : delete_post_meta($job_id, '_job_what_we_offer');

                $resp = wp_kses_post($_POST['key_responsibilities'] ?? '');
                $resp ? update_post_meta($job_id, '_job_responsibilities', $resp) : delete_post_meta($job_id, '_job_responsibilities');

                $hta = wp_kses_post($_POST['how_to_apply'] ?? '');
                $hta ? update_post_meta($job_id, '_job_how_to_apply', $hta) : delete_post_meta($job_id, '_job_how_to_apply');

                // Redirect based on status
                $redirect = ($status === 'draft') ? $dashboard_url : get_permalink($job_id);
                wp_safe_redirect($redirect);
                exit;
            } else {
                $error_message = 'Failed to save job.';
            }
        }
    }
}

// Enqueue custom dropdown assets
wp_enqueue_style('careernest-custom-dropdown', CAREERNEST_URL . 'assets/css/custom-dropdown.css', [], CAREERNEST_VERSION);
wp_enqueue_script('careernest-custom-dropdown', CAREERNEST_URL . 'assets/js/custom-dropdown.js', ['jquery'], CAREERNEST_VERSION, true);

get_header();
?>

<main id="primary" class="site-main cn-job-form-page">
    <div class="cn-job-form-container">
        <div class="cn-job-form-header">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-back-btn">
                <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 7H15M1 7L7 13M1 7L7 1" stroke="#0073aa" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span><?php echo esc_html__('Back to Dashboard', 'careernest'); ?></span>
            </a>
            <h1><?php echo $is_edit ? esc_html__('Edit Job', 'careernest') : esc_html__('Add Job', 'careernest'); ?>
            </h1>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="cn-form-error">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="cn-job-form-layout">
            <form method="post" id="cn-job-submit-form">
                <?php wp_nonce_field('cn_submit_job', 'cn_job_nonce'); ?>
                <input type="hidden" name="submit_action" id="submit_action" value="publish">

                <div class="cn-form-card" id="jobDetails">
                    <h2 class="cn-form-card-title">Job Details</h2>

                    <div class="cn-form-field">
                        <label for="job_title">Job Title <span class="required">*</span></label>
                        <div class="cn-input-with-icon">
                            <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <polygon points="18,2 22,6 12,16 8,16 8,12 18,2" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <input type="text" id="job_title" name="job_title" class="cn-input cn-input-with-icon-field"
                                required value="<?php echo esc_attr($job_data['title'] ?? ''); ?>"
                                placeholder="e.g., Senior Software Engineer">
                        </div>
                    </div>

                    <div class="cn-form-row">
                        <div class="cn-form-field">
                            <label for="job_category">Job Category <span class="required">*</span></label>
                            <div class="cn-custom-select-wrapper" data-icon="folder">
                                <select name="job_category" id="job_category" class="cn-filter-select cn-custom-select"
                                    required>
                                    <option value="">Select Category...</option>
                                    <?php
                                    $selected_category = 0;
                                    if ($is_edit) {
                                        $terms = get_the_terms($job_id, 'job_category');
                                        if ($terms && !is_wp_error($terms)) {
                                            $selected_category = $terms[0]->term_id;
                                        }
                                    }
                                    $categories = get_terms(['taxonomy' => 'job_category', 'hide_empty' => false]);
                                    if ($categories && !is_wp_error($categories)):
                                        foreach ($categories as $category):
                                    ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>"
                                                <?php selected($selected_category, $category->term_id); ?>>
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="cn-form-field">
                            <label for="job_type">Job Type <span class="required">*</span></label>
                            <div class="cn-custom-select-wrapper" data-icon="layers">
                                <select name="job_type" id="job_type" class="cn-filter-select cn-custom-select"
                                    required>
                                    <option value="">Select Type...</option>
                                    <?php
                                    $selected_type = 0;
                                    if ($is_edit) {
                                        $terms = get_the_terms($job_id, 'job_type');
                                        if ($terms && !is_wp_error($terms)) {
                                            $selected_type = $terms[0]->term_id;
                                        }
                                    }
                                    $job_types = get_terms(['taxonomy' => 'job_type', 'hide_empty' => false]);
                                    if ($job_types && !is_wp_error($job_types)):
                                        foreach ($job_types as $type):
                                    ?>
                                            <option value="<?php echo esc_attr($type->term_id); ?>"
                                                <?php selected($selected_type, $type->term_id); ?>>
                                                <?php echo esc_html($type->name); ?>
                                            </option>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="cn-form-row">
                        <div class="cn-form-field">
                            <label for="job_location">Location <span class="required">*</span></label>
                            <div class="cn-input-with-icon">
                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 20 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor"
                                        stroke-width="1.5" />
                                </svg>
                                <input type="text" id="job_location" name="job_location"
                                    class="cn-input cn-input-with-icon-field" required
                                    value="<?php echo esc_attr($job_data['location'] ?? ''); ?>"
                                    placeholder="e.g., Melbourne, VIC">
                            </div>
                        </div>
                        <div class="cn-form-field">
                            <label for="salary_range">Salary Range</label>
                            <div class="cn-input-with-icon">
                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 20 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="10" cy="10.5" r="8.33333" stroke="currentColor" stroke-width="1.5" />
                                    <path d="M10 5.5V15.5" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" />
                                    <path
                                        d="M12.5 8.41683C12.5 7.26624 11.3807 6.3335 10 6.3335C8.61929 6.3335 7.5 7.26624 7.5 8.41683C7.5 9.56742 8.61929 10.5002 10 10.5002C11.3807 10.5002 12.5 11.4329 12.5 12.5835C12.5 13.7341 11.3807 14.6668 10 14.6668C8.61929 14.6668 7.5 13.7341 7.5 12.5835"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                                <input type="text" id="salary_range" name="salary_range"
                                    class="cn-input cn-input-with-icon-field"
                                    value="<?php echo esc_attr($job_data['salary_range'] ?? ''); ?>"
                                    placeholder="e.g., $80,000 - $120,000 per year">
                            </div>
                        </div>
                    </div>

                    <div class="cn-form-row">
                        <div class="cn-form-field">
                            <label for="opening_date">Opening Date <span class="required">*</span></label>
                            <input type="date" id="opening_date" name="opening_date" class="cn-input" required
                                min="<?php echo esc_attr(date('Y-m-d')); ?>"
                                value="<?php echo esc_attr($job_data['opening_date'] ?? ''); ?>">
                        </div>
                        <div class="cn-form-field">
                            <label for="closing_date">Application Close Date <span class="required">*</span></label>
                            <input type="date" id="closing_date" name="closing_date" class="cn-input" required
                                min="<?php echo esc_attr(!empty($job_data['opening_date']) ? $job_data['opening_date'] : date('Y-m-d')); ?>"
                                value="<?php echo esc_attr($job_data['closing_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Update closing date min when opening date changes
                            const openingDate = document.getElementById('opening_date');
                            const closingDate = document.getElementById('closing_date');

                            if (openingDate && closingDate) {
                                openingDate.addEventListener('change', function() {
                                    if (this.value) {
                                        // Set closing date min to opening date
                                        closingDate.min = this.value;

                                        // If closing date is before opening date, clear it
                                        if (closingDate.value && closingDate.value < this.value) {
                                            closingDate.value = '';
                                        }
                                    } else {
                                        // Reset to today if opening date is cleared
                                        closingDate.min = '<?php echo esc_js(date('Y-m-d')); ?>';
                                    }
                                });
                            }

                            // Validate overview field on form submit
                            const form = document.getElementById('cn-job-submit-form');
                            if (form) {
                                form.addEventListener('submit', function(e) {
                                    // Get TinyMCE content
                                    if (typeof tinymce !== 'undefined') {
                                        const editor = tinymce.get('overview');
                                        if (editor) {
                                            const content = editor.getContent({
                                                format: 'text'
                                            }).trim();
                                            if (content === '') {
                                                e.preventDefault();
                                                alert(
                                                    'Overview is required. Please provide a job overview.'
                                                );
                                                // Focus the editor
                                                editor.focus();
                                                return false;
                                            }
                                        }
                                    }
                                    // Also check textarea fallback
                                    const overviewTextarea = document.querySelector(
                                        'textarea[name="overview"]');
                                    if (overviewTextarea && overviewTextarea.value.trim() === '') {
                                        e.preventDefault();
                                        alert('Overview is required. Please provide a job overview.');
                                        overviewTextarea.focus();
                                        return false;
                                    }
                                });
                            }
                        });
                    </script>

                    <div class="cn-form-field">
                        <label>
                            <input type="checkbox" id="remote_position" name="remote_position" value="1"
                                <?php checked($job_data['remote'] ?? false, true); ?>>
                            Remote Position
                        </label>
                    </div>

                    <div class="cn-form-field">
                        <label>
                            <input type="checkbox" id="apply_externally" name="apply_externally" value="1"
                                <?php checked($job_data['apply_externally'] ?? false, true); ?>>
                            Applications handled externally
                        </label>
                        <div id="external-apply-container"
                            style="margin-top: 0.5rem; <?php echo ($job_data['apply_externally'] ?? false) ? '' : 'display: none;'; ?>">
                            <div class="cn-input-with-icon">
                                <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <input type="text" id="external_apply" name="external_apply"
                                    class="cn-input cn-input-with-icon-field"
                                    value="<?php echo esc_attr($job_data['external_apply'] ?? ''); ?>"
                                    placeholder="External URL or email (e.g., jobs@company.com)">
                            </div>
                        </div>
                    </div>

                    <div class="cn-form-field">
                        <label for="overview">Overview <span class="required">*</span></label>
                        <p class="cn-field-description">Provide a high-level summary of the role and its impact.
                            (Required)</p>
                        <?php
                        wp_editor($job_data['overview'] ?? '', 'overview', [
                            'textarea_name' => 'overview',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                        ]);
                        ?>
                        <input type="hidden" id="overview_required" value="1">
                    </div>
                </div>

                <div class="cn-form-card" id="companyDetails">
                    <h2 class="cn-form-card-title">Company Details</h2>
                    <?php if ($company_logo): ?>
                        <div class="cn-company-logo-display">
                            <img src="<?php echo esc_url($company_logo); ?>" alt="<?php echo esc_attr($company_name); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="cn-company-info-display">
                        <div class="cn-info-item">
                            <strong>Company Name:</strong>
                            <span><?php echo esc_html($company_name); ?></span>
                        </div>
                        <?php if ($company_website): ?>
                            <div class="cn-info-item">
                                <strong>Website:</strong>
                                <a href="<?php echo esc_url($company_website); ?>" target="_blank"
                                    rel="noopener"><?php echo esc_html($company_website); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cn-form-card" id="selectionCriteria">
                    <h2 class="cn-form-card-title">Selection Criteria</h2>

                    <div class="cn-form-field">
                        <label for="who_we_are">Who We Are</label>
                        <p class="cn-field-description">Introduce the company, culture, and mission.</p>
                        <?php
                        wp_editor($job_data['who_we_are'] ?? '', 'who_we_are', [
                            'textarea_name' => 'who_we_are',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                        ]);
                        ?>
                    </div>

                    <div class="cn-form-field">
                        <label for="what_we_offer">What We Offer</label>
                        <p class="cn-field-description">Outline compensation, benefits, growth, and perks.</p>
                        <?php
                        wp_editor($job_data['what_we_offer'] ?? '', 'what_we_offer', [
                            'textarea_name' => 'what_we_offer',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                        ]);
                        ?>
                    </div>
                </div>

                <div class="cn-form-card" id="responsibilitiesCard">
                    <h2 class="cn-form-card-title">Key Responsibilities</h2>

                    <div class="cn-form-field">
                        <label for="key_responsibilities">Description</label>
                        <p class="cn-field-description">List main responsibilities and expectations.</p>
                        <?php
                        wp_editor($job_data['key_responsibilities'] ?? '', 'key_responsibilities', [
                            'textarea_name' => 'key_responsibilities',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                        ]);
                        ?>
                    </div>
                </div>

                <div class="cn-form-card" id="howToApply">
                    <h2 class="cn-form-card-title">How to Apply</h2>

                    <div class="cn-form-field">
                        <label for="how_to_apply">Description</label>
                        <p class="cn-field-description">Explain the application process and required materials.</p>
                        <?php
                        wp_editor($job_data['how_to_apply'] ?? '', 'how_to_apply', [
                            'textarea_name' => 'how_to_apply',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                        ]);
                        ?>
                    </div>
                </div>

                <div class="cn-form-actions">
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-btn cn-btn-outline">Cancel</a>
                    <?php
                    // Only show Save Draft button for new jobs or jobs that are already drafts
                    $current_status = $is_edit ? get_post_status($job_id) : 'new';
                    if (!$is_edit || $current_status === 'draft'):
                    ?>
                        <button type="submit" class="cn-btn cn-btn-secondary cn-save-draft">Save Draft</button>
                    <?php endif; ?>
                    <button type="submit"
                        class="cn-btn cn-btn-primary cn-publish"><?php echo $is_edit ? esc_html__('Update', 'careernest') : esc_html__('Publish', 'careernest'); ?></button>
                </div>
            </form>

            <aside class="cn-job-nav-sidebar">
                <div class="cn-job-nav">
                    <a href="#jobDetails" class="cn-job-nav-link">Job Details</a>
                    <a href="#companyDetails" class="cn-job-nav-link">Company Details</a>
                    <a href="#selectionCriteria" class="cn-job-nav-link">Selection Criteria</a>
                    <a href="#responsibilitiesCard" class="cn-job-nav-link">Responsibilities</a>
                    <a href="#howToApply" class="cn-job-nav-link">How to Apply</a>
                </div>
            </aside>
        </div>
    </div>
</main>

<style>
    /* Icon Input Styles */
    .cn-input-with-icon {
        position: relative;
        width: 100%;
    }

    .cn-input-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #4a5568;
        pointer-events: none;
        z-index: 1;
    }

    .cn-input-with-icon-field {
        padding-left: 2.5rem !important;
    }
</style>

<?php
get_footer();

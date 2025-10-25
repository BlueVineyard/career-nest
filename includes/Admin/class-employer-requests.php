<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Employer Account Requests Admin Handler
 * 
 * Manages the admin interface for reviewing and approving/declining employer account requests
 */
class Employer_Requests
{
    /**
     * Initialize hooks
     */
    public function hooks(): void
    {
        add_action('admin_post_cn_approve_employer', [$this, 'handle_approve']);
        add_action('admin_post_cn_decline_employer', [$this, 'handle_decline']);
        add_action('admin_post_cn_request_info', [$this, 'handle_request_info']);
    }

    /**
     * Static render method for menu registration
     */
    public static function render_requests_page_static(): void
    {
        $instance = new self();
        $instance->render_requests_page();
    }

    /**
     * Render the account requests page
     */
    public function render_requests_page(): void
    {
        // Check user capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get pending requests
        $pending_requests = new \WP_Query([
            'post_type' => 'employer',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Employer Account Requests', 'careernest'); ?></h1>

            <?php
            // Display admin notices
            if (isset($_GET['message'])):
                $message_type = sanitize_text_field($_GET['message']);
                $message_class = 'notice-success';
                $message_text = '';

                switch ($message_type) {
                    case 'approved':
                        $message_text = __('Employer account approved and created successfully!', 'careernest');
                        break;
                    case 'declined':
                        $message_text = __('Employer request declined.', 'careernest');
                        $message_class = 'notice-warning';
                        break;
                    case 'info_requested':
                        $message_text = __('Information request sent to employer.', 'careernest');
                        $message_class = 'notice-info';
                        break;
                }

                if ($message_text):
            ?>
                    <div class="notice <?php echo esc_attr($message_class); ?> is-dismissible">
                        <p><?php echo esc_html($message_text); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <hr class="wp-header-end">

            <?php if ($pending_requests->have_posts()): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Company', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Contact', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Location', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Date Requested', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Actions', 'careernest'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pending_requests->have_posts()): $pending_requests->the_post(); ?>
                            <?php
                            $post_id = get_the_ID();
                            $contact_name = get_post_meta($post_id, '_contact_name', true);
                            $contact_email = get_post_meta($post_id, '_contact_email', true);
                            $phone = get_post_meta($post_id, '_phone', true);
                            $website = get_post_meta($post_id, '_website', true);
                            $location = get_post_meta($post_id, '_location', true);
                            $company_size = get_post_meta($post_id, '_company_size', true);
                            $industry = get_post_meta($post_id, '_industry', true);
                            $request_date = get_post_meta($post_id, '_request_date', true);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(get_the_title()); ?></strong>
                                    <div class="row-actions">
                                        <span><a href="#" class="cn-view-details"
                                                data-request-id="<?php echo esc_attr($post_id); ?>">View Details</a></span>
                                    </div>

                                    <!-- Hidden details panel -->
                                    <div class="cn-request-details" id="details-<?php echo esc_attr($post_id); ?>"
                                        style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                            <div>
                                                <strong><?php echo esc_html__('Industry:', 'careernest'); ?></strong>
                                                <?php echo esc_html($industry ?: 'Not specified'); ?><br>
                                                <strong><?php echo esc_html__('Company Size:', 'careernest'); ?></strong>
                                                <?php echo esc_html($company_size ?: 'Not specified'); ?><br>
                                                <strong><?php echo esc_html__('Website:', 'careernest'); ?></strong>
                                                <?php echo $website ? '<a href="' . esc_url($website) . '" target="_blank">' . esc_html($website) . '</a>' : 'Not provided'; ?><br>
                                                <strong><?php echo esc_html__('Phone:', 'careernest'); ?></strong>
                                                <?php echo esc_html($phone ?: 'Not provided'); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo esc_html__('About Company:', 'careernest'); ?></strong><br>
                                                <p style="margin-top: 5px; line-height: 1.5;"><?php echo esc_html(get_the_content()); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo esc_html($contact_name); ?><br>
                                    <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
                                </td>
                                <td><?php echo esc_html($location); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($request_date))); ?></td>
                                <td>
                                    <button type="button" class="button button-primary cn-approve-btn"
                                        data-request-id="<?php echo esc_attr($post_id); ?>">
                                        <?php echo esc_html__('Approve', 'careernest'); ?>
                                    </button>
                                    <button type="button" class="button cn-request-info-btn"
                                        data-request-id="<?php echo esc_attr($post_id); ?>">
                                        <?php echo esc_html__('Request Info', 'careernest'); ?>
                                    </button>
                                    <button type="button" class="button cn-decline-btn"
                                        data-request-id="<?php echo esc_attr($post_id); ?>">
                                        <?php echo esc_html__('Decline', 'careernest'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No pending employer account requests.', 'careernest'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Approve Modal -->
        <div id="cn-approve-modal" style="display:none;">
            <div class="cn-modal-content">
                <p><?php echo esc_html__('Are you sure you want to approve this employer account request?', 'careernest'); ?>
                </p>
                <p><strong><?php echo esc_html__('This will:', 'careernest'); ?></strong></p>
                <ul>
                    <li><?php echo esc_html__('Create a user account with a generated password', 'careernest'); ?></li>
                    <li><?php echo esc_html__('Assign employer_team role', 'careernest'); ?></li>
                    <li><?php echo esc_html__('Publish the employer profile', 'careernest'); ?></li>
                    <li><?php echo esc_html__('Send welcome email with login credentials', 'careernest'); ?></li>
                </ul>
            </div>
        </div>

        <!-- Decline Modal -->
        <div id="cn-decline-modal" style="display:none;">
            <div class="cn-modal-content">
                <p><?php echo esc_html__('Reason for declining (will be sent to the requester):', 'careernest'); ?></p>
                <textarea id="decline-reason" rows="4" style="width: 100%;"></textarea>
            </div>
        </div>

        <!-- Request Info Modal -->
        <div id="cn-request-info-modal" style="display:none;">
            <div class="cn-modal-content">
                <p><?php echo esc_html__('What additional information do you need?', 'careernest'); ?></p>
                <textarea id="info-request-message" rows="4" style="width: 100%;"></textarea>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // View details toggle
                $('.cn-view-details').on('click', function(e) {
                    e.preventDefault();
                    const requestId = $(this).data('request-id');
                    $('#details-' + requestId).slideToggle();
                });

                // Approve button
                $('.cn-approve-btn').on('click', function() {
                    const requestId = $(this).data('request-id');

                    if (confirm(
                            '<?php echo esc_js(__('Are you sure you want to approve this employer account request? This will create a user account and send login credentials via email.', 'careernest')); ?>'
                        )) {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_approve_employer">');
                        form.append('<input type="hidden" name="request_id" value="' + requestId + '">');
                        form.append(
                            '<input type="hidden" name="cn_approve_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_approve_employer')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });

                // Decline button
                $('.cn-decline-btn').on('click', function() {
                    const requestId = $(this).data('request-id');
                    const reason = prompt(
                        '<?php echo esc_js(__('Reason for declining (will be sent to the requester):', 'careernest')); ?>'
                    );

                    if (reason !== null && reason.trim() !== '') {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_decline_employer">');
                        form.append('<input type="hidden" name="request_id" value="' + requestId + '">');
                        form.append('<input type="hidden" name="decline_reason" value="' + reason + '">');
                        form.append(
                            '<input type="hidden" name="cn_decline_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_decline_employer')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });

                // Request info button
                $('.cn-request-info-btn').on('click', function() {
                    const requestId = $(this).data('request-id');
                    const message = prompt(
                        '<?php echo esc_js(__('What additional information do you need?', 'careernest')); ?>');

                    if (message !== null && message.trim() !== '') {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_request_info">');
                        form.append('<input type="hidden" name="request_id" value="' + requestId + '">');
                        form.append('<input type="hidden" name="info_message" value="' + message + '">');
                        form.append(
                            '<input type="hidden" name="cn_info_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_request_info')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        </script>

        <style>
            .cn-approve-btn {
                border-color: #10B981 !important;
                color: #10B981 !important;
            }

            .cn-approve-btn:hover {
                background: #10B981 !important;
                color: white !important;
            }

            .cn-decline-btn {
                border-color: #dc3545 !important;
                color: #dc3545 !important;
            }

            .cn-decline-btn:hover {
                background: #dc3545 !important;
                color: white !important;
            }

            .cn-request-info-btn {
                border-color: #0073aa !important;
                color: #0073aa !important;
            }

            .cn-request-info-btn:hover {
                background: #0073aa !important;
                color: white !important;
            }
        </style>
<?php
    }

    /**
     * Handle employer account approval
     */
    public function handle_approve(): void
    {
        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;

        // Verify nonce
        if (!isset($_POST['cn_approve_nonce']) || !wp_verify_nonce($_POST['cn_approve_nonce'], 'cn_approve_employer')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get request data
        $employer_post = get_post($request_id);
        if (!$employer_post || $employer_post->post_type !== 'employer') {
            wp_die(__('Invalid request.', 'careernest'));
        }

        $contact_name = get_post_meta($request_id, '_contact_name', true);
        $contact_email = get_post_meta($request_id, '_contact_email', true);
        $company_name = $employer_post->post_title;

        // Generate random password
        $password = wp_generate_password(12, true, true);

        // Create user account
        $user_id = wp_create_user($contact_email, $password, $contact_email);

        if (is_wp_error($user_id)) {
            wp_die(__('Failed to create user account: ', 'careernest') . $user_id->get_error_message());
        }

        // Update user profile
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $contact_name,
            'first_name' => explode(' ', $contact_name)[0],
            'last_name' => substr($contact_name, strlen(explode(' ', $contact_name)[0]) + 1),
        ]);

        // Set employer role
        $user = new \WP_User($user_id);
        $user->set_role('employer_team');

        // Update employer post
        wp_update_post([
            'ID' => $request_id,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        // Link user to employer profile (bidirectional)
        update_post_meta($request_id, '_user_id', $user_id);
        update_user_meta($user_id, '_employer_id', $request_id);
        update_post_meta($request_id, '_request_status', 'approved');
        update_post_meta($request_id, '_approved_date', current_time('mysql'));

        // Send welcome email with credentials using HTML template
        $pages = get_option('careernest_pages', []);
        $dashboard_id = isset($pages['employer-dashboard']) ? (int) $pages['employer-dashboard'] : 0;
        $dashboard_url = ($dashboard_id && get_post_status($dashboard_id) === 'publish') ? get_permalink($dashboard_id) : home_url();

        \CareerNest\Email\Mailer::send($contact_email, 'employer_approved', [
            'user_name' => $contact_name,
            'company_name' => $company_name,
            'user_email' => $contact_email,
            'password' => $password,
            'dashboard_url' => $dashboard_url,
        ]);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'employer-requests',
            'message' => 'approved'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle employer request decline
     */
    public function handle_decline(): void
    {
        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $decline_reason = isset($_POST['decline_reason']) ? sanitize_textarea_field($_POST['decline_reason']) : '';

        // Verify nonce
        if (!isset($_POST['cn_decline_nonce']) || !wp_verify_nonce($_POST['cn_decline_nonce'], 'cn_decline_employer')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get request data
        $employer_post = get_post($request_id);
        if (!$employer_post || $employer_post->post_type !== 'employer') {
            wp_die(__('Invalid request.', 'careernest'));
        }

        $contact_name = get_post_meta($request_id, '_contact_name', true);
        $contact_email = get_post_meta($request_id, '_contact_email', true);
        $company_name = $employer_post->post_title;

        // Update request status
        update_post_meta($request_id, '_request_status', 'declined');
        update_post_meta($request_id, '_decline_reason', $decline_reason);
        update_post_meta($request_id, '_declined_date', current_time('mysql'));

        // Delete the request post
        wp_delete_post($request_id, true);

        // Send decline email using HTML template
        \CareerNest\Email\Mailer::send($contact_email, 'employer_declined', [
            'user_name' => $contact_name,
            'company_name' => $company_name,
            'reason' => $decline_reason ?: 'Not specified',
        ]);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'employer-requests',
            'message' => 'declined'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle request for more information
     */
    public function handle_request_info(): void
    {
        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $info_message = isset($_POST['info_message']) ? sanitize_textarea_field($_POST['info_message']) : '';

        // Verify nonce
        if (!isset($_POST['cn_info_nonce']) || !wp_verify_nonce($_POST['cn_info_nonce'], 'cn_request_info')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get request data
        $employer_post = get_post($request_id);
        if (!$employer_post || $employer_post->post_type !== 'employer') {
            wp_die(__('Invalid request.', 'careernest'));
        }

        $contact_name = get_post_meta($request_id, '_contact_name', true);
        $contact_email = get_post_meta($request_id, '_contact_email', true);
        $company_name = $employer_post->post_title;

        // Update request status
        update_post_meta($request_id, '_info_requested', true);
        update_post_meta($request_id, '_info_request_message', $info_message);
        update_post_meta($request_id, '_info_requested_date', current_time('mysql'));

        // Send info request email using HTML template
        \CareerNest\Email\Mailer::send($contact_email, 'employer_info_request', [
            'user_name' => $contact_name,
            'company_name' => $company_name,
            'message' => $info_message,
        ]);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'employer-requests',
            'message' => 'info_requested'
        ], admin_url('admin.php')));
        exit;
    }
}

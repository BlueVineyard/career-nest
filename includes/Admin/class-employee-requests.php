<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Employee Account Requests Admin Handler
 * 
 * Manages the admin interface for reviewing and approving/declining employee account requests
 */
class Employee_Requests
{
    /**
     * Initialize hooks
     */
    public function hooks(): void
    {
        add_action('admin_post_cn_approve_employee', [$this, 'handle_approve']);
        add_action('admin_post_cn_decline_employee', [$this, 'handle_decline']);
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
     * Render the employee requests page
     */
    public function render_requests_page(): void
    {
        // Check user capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get pending employee requests (users with pending flag)
        $pending_requests = get_users([
            'meta_key' => '_pending_employee_request',
            'meta_compare' => 'EXISTS',
            'orderby' => 'registered',
            'order' => 'DESC',
        ]);

?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Employee Account Requests', 'careernest'); ?></h1>

            <?php
            // Display admin notices
            if (isset($_GET['message'])):
                $message_type = sanitize_text_field($_GET['message']);
                $message_class = 'notice-success';
                $message_text = '';

                switch ($message_type) {
                    case 'approved':
                        $message_text = __('Employee account approved successfully!', 'careernest');
                        break;
                    case 'declined':
                        $message_text = __('Employee request declined.', 'careernest');
                        $message_class = 'notice-warning';
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

            <?php if (!empty($pending_requests)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Name', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Email', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Job Title', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Company', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Date Requested', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Actions', 'careernest'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $user): ?>
                            <?php
                            $user_id = $user->ID;
                            $full_name = get_user_meta($user_id, '_request_full_name', true);
                            $email = get_user_meta($user_id, '_request_email', true);
                            $phone = get_user_meta($user_id, '_request_phone', true);
                            $job_title = get_user_meta($user_id, '_request_job_title', true);
                            $employer_id = get_user_meta($user_id, '_request_employer_id', true);
                            $request_date = get_user_meta($user_id, '_request_date', true);
                            $employer_name = $employer_id ? get_the_title($employer_id) : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($full_name); ?></strong>
                                    <?php if ($phone): ?>
                                        <br><small><?php echo esc_html($phone); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                </td>
                                <td><?php echo esc_html($job_title); ?></td>
                                <td>
                                    <?php if ($employer_id): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $employer_id . '&action=edit')); ?>">
                                            <?php echo esc_html($employer_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($employer_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($request_date))); ?></td>
                                <td>
                                    <button type="button" class="button button-primary cn-approve-emp-btn"
                                        data-user-id="<?php echo esc_attr($user_id); ?>">
                                        <?php echo esc_html__('Approve', 'careernest'); ?>
                                    </button>
                                    <button type="button" class="button cn-decline-emp-btn"
                                        data-user-id="<?php echo esc_attr($user_id); ?>">
                                        <?php echo esc_html__('Decline', 'careernest'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No pending employee account requests.', 'careernest'); ?></p>
            <?php endif; ?>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Approve button
                $('.cn-approve-emp-btn').on('click', function() {
                    const userId = $(this).data('user-id');

                    if (confirm(
                            '<?php echo esc_js(__('Are you sure you want to approve this employee account request? This will create a user account and send login credentials via email.', 'careernest')); ?>'
                        )) {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_approve_employee">');
                        form.append('<input type="hidden" name="user_id" value="' + userId + '">');
                        form.append(
                            '<input type="hidden" name="cn_approve_emp_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_approve_employee')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });

                // Decline button
                $('.cn-decline-emp-btn').on('click', function() {
                    const userId = $(this).data('user-id');
                    const reason = prompt(
                        '<?php echo esc_js(__('Reason for declining (will be sent to the requester):', 'careernest')); ?>'
                    );

                    if (reason !== null && reason.trim() !== '') {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_decline_employee">');
                        form.append('<input type="hidden" name="user_id" value="' + userId + '">');
                        form.append('<input type="hidden" name="decline_reason" value="' + reason + '">');
                        form.append(
                            '<input type="hidden" name="cn_decline_emp_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_decline_employee')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        </script>

        <style>
            .cn-approve-emp-btn {
                border-color: #10B981 !important;
                color: #10B981 !important;
            }

            .cn-approve-emp-btn:hover {
                background: #10B981 !important;
                color: white !important;
            }

            .cn-decline-emp-btn {
                border-color: #dc3545 !important;
                color: #dc3545 !important;
            }

            .cn-decline-emp-btn:hover {
                background: #dc3545 !important;
                color: white !important;
            }
        </style>
<?php
    }

    /**
     * Handle employee account approval
     */
    public function handle_approve(): void
    {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        // Verify nonce
        if (!isset($_POST['cn_approve_emp_nonce']) || !wp_verify_nonce($_POST['cn_approve_emp_nonce'], 'cn_approve_employee')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get request data
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_die(__('Invalid request.', 'careernest'));
        }

        $full_name = get_user_meta($user_id, '_request_full_name', true);
        $email = get_user_meta($user_id, '_request_email', true);
        $phone = get_user_meta($user_id, '_request_phone', true);
        $job_title = get_user_meta($user_id, '_request_job_title', true);
        $employer_id = get_user_meta($user_id, '_request_employer_id', true);
        $employer_name = get_the_title($employer_id);

        // Generate random password
        $password = wp_generate_password(12, true, true);

        // Update user account (convert from temporary to real)
        $name_parts = explode(' ', $full_name);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';

        // Update user - note: user_login cannot be changed via wp_update_user
        // We need to update it directly in the database
        global $wpdb;
        $wpdb->update(
            $wpdb->users,
            ['user_login' => $email],
            ['ID' => $user_id],
            ['%s'],
            ['%d']
        );

        // Now update other user fields
        wp_update_user([
            'ID' => $user_id,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $full_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'employer_team',
        ]);

        // Link employee to employer (bidirectional)
        update_user_meta($user_id, '_employer_id', $employer_id);
        update_user_meta($user_id, '_job_title', $job_title);
        if ($phone) {
            update_user_meta($user_id, '_phone', $phone);
        }

        // Remove pending request flags
        delete_user_meta($user_id, '_pending_employee_request');
        delete_user_meta($user_id, '_request_full_name');
        delete_user_meta($user_id, '_request_email');
        delete_user_meta($user_id, '_request_phone');
        delete_user_meta($user_id, '_request_job_title');
        delete_user_meta($user_id, '_request_employer_id');
        delete_user_meta($user_id, '_request_status');
        delete_user_meta($user_id, '_request_date');

        // Send welcome email with credentials
        $platform_name = cn_get_platform_name();
        $from_name = cn_get_email_from_name();

        $subject = $platform_name . ' - Employee Account Approved!';
        $message = "Hi {$full_name},\n\n";
        $message .= "Great news! Your employee account request has been approved.\n\n";
        $message .= "Your Login Credentials:\n";
        $message .= "Username: {$email}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "You are now linked to: {$employer_name}\n\n";
        $message .= "You can now:\n";
        $message .= "- Post job listings for your company\n";
        $message .= "- Review applications\n";
        $message .= "- Manage job postings\n\n";

        $pages = get_option('careernest_pages', []);
        $dashboard_id = isset($pages['employer-dashboard']) ? (int) $pages['employer-dashboard'] : 0;
        if ($dashboard_id && get_post_status($dashboard_id) === 'publish') {
            $message .= "Access your dashboard: " . get_permalink($dashboard_id) . "\n\n";
        }

        $message .= "We recommend changing your password after your first login.\n\n";
        $message .= "Welcome to {$platform_name}!\n{$from_name}";

        wp_mail($email, $subject, $message);

        // Log activity
        $this->log_activity([
            'type' => 'employees_added',
            'text' => '<strong>' . esc_html($full_name) . '</strong> added to <strong>' . esc_html($employer_name) . '</strong>',
            'icon' => 'dashicons-admin-users',
            'color' => '#10B981',
        ]);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'employee-requests',
            'message' => 'approved'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle employee request decline
     */
    public function handle_decline(): void
    {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $decline_reason = isset($_POST['decline_reason']) ? sanitize_textarea_field($_POST['decline_reason']) : '';

        // Verify nonce
        if (!isset($_POST['cn_decline_emp_nonce']) || !wp_verify_nonce($_POST['cn_decline_emp_nonce'], 'cn_decline_employee')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get request data
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_die(__('Invalid request.', 'careernest'));
        }

        $full_name = get_user_meta($user_id, '_request_full_name', true);
        $email = get_user_meta($user_id, '_request_email', true);
        $employer_id = get_user_meta($user_id, '_request_employer_id', true);
        $employer_name = get_the_title($employer_id);

        // Delete the temporary user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        // Send decline email
        $platform_name = cn_get_platform_name();
        $from_name = cn_get_email_from_name();

        $subject = $platform_name . ' - Employee Account Request Update';
        $message = "Hi {$full_name},\n\n";
        $message .= "Thank you for your interest in {$platform_name}.\n\n";
        $message .= "After reviewing your employee account request to join {$employer_name}, we are unable to approve it at this time.\n\n";

        if ($decline_reason) {
            $message .= "Reason: {$decline_reason}\n\n";
        }

        $message .= "If you have any questions or would like to discuss this further, please feel free to contact us.\n\n";
        $message .= "Thank you,\n{$from_name}";

        wp_mail($email, $subject, $message);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'employee-requests',
            'message' => 'declined'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Log activity to recent activity feed
     */
    private function log_activity(array $activity): void
    {
        $activities = get_option('careernest_recent_activity', []);

        // Add timestamp
        $activity['timestamp'] = current_time('timestamp');

        // Prepend new activity
        array_unshift($activities, $activity);

        // Keep only last 50 activities
        $activities = array_slice($activities, 0, 50);

        update_option('careernest_recent_activity', $activities);
    }
}

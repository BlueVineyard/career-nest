<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Team Member Deletion Requests Admin Handler
 * 
 * Manages the admin interface for reviewing and approving/declining deletion requests
 */
class Deletion_Requests
{
    /**
     * Initialize hooks
     */
    public function hooks(): void
    {
        add_action('admin_post_cn_approve_deletion', [$this, 'handle_approve']);
        add_action('admin_post_cn_decline_deletion', [$this, 'handle_decline']);
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
     * Render the deletion requests page
     */
    public function render_requests_page(): void
    {
        // Check user capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get pending deletion requests (users with pending deletion flag)
        $pending_deletions = get_users([
            'meta_key' => '_pending_deletion_request',
            'meta_compare' => 'EXISTS',
            'role' => 'employer_team',
            'orderby' => 'meta_value',
            'meta_key' => '_deletion_request_date',
            'order' => 'DESC',
        ]);

?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Team Member Deletion Requests', 'careernest'); ?></h1>

            <?php
            // Display admin notices
            if (isset($_GET['message'])):
                $message_type = sanitize_text_field($_GET['message']);
                $message_class = 'notice-success';
                $message_text = '';

                switch ($message_type) {
                    case 'approved':
                        $message_text = __('Team member deleted successfully!', 'careernest');
                        break;
                    case 'declined':
                        $message_text = __('Deletion request declined.', 'careernest');
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

            <?php if (!empty($pending_deletions)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Team Member', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Email', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Job Title', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Company', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Requested By', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Date Requested', 'careernest'); ?></th>
                            <th><?php echo esc_html__('Actions', 'careernest'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_deletions as $user): ?>
                            <?php
                            $user_id = $user->ID;
                            $job_title = get_user_meta($user_id, '_job_title', true);
                            $employer_id = get_user_meta($user_id, '_deletion_employer_id', true);
                            $requested_by_id = get_user_meta($user_id, '_deletion_requested_by', true);
                            $request_date = get_user_meta($user_id, '_deletion_request_date', true);

                            $employer_name = $employer_id ? get_the_title($employer_id) : 'N/A';
                            $requester = $requested_by_id ? get_user_by('id', $requested_by_id) : null;
                            $requester_name = $requester ? $requester->display_name : 'Unknown';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong>
                                    <br><small>ID: <?php echo esc_html($user_id); ?></small>
                                </td>
                                <td>
                                    <a
                                        href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a>
                                </td>
                                <td><?php echo esc_html($job_title ?: 'N/A'); ?></td>
                                <td>
                                    <?php if ($employer_id): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $employer_id . '&action=edit')); ?>">
                                            <?php echo esc_html($employer_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($employer_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($requester_name); ?>
                                    <?php if ($requester): ?>
                                        <br><small><?php echo esc_html($requester->user_email); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($request_date))); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-primary cn-approve-del-btn"
                                        data-user-id="<?php echo esc_attr($user_id); ?>"
                                        data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                        <?php echo esc_html__('Approve & Delete', 'careernest'); ?>
                                    </button>
                                    <button type="button" class="button cn-decline-del-btn"
                                        data-user-id="<?php echo esc_attr($user_id); ?>">
                                        <?php echo esc_html__('Decline', 'careernest'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No pending deletion requests.', 'careernest'); ?></p>
            <?php endif; ?>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Approve button
                $('.cn-approve-del-btn').on('click', function() {
                    const userId = $(this).data('user-id');
                    const userName = $(this).data('user-name');

                    if (confirm(
                            '<?php echo esc_js(__('Are you sure you want to DELETE the user account for ', 'careernest')); ?>' +
                            userName + '?\n\n' +
                            '<?php echo esc_js(__('WARNING: This action is PERMANENT and will delete the entire user account. This cannot be undone!', 'careernest')); ?>'
                        )) {
                        // Double confirmation for safety
                        if (confirm(
                                '<?php echo esc_js(__('FINAL CONFIRMATION: Are you absolutely sure you want to permanently delete this user account?', 'careernest')); ?>'
                            )) {
                            const form = $(
                                '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                            );
                            form.append('<input type="hidden" name="action" value="cn_approve_deletion">');
                            form.append('<input type="hidden" name="user_id" value="' + userId + '">');
                            form.append(
                                '<input type="hidden" name="cn_approve_del_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_approve_deletion')); ?>">'
                            );
                            $('body').append(form);
                            form.submit();
                        }
                    }
                });

                // Decline button
                $('.cn-decline-del-btn').on('click', function() {
                    const userId = $(this).data('user-id');

                    if (confirm(
                            '<?php echo esc_js(__('Are you sure you want to decline this deletion request? The team member will remain active.', 'careernest')); ?>'
                        )) {
                        const form = $(
                            '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"></form>'
                        );
                        form.append('<input type="hidden" name="action" value="cn_decline_deletion">');
                        form.append('<input type="hidden" name="user_id" value="' + userId + '">');
                        form.append(
                            '<input type="hidden" name="cn_decline_del_nonce" value="<?php echo esc_attr(wp_create_nonce('cn_decline_deletion')); ?>">'
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        </script>

        <style>
            .cn-approve-del-btn {
                border-color: #dc3545 !important;
                color: #dc3545 !important;
            }

            .cn-approve-del-btn:hover {
                background: #dc3545 !important;
                color: white !important;
            }

            .cn-decline-del-btn {
                border-color: #10B981 !important;
                color: #10B981 !important;
            }

            .cn-decline-del-btn:hover {
                background: #10B981 !important;
                color: white !important;
            }
        </style>
<?php
    }

    /**
     * Handle deletion approval
     */
    public function handle_approve(): void
    {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        // Verify nonce
        if (!isset($_POST['cn_approve_del_nonce']) || !wp_verify_nonce($_POST['cn_approve_del_nonce'], 'cn_approve_deletion')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get user data before deletion
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_die(__('Invalid user.', 'careernest'));
        }

        $user_email = $user->user_email;
        $user_name = $user->display_name;
        $employer_id = get_user_meta($user_id, '_deletion_employer_id', true);
        $requested_by_id = get_user_meta($user_id, '_deletion_requested_by', true);

        $employer_name = $employer_id ? get_the_title($employer_id) : 'Unknown Company';
        $requester = $requested_by_id ? get_user_by('id', $requested_by_id) : null;

        // Delete the user account (permanent)
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $deleted = wp_delete_user($user_id);

        if (!$deleted) {
            wp_die(__('Failed to delete user account.', 'careernest'));
        }

        // Send notification to the user
        $platform_name = cn_get_platform_name();
        $from_name = cn_get_email_from_name();

        $subject = $platform_name . ' - Account Removed';
        $message = "Hi {$user_name},\n\n";
        $message .= "Your {$platform_name} account has been removed from {$employer_name}.\n\n";
        $message .= "Your account and associated data have been deleted from our system.\n\n";
        $message .= "If you believe this was done in error, please contact your company administrator or our support team.\n\n";
        $message .= "Thank you,\n{$from_name}";

        wp_mail($user_email, $subject, $message);

        // Notify the requester
        if ($requester) {
            $req_subject = $platform_name . ' - Deletion Request Approved';
            $req_message = "Hi {$requester->display_name},\n\n";
            $req_message .= "Your request to remove {$user_name} from your team has been approved.\n\n";
            $req_message .= "The user account has been permanently deleted.\n\n";
            $req_message .= "Thank you,\n{$from_name}";

            wp_mail($requester->user_email, $req_subject, $req_message);
        }

        // Log activity
        $this->log_activity([
            'type' => 'employees_deleted',
            'text' => '<strong>' . esc_html($user_name) . '</strong> removed from <strong>' . esc_html($employer_name) . '</strong>',
            'icon' => 'dashicons-dismiss',
            'color' => '#dc3545',
        ]);

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'deletion-requests',
            'message' => 'approved'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle deletion decline
     */
    public function handle_decline(): void
    {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        // Verify nonce
        if (!isset($_POST['cn_decline_del_nonce']) || !wp_verify_nonce($_POST['cn_decline_del_nonce'], 'cn_decline_deletion')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check capabilities
        if (!current_user_can('manage_careernest')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        // Get user data
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_die(__('Invalid user.', 'careernest'));
        }

        $user_name = $user->display_name;
        $requested_by_id = get_user_meta($user_id, '_deletion_requested_by', true);
        $requester = $requested_by_id ? get_user_by('id', $requested_by_id) : null;

        // Remove pending deletion flags
        delete_user_meta($user_id, '_pending_deletion_request');
        delete_user_meta($user_id, '_deletion_requested_by');
        delete_user_meta($user_id, '_deletion_request_date');
        delete_user_meta($user_id, '_deletion_employer_id');

        // Notify the requester
        if ($requester) {
            $platform_name = cn_get_platform_name();
            $from_name = cn_get_email_from_name();

            $subject = $platform_name . ' - Deletion Request Declined';
            $message = "Hi {$requester->display_name},\n\n";
            $message .= "Your request to remove {$user_name} from your team has been declined.\n\n";
            $message .= "The team member will remain active on your team.\n\n";
            $message .= "If you have questions about this decision, please contact support.\n\n";
            $message .= "Thank you,\n{$from_name}";

            wp_mail($requester->user_email, $subject, $message);
        }

        // Redirect back with success message
        wp_redirect(add_query_arg([
            'page' => 'deletion-requests',
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

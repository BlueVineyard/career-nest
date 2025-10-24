<?php

namespace CareerNest\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Team Management Meta Box
 * 
 * Provides team management interface on employer edit screens
 * Allows AES admins and super admins to manage team members and transfer ownership
 */
class Team_Meta_Box
{
    /**
     * Initialize hooks
     */
    public function hooks(): void
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_employer', [$this, 'save_team_data'], 10, 3);
        add_action('admin_post_cn_admin_add_team_member', [$this, 'handle_add_team_member']);
        add_action('admin_post_cn_admin_remove_team_member', [$this, 'handle_remove_team_member']);
    }

    /**
     * Add team management meta box
     */
    public function add_meta_box(): void
    {
        add_meta_box(
            'cn_team_management',
            __('Team Management', 'careernest'),
            [$this, 'render_meta_box'],
            'employer',
            'side',
            'high'
        );
    }

    /**
     * Render the meta box
     */
    public function render_meta_box(\WP_Post $post): void
    {
        $employer_id = $post->ID;

        // Only show for published employers
        if ($post->post_status !== 'publish') {
            echo '<p>' . esc_html__('Team management is available after the employer is approved and published.', 'careernest') . '</p>';
            return;
        }

        // Get team members
        $team_members = \CareerNest\Team_Manager::get_team_members($employer_id);
        $owner_id = (int) get_post_meta($employer_id, '_user_id', true);
        $current_user_id = get_current_user_id();

        // Check if current user can assign owner
        $can_assign_owner = \CareerNest\Team_Manager::can_assign_owner($current_user_id);

        wp_nonce_field('cn_team_management_meta', 'cn_team_management_nonce');
?>

        <div class="cn-team-meta-box">
            <!-- Ownership Transfer (Admin Only) -->
            <?php if ($can_assign_owner && !empty($team_members)): ?>
                <div class="cn-ownership-section">
                    <h4><?php echo esc_html__('Company Owner', 'careernest'); ?></h4>
                    <p class="description">
                        <?php echo esc_html__('Only AES Admins and Super Admins can change the company owner.', 'careernest'); ?>
                    </p>

                    <select name="cn_company_owner" id="cn_company_owner" class="widefat"
                        onchange="cnConfirmOwnershipChange(this, <?php echo esc_js($owner_id); ?>)">
                        <?php foreach ($team_members as $member): ?>
                            <option value="<?php echo esc_attr($member->ID); ?>" <?php selected($member->ID, $owner_id); ?>>
                                <?php echo esc_html($member->display_name); ?>
                                (<?php echo esc_html($member->user_email); ?>)
                                <?php echo $member->ID === $owner_id ? ' - ' . esc_html__('Current Owner', 'careernest') : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="hidden" name="cn_original_owner" id="cn_original_owner" value="<?php echo esc_attr($owner_id); ?>">
                    <input type="hidden" name="cn_ownership_changed" id="cn_ownership_changed" value="0">
                </div>

                <hr style="margin: 15px 0;">
            <?php endif; ?>

            <!-- Team Members List -->
            <div class="cn-team-members-section">
                <h4><?php echo esc_html__('Team Members', 'careernest'); ?> (<?php echo count($team_members); ?>)</h4>

                <?php if (!empty($team_members)): ?>
                    <div class="cn-team-members-list">
                        <?php foreach ($team_members as $member): ?>
                            <?php
                            $is_owner = $member->ID === $owner_id;
                            $job_title = get_user_meta($member->ID, '_job_title', true);
                            ?>
                            <div class="cn-team-member-item"
                                style="padding: 10px; margin-bottom: 10px; background: #f9f9f9; border-radius: 4px;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <strong><?php echo esc_html($member->display_name); ?></strong>
                                        <?php if ($is_owner): ?>
                                            <span
                                                style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; margin-left: 5px;">
                                                <?php echo esc_html__('OWNER', 'careernest'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #666;"><?php echo esc_html($member->user_email); ?></small>
                                        <?php if ($job_title): ?>
                                            <br>
                                            <small style="color: #0073aa;"><?php echo esc_html($job_title); ?></small>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #999;">
                                            <?php echo esc_html__('Joined:', 'careernest'); ?>
                                            <?php echo esc_html(date('M j, Y', strtotime($member->user_registered))); ?>
                                        </small>
                                    </div>

                                    <?php if (!$is_owner): ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin: 0;">
                                            <?php wp_nonce_field('cn_remove_team_member_' . $member->ID, 'cn_remove_nonce'); ?>
                                            <input type="hidden" name="action" value="cn_admin_remove_team_member">
                                            <input type="hidden" name="employer_id" value="<?php echo esc_attr($employer_id); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo esc_attr($member->ID); ?>">
                                            <button type="submit" class="button button-small"
                                                onclick="return confirm('<?php echo esc_js(sprintf(__('Remove %s from the team?', 'careernest'), $member->display_name)); ?>');"
                                                style="color: #dc3545;">
                                                <?php echo esc_html__('Remove', 'careernest'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; font-style: italic;">
                        <?php echo esc_html__('No team members yet.', 'careernest'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <hr style="margin: 15px 0;">

            <!-- Add Team Member Form -->
            <div class="cn-add-team-member-section">
                <h4><?php echo esc_html__('Add Team Member', 'careernest'); ?></h4>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('cn_add_team_member', 'cn_add_team_nonce'); ?>
                    <input type="hidden" name="action" value="cn_admin_add_team_member">
                    <input type="hidden" name="employer_id" value="<?php echo esc_attr($employer_id); ?>">

                    <p>
                        <label for="cn_member_name"><?php echo esc_html__('Full Name:', 'careernest'); ?></label>
                        <input type="text" id="cn_member_name" name="full_name" class="widefat" required>
                    </p>

                    <p>
                        <label for="cn_member_email"><?php echo esc_html__('Email:', 'careernest'); ?></label>
                        <input type="email" id="cn_member_email" name="email" class="widefat" required>
                    </p>

                    <p>
                        <label for="cn_member_job_title"><?php echo esc_html__('Job Title:', 'careernest'); ?></label>
                        <input type="text" id="cn_member_job_title" name="job_title" class="widefat"
                            placeholder="<?php echo esc_attr__('e.g., HR Manager', 'careernest'); ?>">
                    </p>

                    <p>
                        <button type="submit" class="button button-primary button-large" style="width: 100%;">
                            <?php echo esc_html__('Add Team Member', 'careernest'); ?>
                        </button>
                    </p>

                    <p class="description">
                        <?php echo esc_html__('The team member will receive an email with login credentials.', 'careernest'); ?>
                    </p>
                </form>
            </div>
        </div>

        <style>
            .cn-team-meta-box h4 {
                margin: 0 0 10px 0;
                font-size: 13px;
                font-weight: 600;
            }

            .cn-ownership-section {
                margin-bottom: 15px;
            }

            .cn-team-members-list {
                max-height: 400px;
                overflow-y: auto;
            }
        </style>

        <script>
            function cnConfirmOwnershipChange(select, originalOwnerId) {
                const newOwnerId = parseInt(select.value);
                const ownershipChanged = document.getElementById('cn_ownership_changed');

                if (newOwnerId !== originalOwnerId) {
                    const selectedOption = select.options[select.selectedIndex];
                    const newOwnerName = selectedOption.text.split('(')[0].trim();

                    if (confirm('Are you sure you want to transfer ownership to ' + newOwnerName +
                            '?\n\nThis will:\n• Grant full team management access to the new owner\n• Remove team management access from the current owner\n• Send email notifications to both parties'
                        )) {
                        ownershipChanged.value = '1';
                    } else {
                        select.value = originalOwnerId;
                        ownershipChanged.value = '0';
                    }
                } else {
                    ownershipChanged.value = '0';
                }
            }
        </script>
        <?php
    }

    /**
     * Save team data (ownership transfer)
     */
    public function save_team_data(int $post_id, \WP_Post $post, bool $update): void
    {
        // Only on update, not on new posts
        if (!$update) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['cn_team_management_nonce']) || !wp_verify_nonce($_POST['cn_team_management_nonce'], 'cn_team_management_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if user can assign owner
        if (!\CareerNest\Team_Manager::can_assign_owner(get_current_user_id())) {
            return;
        }

        // Handle ownership transfer
        if (isset($_POST['cn_company_owner']) && isset($_POST['cn_original_owner']) && isset($_POST['cn_ownership_changed']) && $_POST['cn_ownership_changed'] === '1') {
            $new_owner_id = (int) $_POST['cn_company_owner'];
            $original_owner_id = (int) $_POST['cn_original_owner'];

            // Only process if owner changed and confirmed
            if ($new_owner_id !== $original_owner_id && $new_owner_id > 0) {
                $result = \CareerNest\Team_Manager::transfer_ownership($post_id, $new_owner_id);

                // Store result in transient for admin notice
                if ($result['success']) {
                    set_transient('cn_ownership_transfer_success_' . get_current_user_id(), true, 30);
                } else {
                    set_transient('cn_ownership_transfer_error_' . get_current_user_id(), $result['message'], 30);
                }
            }
        }
    }

    /**
     * Handle add team member from admin
     */
    public function handle_add_team_member(): void
    {
        // Verify nonce
        if (!isset($_POST['cn_add_team_nonce']) || !wp_verify_nonce($_POST['cn_add_team_nonce'], 'cn_add_team_member')) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        $employer_id = isset($_POST['employer_id']) ? (int) $_POST['employer_id'] : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
        $job_title = isset($_POST['job_title']) ? sanitize_text_field($_POST['job_title']) : '';

        // Add team member
        $result = \CareerNest\Team_Manager::add_team_member($employer_id, $email, $full_name, $job_title);

        // Redirect back
        $redirect_url = add_query_arg(
            [
                'post' => $employer_id,
                'action' => 'edit',
                'cn_team_action' => $result['success'] ? 'member_added' : 'error',
                'cn_message' => urlencode($result['message'])
            ],
            admin_url('post.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handle remove team member from admin
     */
    public function handle_remove_team_member(): void
    {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        // Verify nonce
        if (!isset($_POST['cn_remove_nonce']) || !wp_verify_nonce($_POST['cn_remove_nonce'], 'cn_remove_team_member_' . $user_id)) {
            wp_die(__('Security check failed.', 'careernest'));
        }

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to perform this action.', 'careernest'));
        }

        $employer_id = isset($_POST['employer_id']) ? (int) $_POST['employer_id'] : 0;

        // Remove team member
        $result = \CareerNest\Team_Manager::remove_team_member($user_id, $employer_id, false);

        // Redirect back
        $redirect_url = add_query_arg(
            [
                'post' => $employer_id,
                'action' => 'edit',
                'cn_team_action' => $result['success'] ? 'member_removed' : 'error',
                'cn_message' => urlencode($result['message'])
            ],
            admin_url('post.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Display admin notices
     */
    public static function display_admin_notices(): void
    {
        $current_user_id = get_current_user_id();

        // Check transient for ownership transfer success
        if (get_transient('cn_ownership_transfer_success_' . $current_user_id)) {
            delete_transient('cn_ownership_transfer_success_' . $current_user_id);
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Ownership transferred successfully!', 'careernest'); ?></p>
            </div>
        <?php
        }

        // Check transient for ownership transfer error
        $error_message = get_transient('cn_ownership_transfer_error_' . $current_user_id);
        if ($error_message) {
            delete_transient('cn_ownership_transfer_error_' . $current_user_id);
        ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php
        }

        // Ownership transferred (URL param - fallback)
        if (isset($_GET['cn_ownership_transferred'])) {
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Ownership transferred successfully!', 'careernest'); ?></p>
            </div>
        <?php
        }

        // Ownership error (URL param - fallback)
        if (isset($_GET['cn_ownership_error'])) {
        ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(urldecode($_GET['cn_ownership_error'])); ?></p>
            </div>
        <?php
        }

        // Team action success
        if (isset($_GET['cn_team_action']) && isset($_GET['cn_message'])) {
            $is_success = $_GET['cn_team_action'] !== 'error';
            $class = $is_success ? 'notice-success' : 'notice-error';
        ?>
            <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
                <p><?php echo esc_html(urldecode($_GET['cn_message'])); ?></p>
            </div>
<?php
        }
    }
}

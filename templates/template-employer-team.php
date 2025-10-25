<?php

/**
 * Template: CareerNest — Employer Team Management
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// Verify user has employer_team role
if (!in_array('employer_team', $user_roles, true)) {
    echo '<main id="primary" class="site-main"><div class="cn-dashboard-error">';
    echo '<h1>' . esc_html__('Access Denied', 'careernest') . '</h1>';
    echo '<p>' . esc_html__('You do not have permission to access this page.', 'careernest') . '</p>';
    echo '</div></main>';
    get_footer();
    return;
}

// Get employer ID
$employer_id = (int) get_user_meta($current_user->ID, '_employer_id', true);
if (!$employer_id) {
    echo '<main id="primary" class="site-main"><div class="cn-dashboard-error">';
    echo '<h1>' . esc_html__('No Employer Linked', 'careernest') . '</h1>';
    echo '<p>' . esc_html__('Your account is not linked to any employer.', 'careernest') . '</p>';
    echo '</div></main>';
    get_footer();
    return;
}

// Get employer details
$employer_post = get_post($employer_id);
$company_name = $employer_post ? $employer_post->post_title : '';

// Get team members
$team_members = get_users([
    'role' => 'employer_team',
    'meta_key' => '_employer_id',
    'meta_value' => $employer_id,
    'orderby' => 'registered',
    'order' => 'ASC'
]);

// Get owner ID
$owner_id = (int) get_post_meta($employer_id, '_user_id', true);
$is_owner = ($owner_id === $current_user->ID);

// Handle team member invitation (owner only)
$invitation_success = '';
$invitation_error = '';

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cn_invite_team_member_nonce'])) {
    if (wp_verify_nonce($_POST['cn_invite_team_member_nonce'], 'cn_invite_team_member')) {
        $invite_email = isset($_POST['invite_email']) ? sanitize_email($_POST['invite_email']) : '';
        $invite_name = isset($_POST['invite_name']) ? sanitize_text_field($_POST['invite_name']) : '';
        $invite_job_title = isset($_POST['invite_job_title']) ? sanitize_text_field($_POST['invite_job_title']) : '';

        if (empty($invite_email) || empty($invite_name)) {
            $invitation_error = 'Email and name are required.';
        } elseif (!is_email($invite_email)) {
            $invitation_error = 'Please provide a valid email address.';
        } elseif (email_exists($invite_email)) {
            $invitation_error = 'A user with this email already exists. Please ask an administrator to link them to your company.';
        } else {
            // Create employee request (temporary user with pending flag)
            $temp_username = 'temp_' . time() . '_' . wp_generate_password(8, false);
            $temp_user_id = wp_create_user($temp_username, wp_generate_password(20, true, true), $temp_username . '@temp.local');

            if (is_wp_error($temp_user_id)) {
                $invitation_error = 'Failed to create request: ' . $temp_user_id->get_error_message();
            } else {
                // Store request data in user meta
                update_user_meta($temp_user_id, '_pending_employee_request', true);
                update_user_meta($temp_user_id, '_request_full_name', $invite_name);
                update_user_meta($temp_user_id, '_request_email', $invite_email);
                update_user_meta($temp_user_id, '_request_job_title', $invite_job_title);
                update_user_meta($temp_user_id, '_request_employer_id', $employer_id);
                update_user_meta($temp_user_id, '_request_status', 'pending');
                update_user_meta($temp_user_id, '_request_date', current_time('mysql'));
                update_user_meta($temp_user_id, '_requested_by_owner', $current_user->ID);

                // Send notification email to admin
                $admin_email = get_option('admin_email');
                $subject = 'New Team Member Request - ' . $company_name;

                $message = '<h2>Team Member Addition Request</h2>';
                $message .= '<p>A company owner has requested to add a new team member:</p>';
                $message .= '<hr>';
                $message .= '<p><strong>Company:</strong> ' . esc_html($company_name) . '</p>';
                $message .= '<p><strong>Requested by:</strong> ' . esc_html($current_user->display_name) . ' (' . esc_html($current_user->user_email) . ')</p>';
                $message .= '<hr>';
                $message .= '<h3>New Team Member Details:</h3>';
                $message .= '<p><strong>Name:</strong> ' . esc_html($invite_name) . '</p>';
                $message .= '<p><strong>Email:</strong> ' . esc_html($invite_email) . '</p>';
                if ($invite_job_title) {
                    $message .= '<p><strong>Job Title:</strong> ' . esc_html($invite_job_title) . '</p>';
                }
                $message .= '<hr>';
                $message .= '<p><strong>This request is now in your Employee Requests queue.</strong></p>';
                $message .= '<p><a href="' . esc_url(admin_url('admin.php?page=employee-requests')) . '" style="display:inline-block; padding:10px 20px; background:#0073aa; color:white; text-decoration:none; border-radius:4px;">Review Request</a></p>';

                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($admin_email, $subject, $message, $headers);

                $invitation_success = 'Request submitted successfully! Your administrator will review and approve the team member addition.';
            }
        }
    } else {
        $invitation_error = 'Security check failed. Please try again.';
    }
}

// Get dashboard page URL
$pages = get_option('careernest_pages', []);
$dashboard_id = isset($pages['employer-dashboard']) ? (int) $pages['employer-dashboard'] : 0;
$dashboard_url = $dashboard_id ? get_permalink($dashboard_id) : home_url();
?>

<main id="primary" class="site-main">
    <div class="cn-team-page-container" style="max-width: 1200px; margin: 2rem auto; padding: 0 20px;">

        <!-- Header -->
        <div class="cn-page-header"
            style="background: #f8f9fa; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #0073aa;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <h1 style="margin: 0 0 0.5rem 0; color: #333; font-size: 2rem;">
                        <?php echo esc_html__('Team Members', 'careernest'); ?>
                    </h1>
                    <?php if ($company_name): ?>
                        <p style="margin: 0; color: #0073aa; font-size: 1.1rem; font-weight: 500;">
                            <?php echo esc_html($company_name); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="cn-btn cn-btn-outline">
                        ← <?php echo esc_html__('Back to Dashboard', 'careernest'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Invite Team Member (Owner Only) -->
        <?php if ($is_owner): ?>
            <div
                style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
                <h2 style="margin: 0 0 0.5rem 0; color: #333; font-size: 1.3rem;">
                    <?php echo esc_html__('Invite Team Member', 'careernest'); ?>
                </h2>
                <p style="color: #666; margin: 0 0 1.5rem 0;">
                    <?php echo esc_html__('Add new team members to help manage your company\'s job postings and applications.', 'careernest'); ?>
                </p>

                <?php if ($invitation_success): ?>
                    <div
                        style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: #155724;">
                        <?php echo esc_html($invitation_success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($invitation_error): ?>
                    <div
                        style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: #721c24;">
                        <?php echo esc_html($invitation_error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" style="background: #f8f9fa; border-radius: 6px; padding: 1.5rem;">
                    <?php wp_nonce_field('cn_invite_team_member', 'cn_invite_team_member_nonce'); ?>

                    <div style="margin-bottom: 1rem;">
                        <label for="invite_name"
                            style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">
                            <?php echo esc_html__('Full Name', 'careernest'); ?> <span style="color: #dc3545;">*</span>
                        </label>
                        <input type="text" id="invite_name" name="invite_name" required
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                            placeholder="<?php echo esc_attr__('e.g., John Doe', 'careernest'); ?>">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label for="invite_email"
                            style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">
                            <?php echo esc_html__('Email Address', 'careernest'); ?> <span style="color: #dc3545;">*</span>
                        </label>
                        <input type="email" id="invite_email" name="invite_email" required
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                            placeholder="<?php echo esc_attr__('john@example.com', 'careernest'); ?>">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="invite_job_title"
                            style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">
                            <?php echo esc_html__('Job Title', 'careernest'); ?>
                        </label>
                        <input type="text" id="invite_job_title" name="invite_job_title"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                            placeholder="<?php echo esc_attr__('e.g., HR Manager', 'careernest'); ?>">
                    </div>

                    <button type="submit" class="cn-btn cn-btn-primary"
                        style="padding: 0.75rem 2rem; font-size: 1rem; cursor: pointer;">
                        <?php echo esc_html__('Request Team Member Addition', 'careernest'); ?>
                    </button>

                    <p style="margin: 1rem 0 0 0; color: #666; font-size: 0.9rem;">
                        <em><?php echo esc_html__('Your administrator will be notified and can add this team member from the WordPress admin panel.', 'careernest'); ?></em>
                    </p>
                </form>
            </div>
        <?php endif; ?>

        <!-- Team Members List -->
        <div style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 2rem;">
            <h2 style="margin: 0 0 1.5rem 0; color: #333; font-size: 1.5rem;">
                <?php echo esc_html(count($team_members)); ?>
                <?php echo esc_html(count($team_members) === 1 ? 'Team Member' : 'Team Members'); ?>
            </h2>

            <?php if (!empty($team_members)): ?>
                <div class="cn-team-members-grid" style="display: grid; gap: 1.5rem;">
                    <?php foreach ($team_members as $member): ?>
                        <?php
                        $member_is_owner = ($member->ID === $owner_id);
                        $member_job_title = get_user_meta($member->ID, '_job_title', true);
                        $member_phone = get_user_meta($member->ID, '_personal_phone', true);
                        $member_bio = get_user_meta($member->ID, '_bio', true);
                        ?>
                        <div class="cn-team-member-card-view"
                            style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem; <?php echo $member_is_owner ? 'background: #f0f9ff; border-color: #0073aa;' : ''; ?>">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.25rem 0; color: #333; font-size: 1.2rem;">
                                        <?php echo esc_html($member->display_name); ?>
                                        <?php if ($member_is_owner): ?>
                                            <span
                                                style="background: #0073aa; color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.65em; font-weight: 500; margin-left: 8px;">
                                                <?php echo esc_html__('OWNER', 'careernest'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </h3>
                                    <?php if ($member_job_title): ?>
                                        <p style="margin: 0; color: #0073aa; font-weight: 500;">
                                            <?php echo esc_html($member_job_title); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: #666; font-size: 0.9rem;">📧 Email:</strong>
                                    <a href="mailto:<?php echo esc_attr($member->user_email); ?>"
                                        style="color: #0073aa; text-decoration: none; margin-left: 0.5rem;">
                                        <?php echo esc_html($member->user_email); ?>
                                    </a>
                                </div>

                                <?php if ($member_phone): ?>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="color: #666; font-size: 0.9rem;">📞 Phone:</strong>
                                        <span style="margin-left: 0.5rem;"><?php echo esc_html($member_phone); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <strong style="color: #666; font-size: 0.9rem;">📅 Joined:</strong>
                                    <span style="margin-left: 0.5rem;">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member->user_registered))); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($member_bio): ?>
                                <div style="padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                                    <strong
                                        style="color: #666; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">About:</strong>
                                    <div style="color: #555; line-height: 1.6; font-size: 0.95rem;">
                                        <?php echo wp_kses_post(wpautop($member_bio)); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <p style="font-size: 1.1rem; font-style: italic;">
                        <?php echo esc_html__('No team members found.', 'careernest'); ?>
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php
get_footer();

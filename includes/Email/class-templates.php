<?php

namespace CareerNest\Email;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Templates Class
 * 
 * Manages email templates and their default content
 */
class Templates
{
    /**
     * Get a specific email template
     * 
     * @param string $template_key Template identifier
     * @return array|null Template data or null if not found
     */
    public function get_template(string $template_key): ?array
    {
        $templates = $this->get_all_templates();

        if (!isset($templates[$template_key])) {
            return null;
        }

        // Get saved template from options
        $options = get_option('careernest_email_templates', []);

        // Use saved template if available, otherwise use default
        if (isset($options[$template_key]) && !empty($options[$template_key]['body'])) {
            return [
                'subject' => $options[$template_key]['subject'] ?? $templates[$template_key]['subject'],
                'body' => $options[$template_key]['body'],
                'name' => $templates[$template_key]['name'],
                'variables' => $templates[$template_key]['variables'] ?? [],
            ];
        }

        return $templates[$template_key];
    }

    /**
     * Get all available email templates with defaults
     * 
     * @return array All email templates
     */
    public function get_all_templates(): array
    {
        return [
            'applicant_welcome' => [
                'name' => 'Applicant Welcome Email',
                'subject' => 'Welcome to {{site_name}}!',
                'body' => 'Hi {{user_name}},

Welcome to {{site_name}}! Your applicant account has been created successfully.

<strong>You can now:</strong>
<ul>
<li>Apply for jobs</li>
<li>Track your applications</li>
<li>Update your profile</li>
<li>Upload your resume</li>
</ul>

{{guest_applications_message}}

<a href="{{dashboard_url}}" class="email-button">Access Your Dashboard</a>

Thank you for joining {{site_name}}!',
                'variables' => ['user_name', 'dashboard_url', 'guest_applications_message'],
            ],

            'employer_request_confirmation' => [
                'name' => 'Employer Request Confirmation',
                'subject' => 'Employer Account Request Received',
                'body' => 'Hi {{user_name}},

Thank you for submitting an employer account request for <strong>{{company_name}}</strong>.

We have received your request and our team will review it shortly. You will receive an email notification once your account has been approved.

<strong>What happens next?</strong>
<ul>
<li>Our team reviews your company information</li>
<li>You receive approval (typically within 24-48 hours)</li>
<li>You can start posting jobs and managing applications</li>
</ul>

If you have any questions, please don\'t hesitate to contact us at {{contact_email}}.

Thank you for your patience!',
                'variables' => ['user_name', 'company_name', 'contact_email'],
            ],

            'employer_approved' => [
                'name' => 'Employer Account Approved',
                'subject' => '{{site_name}} - Employer Account Approved!',
                'body' => 'Hi {{user_name}},

Great news! Your employer account for <strong>{{company_name}}</strong> has been approved.

<div class="credentials-box">
<strong>Your Login Credentials:</strong>
<p><strong>Username:</strong> {{user_email}}</p>
<p><strong>Password:</strong> {{password}}</p>
</div>

<strong>You can now:</strong>
<ul>
<li>Post job listings</li>
<li>Manage your company profile</li>
<li>Review applications</li>
<li>Track your job postings</li>
</ul>

<a href="{{dashboard_url}}" class="email-button">Access Your Dashboard</a>

<em>We recommend changing your password after your first login.</em>

Welcome to {{site_name}}!',
                'variables' => ['user_name', 'company_name', 'user_email', 'password', 'dashboard_url'],
            ],

            'employer_declined' => [
                'name' => 'Employer Request Declined',
                'subject' => '{{site_name}} - Employer Account Request Update',
                'body' => 'Hi {{user_name}},

Thank you for your interest in {{site_name}}.

After reviewing your employer account request for <strong>{{company_name}}</strong>, we are unable to approve it at this time.

<strong>Reason:</strong> {{reason}}

If you have any questions or would like to discuss this further, please feel free to contact us at {{contact_email}}.

Thank you for your understanding.',
                'variables' => ['user_name', 'company_name', 'reason', 'contact_email'],
            ],

            'employer_info_request' => [
                'name' => 'Employer Additional Info Request',
                'subject' => '{{site_name}} - Additional Information Needed',
                'body' => 'Hi {{user_name}},

Thank you for submitting an employer account request for <strong>{{company_name}}</strong>.

To proceed with your request, we need some additional information:

{{message}}

Please reply to this email with the requested information.

Thank you for your cooperation!',
                'variables' => ['user_name', 'company_name', 'message'],
            ],

            'employee_request_confirmation' => [
                'name' => 'Employee Request Confirmation',
                'subject' => 'Employee Account Request Received',
                'body' => 'Hi {{user_name}},

Thank you for requesting to join <strong>{{company_name}}</strong> as a team member.

We have received your request and the admin team will review it shortly. You will receive an email notification once your account has been approved.

<strong>What happens next?</strong>
<ul>
<li>Admin team reviews your request</li>
<li>You receive approval notification</li>
<li>You can access the employer dashboard</li>
</ul>

If you have any questions, please contact us at {{contact_email}}.

Thank you for your patience!',
                'variables' => ['user_name', 'company_name', 'contact_email'],
            ],

            'employee_approved' => [
                'name' => 'Employee Account Approved',
                'subject' => '{{site_name}} - Employee Account Approved!',
                'body' => 'Hi {{user_name}},

Great news! Your request to join <strong>{{company_name}}</strong> has been approved.

<div class="credentials-box">
<strong>Your Login Credentials:</strong>
<p><strong>Username:</strong> {{user_email}}</p>
<p><strong>Password:</strong> {{password}}</p>
</div>

<strong>You can now:</strong>
<ul>
<li>Access the employer dashboard</li>
<li>Manage job postings</li>
<li>Review applications</li>
<li>Collaborate with your team</li>
</ul>

<a href="{{dashboard_url}}" class="email-button">Access Your Dashboard</a>

<em>We recommend changing your password after your first login.</em>

Welcome to the team!',
                'variables' => ['user_name', 'company_name', 'user_email', 'password', 'dashboard_url'],
            ],

            'employee_declined' => [
                'name' => 'Employee Request Declined',
                'subject' => '{{site_name}} - Employee Account Request Update',
                'body' => 'Hi {{user_name}},

Thank you for your interest in joining <strong>{{company_name}}</strong>.

After reviewing your request, we are unable to approve it at this time.

<strong>Reason:</strong> {{reason}}

If you have any questions or would like to discuss this further, please contact us at {{contact_email}}.

Thank you for your understanding.',
                'variables' => ['user_name', 'company_name', 'reason', 'contact_email'],
            ],

            'admin_notification' => [
                'name' => 'Admin Notification',
                'subject' => '{{site_name}} - {{subject_text}}',
                'body' => '{{message}}',
                'variables' => ['subject_text', 'message'],
            ],
        ];
    }

    /**
     * Get default template for a specific key
     * 
     * @param string $template_key Template identifier
     * @return array|null Default template data
     */
    public function get_default_template(string $template_key): ?array
    {
        $templates = $this->get_all_templates();
        return $templates[$template_key] ?? null;
    }

    /**
     * Save template to options
     * 
     * @param string $template_key Template identifier
     * @param string $subject Email subject
     * @param string $body Email body
     * @return bool Whether save was successful
     */
    public function save_template(string $template_key, string $subject, string $body): bool
    {
        $options = get_option('careernest_email_templates', []);

        $options[$template_key] = [
            'subject' => sanitize_text_field($subject),
            'body' => wp_kses_post($body),
        ];

        return update_option('careernest_email_templates', $options);
    }

    /**
     * Reset template to default
     * 
     * @param string $template_key Template identifier
     * @return bool Whether reset was successful
     */
    public function reset_template(string $template_key): bool
    {
        $options = get_option('careernest_email_templates', []);

        if (isset($options[$template_key])) {
            unset($options[$template_key]);
            return update_option('careernest_email_templates', $options);
        }

        return true;
    }

    /**
     * Reset all templates to defaults
     * 
     * @return bool Whether reset was successful
     */
    public function reset_all_templates(): bool
    {
        return delete_option('careernest_email_templates');
    }
}

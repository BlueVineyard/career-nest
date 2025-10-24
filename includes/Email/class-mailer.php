<?php

namespace CareerNest\Email;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Mailer Class
 * 
 * Handles sending HTML emails with template variable replacement
 */
class Mailer
{
    /**
     * Send an email using a template
     * 
     * @param string $to Recipient email address
     * @param string $template_key Template key (e.g., 'applicant_welcome')
     * @param array $variables Template variables to replace
     * @param array $args Additional wp_mail arguments (cc, bcc, etc.)
     * @return bool Whether email was sent successfully
     */
    public static function send(string $to, string $template_key, array $variables = [], array $args = []): bool
    {
        // Get template
        $templates = new Templates();
        $template = $templates->get_template($template_key);

        if (!$template) {
            error_log("CareerNest: Email template '{$template_key}' not found");
            return false;
        }

        // Add default variables
        $variables = array_merge(self::get_default_variables(), $variables);

        // Replace variables in subject and body
        $subject = self::replace_variables($template['subject'], $variables);
        $body = self::replace_variables($template['body'], $variables);

        // Wrap body in HTML template
        $html_body = self::wrap_html_template($body);

        // Set up headers for HTML email
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Add additional headers from args
        if (!empty($args['headers'])) {
            if (is_array($args['headers'])) {
                $headers = array_merge($headers, $args['headers']);
            } else {
                $headers[] = $args['headers'];
            }
        }

        // Send email
        $sent = wp_mail($to, $subject, $html_body, $headers, $args['attachments'] ?? []);

        // Log if sending failed
        if (!$sent) {
            error_log("CareerNest: Failed to send email to {$to} using template '{$template_key}'");
        }

        return $sent;
    }

    /**
     * Replace template variables with actual values
     * 
     * @param string $content Content with {{variables}}
     * @param array $variables Key-value pairs of variables
     * @return string Content with variables replaced
     */
    private static function replace_variables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Support both {{key}} and {{KEY}} formats
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{' . strtoupper($key) . '}}', $value, $content);
        }
        return $content;
    }

    /**
     * Get default template variables available to all emails
     * 
     * @return array Default variables
     */
    private static function get_default_variables(): array
    {
        $pages = get_option('careernest_pages', []);

        return [
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'contact_email' => get_option('admin_email'),
            'current_year' => date('Y'),
            'jobs_url' => isset($pages['jobs']) ? get_permalink($pages['jobs']) : home_url(),
        ];
    }

    /**
     * Wrap email content in HTML template
     * 
     * @param string $content Email content
     * @return string Complete HTML email
     */
    private static function wrap_html_template(string $content): string
    {
        // Get primary color from options or use default
        $primary_color = '#0073aa';

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerNest Email</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background-color: ' . esc_attr($primary_color) . ';
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body p {
            margin: 0 0 16px 0;
        }
        .email-body ul {
            margin: 16px 0;
            padding-left: 20px;
        }
        .email-body li {
            margin-bottom: 8px;
        }
        .email-button {
            display: inline-block;
            padding: 14px 28px;
            margin: 20px 0;
            background-color: ' . esc_attr($primary_color) . ';
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #666666;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid ' . esc_attr($primary_color) . ';
            padding: 20px;
            margin: 20px 0;
        }
        .credentials-box p {
            margin: 8px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            .email-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>CareerNest</h1>
        </div>
        <div class="email-body">
            ' . wp_kses_post(wpautop($content)) . '
        </div>
        <div class="email-footer">
            <p><strong>CareerNest</strong></p>
            <p>' . esc_html(get_bloginfo('name')) . '</p>
            <p>' . esc_html(home_url()) . '</p>
            <p>&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Send a plain text email (fallback)
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool Whether email was sent
     */
    public static function send_plain(string $to, string $subject, string $message): bool
    {
        return wp_mail($to, $subject, $message);
    }

    /**
     * Send admin notification email
     * 
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool Whether email was sent
     */
    public static function send_admin_notification(string $subject, string $message): bool
    {
        $admin_email = get_option('admin_email');

        return self::send($admin_email, 'admin_notification', [
            'message' => $message,
            'subject_text' => $subject,
        ]);
    }
}

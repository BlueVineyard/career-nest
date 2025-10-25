<?php

namespace CareerNest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profile Helper Class
 * 
 * Provides helper functions for applicant profile management
 */
class Profile_Helper
{
    /**
     * Calculate profile completeness percentage
     * 
     * @param int $applicant_id Applicant post ID
     * @return array Array with 'percentage' and 'missing_fields'
     */
    public static function calculate_completeness(int $applicant_id): array
    {
        if (!$applicant_id) {
            return ['percentage' => 0, 'missing_fields' => []];
        }

        $fields = [
            'professional_title' => [
                'label' => 'Professional Title',
                'weight' => 10,
                'check' => fn() => !empty(get_post_meta($applicant_id, '_professional_title', true))
            ],
            'phone' => [
                'label' => 'Phone Number',
                'weight' => 5,
                'check' => fn() => !empty(get_post_meta($applicant_id, '_phone', true))
            ],
            'location' => [
                'label' => 'Location',
                'weight' => 10,
                'check' => fn() => !empty(get_post_meta($applicant_id, '_location', true))
            ],
            'personal_summary' => [
                'label' => 'Personal Summary',
                'weight' => 15,
                'check' => fn() => !empty(get_post_field('post_content', $applicant_id))
            ],
            'skills' => [
                'label' => 'Skills',
                'weight' => 15,
                'check' => function () use ($applicant_id) {
                    $skills = get_post_meta($applicant_id, '_skills', true);
                    return is_array($skills) && count($skills) >= 3;
                }
            ],
            'work_experience' => [
                'label' => 'Work Experience',
                'weight' => 20,
                'check' => function () use ($applicant_id) {
                    $experience = get_post_meta($applicant_id, '_experience', true);
                    return is_array($experience) && count($experience) >= 1;
                }
            ],
            'education' => [
                'label' => 'Education',
                'weight' => 15,
                'check' => function () use ($applicant_id) {
                    $education = get_post_meta($applicant_id, '_education', true);
                    return is_array($education) && count($education) >= 1;
                }
            ],
            'work_preferences' => [
                'label' => 'Work Preferences',
                'weight' => 5,
                'check' => function () use ($applicant_id) {
                    $work_types = get_post_meta($applicant_id, '_work_types', true);
                    return is_array($work_types) && count($work_types) >= 1;
                }
            ],
            'linkedin' => [
                'label' => 'LinkedIn Profile',
                'weight' => 5,
                'check' => fn() => !empty(get_post_meta($applicant_id, '_linkedin_url', true))
            ],
        ];

        $total_weight = 0;
        $completed_weight = 0;
        $missing_fields = [];

        foreach ($fields as $key => $field) {
            $total_weight += $field['weight'];

            if ($field['check']()) {
                $completed_weight += $field['weight'];
            } else {
                $missing_fields[] = $field['label'];
            }
        }

        $percentage = $total_weight > 0 ? round(($completed_weight / $total_weight) * 100) : 0;

        return [
            'percentage' => $percentage,
            'missing_fields' => $missing_fields
        ];
    }

    /**
     * Get profile strength tips based on completeness
     * 
     * @param int $applicant_id Applicant post ID
     * @return array Array of tip strings
     */
    public static function get_profile_tips(int $applicant_id): array
    {
        $completeness = self::calculate_completeness($applicant_id);
        $tips = [];

        if ($completeness['percentage'] < 100) {
            $tips[] = "Complete your profile to improve visibility to employers.";
        }

        // Check specific missing fields and provide targeted tips
        $skills = get_post_meta($applicant_id, '_skills', true);
        if (!is_array($skills) || count($skills) < 5) {
            $tips[] = "Add more skills to your profile - profiles with 5+ skills get 40% more views.";
        }

        $summary = get_post_field('post_content', $applicant_id);
        if (empty($summary)) {
            $tips[] = "Add a personal summary to help employers understand your career goals.";
        }

        $experience = get_post_meta($applicant_id, '_experience', true);
        if (!is_array($experience) || count($experience) < 2) {
            $tips[] = "Add more work experience entries to showcase your career history.";
        }

        $linkedin = get_post_meta($applicant_id, '_linkedin_url', true);
        if (empty($linkedin)) {
            $tips[] = "Link your LinkedIn profile to build credibility with employers.";
        }

        return $tips;
    }

    /**
     * Get profile completion color based on percentage
     * 
     * @param int $percentage Completion percentage
     * @return string Color code
     */
    public static function get_completion_color(int $percentage): string
    {
        if ($percentage >= 80) {
            return '#10b981'; // Green
        } elseif ($percentage >= 50) {
            return '#f39c12'; // Orange
        } else {
            return '#e74c3c'; // Red
        }
    }

    /**
     * Get profile completion status text
     * 
     * @param int $percentage Completion percentage
     * @return string Status text
     */
    public static function get_completion_status(int $percentage): string
    {
        if ($percentage >= 90) {
            return 'Excellent';
        } elseif ($percentage >= 70) {
            return 'Good';
        } elseif ($percentage >= 50) {
            return 'Fair';
        } else {
            return 'Needs Work';
        }
    }
}

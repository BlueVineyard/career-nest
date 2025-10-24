# Job Posting/Editing Feature - Employer Dashboard

## Overview

Comprehensive frontend job posting and editing system for employer dashboard users. Allows employers to create, edit, and manage job listings without accessing the WordPress admin panel.

## Implementation Files

### Backend

- **`includes/class-job-ajax-handler.php`** - AJAX handler for job operations
  - `cn_create_job` - Create new job listing
  - `cn_update_job` - Update existing job listing
  - `cn_get_job_data` - Fetch job data for editing
  - `cn_delete_job` - Move job to trash

### Frontend

- **`assets/js/employer-dashboard-job-management.js`** - Job management JavaScript

  - Modal handling (open/close)
  - Form submission via AJAX
  - Data loading for editing
  - Error/success messaging

- **`assets/css/employer-dashboard.css`** - Modal and form styling

  - Responsive modal design
  - Form field styling
  - Error/success message styling

- **`templates/template-employer-dashboard.php`** - Updated with job modal HTML

## Features

### Job Creation

- **Modal Form** - Professional modal interface for job posting
- **Fields Supported:**
  - Job Title (required)
  - Location (with optional remote flag)
  - Opening Date
  - Closing Date
  - Salary Range
  - Apply Externally (with URL/email field)
  - Overview (WYSIWYG)
  - Who We Are (WYSIWYG)
  - What We Offer (WYSIWYG)
  - Key Responsibilities (WYSIWYG)
  - How to Apply (WYSIWYG)

### Job Editing

- Load existing job data into modal form
- Update job details via AJAX
- Same validation as creation
- Permission checks (employer must own the job)

### Security

- ✅ Nonce verification on all AJAX requests
- ✅ Capability checks (`edit_posts` required)
- ✅ Employer ownership validation
- ✅ Input sanitization (sanitize_text_field, wp_kses_post)
- ✅ XSS protection via proper escaping

### User Experience

- **Modal Interface** - No page reloads
- **Loading States** - Visual feedback during operations
- **Error Handling** - Clear error messages
- **Success Feedback** - Confirmation messages
- **Auto-reload** - Dashboard refreshes after save
- **Keyboard Support** - ESC key to close modal
- **Responsive Design** - Mobile-friendly modal

## Usage

### For Employers

1. **Post Job:**

   - Click "Post New Job" button
   - Fill out job details in modal
   - Click "Post Job" to publish

2. **Edit Job:**

   - Click "Edit" button on any job card
   - Modify details in modal
   - Click "Update Job" to save changes

3. **Delete Job:**
   - Click delete button (when implemented)
   - Confirm deletion
   - Job moves to trash

### Workflow

```
User clicks "Post New Job"
  ↓
Modal opens with blank form
  ↓
User fills job details
  ↓
User submits form
  ↓
AJAX request to cn_create_job
  ↓
Validation & security checks
  ↓
Create job post + meta data
  ↓
Success response
  ↓
Show success message
  ↓
Reload dashboard (updated job list)
```

## Technical Details

### AJAX Endpoints

All endpoints require:

- Valid nonce (`careernest_job_nonce`)
- User logged in with `employer_team` role
- User linked to an employer profile

**Create Job:**

```php
POST /wp-admin/admin-ajax.php
action: cn_create_job
nonce: [nonce]
[job fields...]
```

**Update Job:**

```php
POST /wp-admin/admin-ajax.php
action: cn_update_job
nonce: [nonce]
job_id: [ID]
[job fields...]
```

**Get Job Data:**

```php
POST /wp-admin/admin-ajax.php
action: cn_get_job_data
nonce: [nonce]
job_id: [ID]
```

**Delete Job:**

```php
POST /wp-admin/admin-ajax.php
action: cn_delete_job
nonce: [nonce]
job_id: [ID]
```

### Permissions Model

- Only employers linked to a valid employer profile can post jobs
- Jobs automatically linked to employer and posting user
- Employers can only edit/delete their own jobs
- Superadmins retain full access via admin panel

### Data Storage

Jobs stored as `job_listing` CPT with meta fields:

- `_employer_id` - Linked employer profile
- `_posted_by` - User who created the job
- `_job_location` - Location string
- `_remote_position` - Boolean flag
- `_opening_date` - YYYY-MM-DD format
- `_closing_date` - YYYY-MM-DD format
- `_salary_range` - Freeform text
- `_apply_externally` - Boolean flag
- `_external_apply` - URL or email
- `_job_overview` - HTML content
- `_job_who_we_are` - HTML content
- `_job_what_we_offer` - HTML content
- `_job_responsibilities` - HTML content
- `_job_how_to_apply` - HTML content

## Future Enhancements

- [ ] Add job category/type selection in modal
- [ ] Google Maps integration for location picking
- [ ] Draft save functionality
- [ ] Duplicate job feature
- [ ] Bulk job actions
- [ ] Job preview before publishing
- [ ] Rich text editor (TinyMCE) integration
- [ ] File attachments for job descriptions

## Testing Checklist

- [ ] Create new job from empty dashboard
- [ ] Create new job with existing jobs
- [ ] Edit job and verify changes
- [ ] Test validation (empty title, etc.)
- [ ] Test remote position toggle
- [ ] Test apply externally toggle
- [ ] Verify employer ownership checks
- [ ] Test on mobile devices
- [ ] Test with multiple browser tabs
- [ ] Verify job appears on jobs listing page

## Notes

- Jobs are published immediately (no draft state in frontend)
- All WYSIWYG fields support basic HTML formatting
- Modal uses native WordPress sanitization functions
- System follows CareerNest security patterns
- Compatible with WordPress multisite

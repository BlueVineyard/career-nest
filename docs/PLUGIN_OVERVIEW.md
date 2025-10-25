# CareerNest Plugin - Complete Overview & Feature Guide

## Table of Contents

1. [What Is CareerNest?](#what-is-careernest)
2. [Primary Use Case](#primary-use-case)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Core Features](#core-features)
5. [White-Label Branding](#white-label-branding)
6. [Technical Architecture](#technical-architecture)
7. [Current Status](#current-status)
8. [Setup Guide](#setup-guide)

---

## What Is CareerNest?

CareerNest is a **standalone WordPress job portal plugin** that transforms any WordPress site into a professional job board platform. Built exclusively with WordPress core APIs, it enables businesses to operate a multi-tenant job portal service.

### Key Characteristics

- **No Dependencies**: Zero reliance on third-party plugins (no ACF, no page builders)
- **WordPress Native**: Built entirely with WordPress core APIs
- **White-Label Ready**: Complete platform branding control
- **Multi-Tenant**: Designed to serve multiple employer clients
- **Professional UX**: Commercial-grade user interface
- **Security-First**: Comprehensive security implementation

---

## Primary Use Case

### 🎯 White-Label Job Portal Business

**Perfect for:** Entities like **AES (Adventist Employment Services)** or **Blue Vineyard Group** who want to:

- Operate their own branded job portal platform
- Serve multiple employer clients
- Control the entire hiring ecosystem
- Generate revenue through job posting services

### Business Model Architecture

```
Technical Layer: Super Admin (WordPress Administrator)
    ↓ (Site Installation & Configuration)

Business Layer: AES Admin (Platform Operator/Owner)
    ↓ (Platform Management & Client Relations)

Client Layer: Employer Team (Paying Customers)
    ↓ (Job Posting & Hiring)

End User Layer: Applicants/Job Seekers
```

### Real-World Example

**Blue Vineyard Group installs CareerNest:**

1. **Branding:**

   - Sets Platform Name: "Blue Vineyard Careers"
   - Uploads Blue Vineyard logo
   - Configures email: noreply@bluevineyard.com
   - Sets sender name: "Blue Vineyard Team"

2. **Operations:**

   - Employers register for accounts
   - Blue Vineyard approves registrations
   - Employers post jobs and manage applications
   - Job seekers apply through Blue Vineyard's branded platform

3. **User Experience:**
   - Employers see: "Blue Vineyard Careers" everywhere
   - Emails from: "Blue Vineyard Team <noreply@bluevineyard.com>"
   - Professional branded dashboard
   - Zero "CareerNest" references visible

---

## User Roles & Permissions

### 1. Super Admin (WordPress Administrator)

**Purpose:** Technical site owner/developer

**Responsibilities:**

- WordPress installation and hosting
- Plugin/theme installation and updates
- Database management
- Server configuration
- Technical support and maintenance

**CareerNest Access:**

- ✅ Full access to all CareerNest features
- ✅ Full WordPress admin access (Users, Plugins, Themes, etc.)
- ✅ Can manage CareerNest settings, requests, and data
- ⚠️ **Too powerful for day-to-day business operations**

**When to Use:** Initial setup and technical maintenance only

---

### 2. AES Admin (Platform Business Manager)

**Purpose:** Business operator managing the job portal

**Responsibilities:**

- Daily platform operations
- Approve employer/employee requests
- Manage platform branding
- Configure platform settings
- Handle deletion requests
- Customer service for employers

**CareerNest Access:**

- ✅ Full CareerNest management (`manage_careernest` capability)
- ✅ All request queues (Account Requests, Employee Requests, Deletion Requests)
- ✅ All settings (Branding, Appearance, Email Templates, General, Dashboard)
- ✅ View/edit all jobs, employers, applicants, applications
- ✅ Access CareerNest admin menu and all submenus
- ❌ **Cannot** install plugins or change themes
- ❌ **Cannot** access WordPress core settings
- ❌ **Cannot** manage WordPress users directly

**When to Use:** Daily business operations - this is the primary operational role

---

### 3. Employer Team (Client Companies)

**Purpose:** Employer clients posting jobs and reviewing candidates

**Access Level:** **Frontend-Only** (No WordPress Admin)

**What Owners Can Do:**

- ✅ Post and manage job listings
- ✅ Review job applications with full candidate data
- ✅ Edit company profile (frontend)
- ✅ Invite team members (requires admin approval)
- ✅ Request team member removal (requires admin approval)
- ✅ Access professional employer dashboard
- ✅ View candidate profiles and resumes
- ✅ Manage team settings

**What Team Members Can Do:**

- ✅ Post and manage job listings
- ✅ Review job applications
- ✅ Access employer dashboard
- ✅ View candidate profiles
- ❌ Cannot edit company profile
- ❌ Cannot manage team

**Dashboard Features:**

- Job statistics (total, active, filled, expired)
- Application statistics (total, new, reviewed)
- Recent jobs list
- Recent applications list
- Quick actions menu
- Personal profile editing
- Company information display

---

### 4. Applicants (Job Seekers)

**Purpose:** Job seekers searching and applying for positions

**Registration Options:**

- Apply as guest (automatic account creation)
- Register directly for account
- Converted guest users

**Features:**

- ✅ Browse jobs publicly (no login required)
- ✅ Apply as guest with automatic account creation
- ✅ Comprehensive profile management
- ✅ Unlimited work experience entries
- ✅ Unlimited education entries
- ✅ Unlimited certifications
- ✅ Skills and languages
- ✅ Resume upload
- ✅ Application tracking dashboard
- ✅ Public profile pages
- ✅ Application history

**Dashboard:**

- Application statistics
- Application list with status
- Profile completeness indicator
- Profile editing forms
- Public profile link

---

### 5. Guest Users

**Public Access:**

- ✅ Browse job listings
- ✅ View employer profiles
- ✅ Submit job applications (converts to registered user)
- ✅ Search and filter jobs

---

## Core Features

### 🎨 White-Label Branding System

**Complete Platform Customization:**

**Settings Location:** CareerNest → Settings → Branding

**Available Options:**

1. **Platform Name**

   - Replaces "CareerNest" globally
   - Used in emails, dashboards, all user-facing text
   - Example: "Blue Vineyard Careers", "AES Careers"

2. **Platform Logo**

   - Uploaded via WordPress Media Library
   - Used in emails and dashboards
   - Recommended: 200x100px or similar aspect ratio

3. **Email From Name**

   - Sender name in all emails
   - Example: "AES Team", "Blue Vineyard Team"
   - Default: "The [Platform Name] Team"

4. **Email From Address**

   - Sender email address (overrides SMTP)
   - Example: "no-reply@adventistemployment.org.au"
   - Priority 999 filter overrides SMTP plugins

5. **Support Email**
   - Contact email for user support
   - Used in email content
   - Default: WordPress admin email

**Impact:** All emails, dashboards, and user-facing content show your brand, not "CareerNest"

---

### 👔 Employer Management

#### Registration & Approval Workflow

**Step 1: Employer Registration**

- Employer submits registration via frontend form
- Creates employer CPT with "pending" status
- Stores company information

**Step 2: Admin Review**

- Appears in: **CareerNest → Account Requests**
- Admin reviews company details
- Options: Approve, Decline, Request More Info

**Step 3: Account Creation**

- Approval creates WordPress user account
- Assigns `employer_team` role
- Links user to employer CPT (bidirectional)
- Sets user as owner (`_user_id` meta)
- Publishes employer profile
- Sends welcome email with credentials

**Step 4: Employer Onboarding**

- Owner logs in to dashboard
- Can edit company profile (frontend)
- Can start posting jobs
- Can invite team members

#### Team Management

**Owner Capabilities:**

- **Invite Team Members:**
  - Request sent to admin
  - Admin approves via **Employee Requests** page
  - New member gets account and access
- **Remove Team Members:**
  - Request sent to admin
  - Admin approves via **Deletion Requests** page
  - User account permanently deleted

**Team Member Access:**

- Share access to company's jobs
- Can post and manage jobs
- Can review applications
- Cannot edit company profile
- Cannot manage team

#### Company Profile

**Editable Fields (Owner Only):**

- Company name, tagline, location
- Website, contact email, phone
- Company size, founded year
- Industry, specialities
- About, mission statement
- Company spotlight
- "Interested in working for us?" message

**Public Display:**

- Public-facing company profile page
- Listed jobs
- Company overview with icons
- Professional design

---

### 💼 Job Management

#### Job Posting Features

**Job Fields:**

- Title, location, salary range
- Remote position option
- Opening/closing dates
- Overview, responsibilities
- "Who We Are", "What We Offer"
- "How to Apply" instructions
- Category & type taxonomies
- External application option

**Job Management:**

- Create via frontend form
- Edit existing jobs
- Delete jobs
- Track applications per job
- View job statistics (active, filled, expired, draft)

**Job Display:**

- Public job listing page
- Single job detail pages
- Related jobs
- Company information
- Application button/link

---

### 📋 Application System

#### Guest Application Flow

**Advantage:** Reduces friction - no registration required to apply

**Process:**

1. Guest fills out application form
2. Uploads resume (PDF/DOC/DOCX)
3. Submits application
4. **System automatically:**
   - Creates WordPress user account
   - Generates secure password
   - Creates applicant profile CPT
   - Links application to user
   - Sends welcome email with password reset link

**Result:** Guest becomes registered user with tracked application

#### Registered User Applications

**Process:**

1. One-click apply with saved profile
2. Profile data auto-populated
3. Can attach different resume
4. Instant submission

**Benefits:**

- Faster application process
- Consistent profile data
- Application history tracking

#### Application Management

**For Employers:**

- View all applications
- Filter by job, status, date
- Search candidates
- Update application status
- View candidate profiles
- Download resumes
- Contact applicants

**For Applicants:**

- Track all applications
- View application status
- See application dates
- Access applied job details

---

### ⚙️ Admin Request System

All request systems accessible to both Super Admin and AES Admin.

#### 1. Account Requests (Employer Registrations)

**Location:** CareerNest → Account Requests

**Features:**

- List of pending employer registrations
- View company details
- Approve (creates account + publishes profile)
- Decline (deletes request + sends email)
- Request more information

#### 2. Employee Requests (Team Member Additions)

**Location:** CareerNest → Employee Requests

**Features:**

- List of pending team member requests
- View requester and company details
- Approve (creates account + links to employer)
- Decline (deletes temp user + notifies requester)

#### 3. Deletion Requests (Team Member Removals)

**Location:** CareerNest → Deletion Requests

**Features:**

- List of pending deletion requests
- View team member, company, and requester
- Approve (permanently deletes user account)
- Decline (keeps user active + notifies requester)
- Double confirmation for safety

---

### 🎨 Appearance Customization

**Location:** CareerNest → Settings → Appearance

**Customizable Colors:**

- Primary button color
- Secondary button color
- Primary text color
- Secondary text color
- Success badge color (active jobs, hired status)
- Warning badge color (draft jobs, reviewed apps)
- Danger badge color (expired jobs, rejected)

**Layout:**

- Container width (1140px, 1200px, 1320px, 1400px, Full Width)

**Application:** All colors apply site-wide with CSS variables

---

### 📧 Email System

#### Email Configuration

**Branding Controls:**

- Email From Name (overrides SMTP)
- Email From Address (overrides SMTP)
- Support Email (for content)

**Email Types:**

- Employer approval/decline
- Employee approval/decline
- Team deletion approved/declined
- Guest application confirmation
- Application linked notification
- Welcome emails with credentials

**Customization:**

- Email templates editable in settings
- Template variables available
- HTML formatted emails

---

### 📊 Dashboard Features

#### Employer Dashboard

**Access:** Frontend only (not WordPress admin)

**Statistics Cards:**

- Total jobs, Active jobs
- Total applications, New applications

**Sections:**

- Recent job listings with actions
- Recent applications with filtering
- Personal profile editing
- Company information display
- Quick actions menu

**Actions:**

- Post new job
- Manage jobs
- View applications
- Manage team
- Edit personal profile
- View public profile

#### Applicant Dashboard

**Statistics:**

- Total applications
- Applications by status
- Profile completeness

**Features:**

- Application tracking list
- Profile management
- Work experience (unlimited entries)
- Education (unlimited entries)
- Certifications (unlimited entries)
- Skills, languages, bio
- Resume upload
- Public profile link

---

## Technical Architecture

### Data Structure

```
WordPress Users
    ↓
    ├─ Employer CPT (Company Profiles)
    │    ↓
    │    └─ Job Listings CPT
    │         ↓
    │         └─ Job Applications CPT
    │
    └─ Applicant CPT (User Profiles)
         ↓
         └─ Job Applications CPT
```

### Security Model

**Every Action Protected By:**

- ✅ Nonce verification (CSRF protection)
- ✅ Capability checks (role-based access)
- ✅ Input sanitization (data validation)
- ✅ Output escaping (XSS prevention)
- ✅ File upload validation (security)

### Performance Optimization

- Conditional asset loading (page-specific CSS/JS)
- Optimized database queries
- Efficient meta queries
- No unnecessary WordPress queries
- Minimal memory footprint

---

## Current Status

### ✅ Completed Features (Milestones 1-6)

**Foundation:**

- Plugin activation/deactivation system
- Custom post types (Job, Employer, Applicant, Application)
- Taxonomies (Job Category, Job Type)
- Role system with custom capabilities

**Admin Interface:**

- Hierarchical admin menus
- Meta boxes with Google Maps integration
- Admin columns with filtering
- Request management pages
- Settings pages with tabs

**Frontend:**

- Guest application system with auto-account creation
- Applicant dashboard (fully functional)
- Employer dashboard (functional)
- Job posting form (frontend)
- Team management interface
- Company profile editing (frontend)
- Job application form
- Template routing system

**Recent Additions:**

- ✅ Frontend company profile editing for owners
- ✅ Team member deletion request system
- ✅ Deletion Requests admin page
- ✅ White-label branding system
- ✅ Email sender override (name & address)
- ✅ All request pages accessible to AES Admin
- ✅ Complete email branding integration

### 🚧 In Progress

- Job listing frontend improvements
- Advanced filtering and search
- Enhanced employer registration flow

### 📋 Planned Features

- Enhanced email template system
- Advanced analytics dashboard
- Bulk action tools
- Data export capabilities
- API integrations
- Premium features (featured jobs, etc.)

---

## Role Permission Matrix

| Capability              | Super Admin | AES Admin | Employer Owner | Team Member | Applicant | Guest |
| ----------------------- | ----------- | --------- | -------------- | ----------- | --------- | ----- |
| **WordPress Core**      |
| Install Plugins         | ✅          | ❌        | ❌             | ❌          | ❌        | ❌    |
| Manage WP Users         | ✅          | ❌        | ❌             | ❌          | ❌        | ❌    |
| Edit Themes             | ✅          | ❌        | ❌             | ❌          | ❌        | ❌    |
| **Platform Management** |
| Branding Settings       | ✅          | ✅        | ❌             | ❌          | ❌        | ❌    |
| Approve Requests        | ✅          | ✅        | ❌             | ❌          | ❌        | ❌    |
| Platform Settings       | ✅          | ✅        | ❌             | ❌          | ❌        | ❌    |
| View All Data           | ✅          | ✅        | ❌             | ❌          | ❌        | ❌    |
| **Employer Functions**  |
| Edit Company Profile    | ✅          | ✅        | ✅             | ❌          | ❌        | ❌    |
| Invite Team Members     | ✅          | ✅        | ✅             | ❌          | ❌        | ❌    |
| Remove Team Members     | ✅          | ✅        | ✅             | ❌          | ❌        | ❌    |
| Post Jobs               | ✅          | ✅        | ✅             | ✅          | ❌        | ❌    |
| Manage Jobs             | ✅          | ✅        | ✅             | ✅          | ❌        | ❌    |
| Review Applications     | ✅          | ✅        | ✅             | ✅          | ❌        | ❌    |
| **Applicant Functions** |
| Apply to Jobs           | ✅          | ✅        | ✅             | ✅          | ✅        | ✅    |
| Manage Profile          | ✅          | ✅        | ❌             | ❌          | ✅        | ❌    |
| Track Applications      | ✅          | ✅        | ❌             | ❌          | ✅        | ❌    |
| **Public Access**       |
| Browse Jobs             | ✅          | ✅        | ✅             | ✅          | ✅        | ✅    |
| View Profiles           | ✅          | ✅        | ✅             | ✅          | ✅        | ✅    |

---

## White-Label Branding

### Configuration

**Access:** CareerNest → Settings → Branding (AES Admin or Super Admin)

**Settings:**

1. **Platform Name**

   - Your platform name (e.g., "Blue Vineyard Careers")
   - Replaces "CareerNest" throughout the platform
   - Used in emails, dashboards, all user-facing text

2. **Platform Logo**

   - Upload via WordPress Media Library
   - Recommended size: 200x100px
   - Used in emails and dashboards

3. **Email From Name**

   - Sender name in all emails
   - Example: "AES Team"
   - Leave empty for: "The [Platform Name] Team"

4. **Email From Address**

   - Sender email (overrides SMTP plugins)
   - Example: "no-reply@adventistemployment.org.au"
   - Leave empty to use WordPress admin email

5. **Support Email**
   - Support contact in email content
   - Example: "support@bluevineyard.com"
   - Leave empty to use WordPress admin email

### Helper Functions

For developers extending the plugin:

```php
cn_get_platform_name()           // Get platform name
cn_get_platform_logo()           // Get logo URL
cn_get_platform_logo_html()      // Get logo HTML
cn_get_email_from_name()         // Get email sender name
cn_get_email_from_address()      // Get email sender address
cn_get_support_email()           // Get support email
cn_replace_branding($text)       // Batch replace branding in text
```

### Email Override System

**WordPress Filters (Priority 999):**

- `wp_mail_from_name` → Uses Email From Name
- `wp_mail_from` → Uses Email From Address

**Result:** Overrides SMTP plugin settings automatically for all CareerNest emails

---

## Setup Guide

### Initial Installation

**Prerequisites:**

- WordPress 6.0+
- PHP 8.0+
- Modern hosting environment

**Steps:**

1. **Install Plugin** (Super Admin)

   - Upload plugin to `/wp-content/plugins/`
   - Activate via Plugins menu
   - Plugin creates required pages and roles

2. **Create AES Admin User** (Super Admin)

   - Go to: Users → Add New
   - Create user with email/password
   - Assign role: **AES Admin**
   - This user will manage daily operations

3. **Configure Branding** (AES Admin)

   - Log in as AES Admin
   - Go to: CareerNest → Settings → Branding
   - Set Platform Name, Logo, Email settings
   - Save Changes

4. **Configure Appearance** (AES Admin)

   - CareerNest → Settings → Appearance
   - Set brand colors
   - Set container width
   - Save Changes

5. **Optional: Google Maps** (AES Admin)

   - CareerNest → Settings → General
   - Add Google Maps API key
   - Enables location autocomplete

6. **Ready for Business!**
   - Employers can now register
   - Jobs can be posted
   - Applicants can apply
   - Platform is fully operational

### Daily Operations (AES Admin)

**Morning Routine:**

1. Check **Account Requests** for new employer registrations
2. Check **Employee Requests** for team member additions
3. Check **Deletion Requests** for team member removals
4. Review any flagged applications or jobs

**Ongoing:**

- Approve legitimate requests
- Decline suspicious requests
- Provide customer support to employers
- Monitor platform usage
- Update branding/settings as needed

---

## Key Workflows

### Employer Onboarding

```
Registration → Pending Review → Admin Approval → Account Created →
Welcome Email → Login → Dashboard → Post First Job
```

### Job Application (Guest)

```
Browse Jobs → Find Job → Apply as Guest → Submit → Account Created →
Email Sent → Reset Password → Login → Dashboard → Track Application
```

### Job Application (Registered)

```
Browse Jobs → Find Job → Click Apply → Confirm → Submitted →
Dashboard Updated → Track Status
```

### Team Member Addition

```
Owner Invites → Request Created → Admin Notified → Admin Approves →
Account Created → Welcome Email → Member Logs In → Access Jobs
```

### Team Member Removal

```
Owner Clicks Delete → Confirm → Request Created → Admin Notified →
Admin Approves → Account Deleted → Notifications Sent
```

---

## File Structure

```
career-nest/
├── careernest.php                 # Main plugin file
├── includes/
│   ├── branding-helpers.php       # White-label helper functions
│   ├── class-activator.php        # Activation logic
│   ├── class-deactivator.php      # Deactivation logic
│   ├── class-plugin.php           # Core plugin orchestration
│   ├── Admin/
│   │   ├── class-admin.php        # Admin bootstrap
│   │   ├── class-admin-menus.php  # Menu structure
│   │   ├── class-settings.php     # Settings management
│   │   ├── class-meta-boxes.php   # CPT meta boxes
│   │   ├── class-employer-requests.php
│   │   ├── class-employee-requests.php
│   │   └── class-deletion-requests.php
│   ├── Data/
│   │   ├── class-cpt.php          # Custom Post Types
│   │   ├── class-roles.php        # User roles
│   │   └── class-taxonomies.php   # Taxonomies
│   ├── Security/
│   │   └── class-caps.php         # Capabilities
│   ├── Email/
│   │   ├── class-mailer.php       # Email sending
│   │   └── class-templates.php    # Email templates
│   └── Shortcodes/
│       └── class-login.php        # Login shortcode
├── templates/
│   ├── single-job_listing.php     # Single job page
│   ├── single-employer.php        # Company profile
│   ├── single-applicant.php       # Applicant profile
│   ├── template-jobs.php          # Job listing page
│   ├── template-employer-dashboard.php
│   ├── template-employer-profile-edit.php
│   ├── template-employer-team.php
│   ├── template-applicant-dashboard.php
│   └── template-apply-job.php
├── assets/
│   ├── css/                       # Stylesheets
│   └── js/                        # JavaScript
└── docs/
    └── PLUGIN_OVERVIEW.md         # This file
```

---

## Best Practices

### For Platform Operators (AES Admin)

1. **Regular Monitoring:** Check request queues daily
2. **Prompt Approvals:** Process requests within 24 hours
3. **Clear Communication:** Use decline reasons when rejecting
4. **Branding Consistency:** Keep branding settings up-to-date
5. **Support Responsiveness:** Respond to employer inquiries promptly

### For Security

1. **Strong Passwords:** Enforce for all users
2. **Regular Updates:** Keep WordPress core updated
3. **Backup System:** Regular database backups
4. **Monitor Activity:** Watch for suspicious patterns
5. **SSL Certificate:** Use HTTPS for all traffic

### For Performance

1. **Image Optimization:** Compress uploaded logos/resumes
2. **Caching:** Use WordPress caching plugins if needed
3. **Database Maintenance:** Regular optimization
4. **Monitor Queries:** Watch slow query logs
5. **CDN Usage:** Consider for high-traffic sites

---

## Support & Documentation

### Admin Documentation

**Location:** WordPress Admin → CareerNest → Settings

Each setting has inline help text explaining:

- What it does
- How to use it
- Recommended values
- Examples

### Developer Documentation

**For Theme Developers:**

- Templates can be overridden in theme's `careernest/` directory
- Hooks and filters available throughout
- CSS classes follow BEM-like naming: `cn-*`
- Helper functions for branding integration

### Getting Help

**Technical Issues:** Check WordPress debug.log
**Business Questions:** Review this documentation
**Feature Requests:** Contact plugin developer

---

## Summary

CareerNest is a **complete white-label job portal solution** designed for businesses operating job board platforms. With comprehensive role management, professional user interfaces, and complete branding control, it enables entities like Blue Vineyard or AES to:

✅ **Operate branded job portal businesses**
✅ **Serve multiple employer clients professionally**
✅ **Provide commercial-grade user experience**
✅ **Maintain complete platform control**
✅ **Scale operations efficiently**

The plugin is production-ready for the core job portal workflow with ongoing enhancements planned for advanced features.

# CareerNest Plugin - Client Setup & Usage Guide

## Table of Contents

1. [Installation](#installation)
2. [Initial Setup Checklist](#initial-setup-checklist)
3. [Settings Walkthrough](#settings-walkthrough)
4. [Admin Menu Overview](#admin-menu-overview)
5. [Adding Jobs](#adding-jobs)
6. [Managing Employers](#managing-employers)
7. [Managing Applicants](#managing-applicants)
8. [Frontend User Flows](#frontend-user-flows)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Installation

### Step 1: Upload Plugin

1. Download the `career-nest` plugin folder
2. Upload to `/wp-content/plugins/` directory via:
   - FTP/SFTP
   - cPanel File Manager
   - WordPress Admin → Plugins → Add New → Upload Plugin

### Step 2: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "CareerNest" in the list
3. Click "Activate"
4. **Important**: Plugin automatically creates required pages and database tables

### Step 3: Verify Installation

✅ Check that these pages were created:

- Jobs Listing Page
- Login/Register Page
- Forgot Password Page
- Employer Dashboard
- Applicant Dashboard
- Apply for Job Page
- Register Employer Page
- Register Applicant Page
- Register Employee Page

**Location**: WordPress Admin → Pages → Look for pages with "(CareerNest)" in the title

---

## Initial Setup Checklist

### Immediately After Installation

#### 1. ✅ Verify Pages Were Created

- [ ] Go to **Pages → All Pages**
- [ ] Confirm all 9 CareerNest pages exist
- [ ] Check that pages are Published (not Draft)
- [ ] Note: These pages are hidden from non-admins

#### 2. ✅ Check Permalinks

- [ ] Go to **Settings → Permalinks**
- [ ] Click "Save Changes" (no changes needed, just flush rewrite rules)
- [ ] This ensures custom post types work correctly

#### 3. ✅ Configure Basic Settings

- [ ] Go to **Job Listings → Settings**
- [ ] Set your **Google Maps API key** (optional but recommended)
- [ ] Configure **appearance settings** (colors, container width)
- [ ] Set **email settings** (from name, from email)

#### 4. ✅ Create Test Data

- [ ] Add at least one Job Category
- [ ] Add at least one Job Type
- [ ] Create a test employer
- [ ] Create a test job listing

#### 5. ✅ Test Frontend Pages

- [ ] Visit the Jobs Listing page
- [ ] Try the job search/filter functionality
- [ ] View a single job listing
- [ ] Test the application process (guest application)

---

## Settings Walkthrough

### Location: Job Listings → Settings

### Tab 1: General Settings

#### Google Maps Integration

- **Maps API Key**: Enter your Google Maps JavaScript API key
  - Required for: Location autocomplete, map picking
  - Get key from: https://console.cloud.google.com/
  - Enable APIs: Maps JavaScript API, Places API
- **Country Restrictions**: Limit location searches to specific countries
  - Leave empty for worldwide
  - Enter country codes (e.g., US, CA, GB)
  - Useful for regional job boards

#### Job Listings Display

- **Jobs Per Page**: Number of jobs shown on listings page (default: 9)
- **Column Layout**: 1, 2, or 3 columns for job cards
- **Filter Position**: Left sidebar, right sidebar, or top bar
- **Enable Filters**: Toggle individual filters on/off
  - Category filter
  - Job type filter
  - Location filter
  - Employer filter
  - Salary range filter
  - Date posted filter
  - Sort options

### Tab 2: Appearance Settings

#### Color Scheme

- **Primary Button Color**: Main brand color (default: #FF8200)
  - Used for: Buttons, links, accents
  - Changes globally across plugin
- **Secondary Button Color**: Alternative button color
- **Text Colors**: Primary and secondary text
- **Badge Colors**: Success, warning, danger badges

#### Layout

- **Container Width**: Max width of content (default: 1200px)
  - Options: 1000px, 1200px, 1400px, 100% (full width)

### Tab 3: Email Settings

#### Email Configuration

- **From Name**: Name shown in emails (e.g., "CareerNest Jobs")
- **From Email**: Email address for outgoing emails
- **Email Footer**: Text appended to all emails

#### Email Templates (Future Enhancement)

- Application confirmation emails
- Status update notifications
- Password reset emails

### Tab 4: Advanced Settings

#### Access Control

- **Hide CareerNest Pages**: Hide plugin pages from non-admin users in page list
- **Admin Bar Menu**: Show/hide CareerNest quick access menu in admin bar

#### Data Management

- **Export/Import**: Backup and restore plugin data
- **Delete Data on Uninstall**: Remove all data when plugin is deleted

---

## Admin Menu Overview

### Location: WordPress Admin Sidebar → "💼 Job Listings"

### Menu Structure

#### 1. **All Job Listings**

- View all jobs in the system
- Quick edit job details
- Bulk actions (trash, change status)
- Custom columns: Company, Location, Salary, Closing Date, Status

#### 2. **Add New Job**

- Create jobs from backend
- **Important**: Must select an employer first
- All fields same as frontend job form

#### 3. **Job Categories**

- Hierarchical taxonomy (can have parent/child categories)
- Examples: Technology, Healthcare, Marketing
- Used for job filtering and organization

#### 4. **Job Types**

- Flat taxonomy (no hierarchy)
- Examples: Full-Time, Part-Time, Contract, Freelance
- Color-coded in frontend display

#### 5. **Employers**

- View all company profiles
- Edit company information
- Link to employer dashboard

#### 6. **Applicants**

- View all applicant profiles
- Access applicant information
- Link to applicant dashboard

#### 7. **Applications**

- View all job applications
- Filter by status, job, applicant
- Update application status
- View application details

#### 8. **Requests** (Submenu)

- **Employer Requests**: Pending employer registrations
- **Employee Requests**: Team member access requests
- **Account Deletion Requests**: User data deletion requests

#### 9. **Team Management**

- Manage employer team members
- Grant/revoke access
- View team associations

#### 10. **Settings**

- Access all plugin configuration
- See "Settings Walkthrough" above

---

## Adding Jobs

### Method 1: Backend (Admin Panel)

#### Step-by-Step Process

1. **Navigate to Add New Job**

   - Go to: **Job Listings → Add New**

2. **Basic Information** (Top of page)

   - **Title**: Enter job title (e.g., "Senior WordPress Developer")
   - **Content Area**: Optional additional job description
   - **Featured Image**: Upload company logo (optional)

3. **Job Details Meta Box** (Right Sidebar)

   **Employer Selection** ⚠️ **CRITICAL**

   - Select the employer from dropdown
   - This links the job to a company
   - **Must be set or job won't display properly**

   **Location**

   - Click location field to trigger autocomplete
   - Select from suggestions for accurate coordinates
   - Check "Remote Position" if applicable

   **Job Dates**

   - Opening Date: When job became available
   - Closing Date: Application deadline
   - **Note**: Jobs auto-expire after closing date

   **Salary Information**

   - Choose: Range or Numeric
   - Range: Enter text (e.g., "$50k-$70k")
   - Numeric: Enter number for exact salary

   **Application Method**

   - Internal: Uses CareerNest application form
   - External: Provide URL or email for applications

4. **Job Content Sections** (Main Editor Area)

   **Available Fields:**

   - Job Overview: Brief summary
   - Who We Are: Company background
   - What We Offer: Benefits, perks
   - Key Responsibilities: Main duties
   - How to Apply: Application instructions

   **Tips:**

   - Use bullet points for readability
   - Keep paragraphs concise
   - Include key requirements

5. **Taxonomy Selection** (Right Sidebar)

   - **Categories**: Select one or more
   - **Job Types**: Select primary type
   - These enable filtering on frontend

6. **Additional Options**

   - **Position Filled**: Mark when hired
   - **Excerpt**: Custom job summary for listings

7. **Publish**
   - Click "Publish" to make live
   - Or "Save as Draft" to save for later

#### ⚠️ Important Notes

- **No Employer Selected**: Job won't show company info
- **No Closing Date**: Job stays active indefinitely
- **Missing Location**: Won't appear in location searches
- **No Categories/Types**: Limits discoverability

### Method 2: Frontend (Employer Dashboard)

#### Prerequisites

- Must be logged in as employer
- Must have employer profile

#### Step-by-Step Process

1. **Access Employer Dashboard**

   - Navigate to employer dashboard page
   - Or click "Dashboard" in account menu

2. **Click "Post a Job"**

   - Green button in top section
   - Or "Manage Jobs" → "Add New Job"

3. **Fill Job Form**

   - Same fields as backend
   - Real-time validation
   - Auto-saves to prevent data loss

4. **Location Field**

   - Type to get autocomplete suggestions
   - Or click map icon to pick on map
   - GPS button uses current location

5. **Review & Submit**
   - Preview shows how job will appear
   - Click "Publish Job" when ready

#### Frontend vs Backend Differences

| Feature         | Backend         | Frontend                          |
| --------------- | --------------- | --------------------------------- |
| Who can add     | Admins only     | Employers only                    |
| Employer field  | Must select     | Auto-populated (current employer) |
| User experience | WordPress admin | Custom branded interface          |
| Validation      | Server-side     | Real-time + server-side           |
| Preview         | No              | Yes (before publishing)           |

---

## Managing Employers

### Method 1: Backend Creation

#### When to Use

- Setting up initial demo data
- Creating employer on behalf of client
- Bulk import scenarios

#### Step-by-Step Process

1. **Go to: Job Listings → Employers → Add New**

2. **Company Profile**

   - **Title**: Company name (e.g., "Tech Innovations Inc.")
   - **Featured Image**: Company logo
   - **Excerpt**: Short company tagline

3. **Company Information Meta Box**

   **Contact Details:**

   - Contact Email (public)
   - Phone Number
   - Website URL
   - Location (use autocomplete)

   **Company Details:**

   - Tagline: One-line description
   - Industry Description: Sector/industry
   - Company Size: Employee count category
   - Founded Year: Establishment year
   - Specialities: Areas of expertise

   **Content Sections:**

   - About: Company description
   - Mission Statement: Company values
   - Spotlight: Recent achievements
   - Interested in Working: Why join us

4. **⚠️ CRITICAL: Create Owner User Account**

   **If employer doesn't have WordPress account:**

   - Go to **Users → Add New**
   - Create account with `employer_team` role
   - Note the username and password
   - Link this user to employer profile:
     - Edit the employer post
     - In "Owner" field, select the user
     - Save the employer

   **Why This Matters:**

   - Owner can manage jobs
   - Owner can view applications
   - Owner can edit company profile

5. **Profile Completeness**

   - Aim for 70%+ completion
   - This unlocks public profile visibility
   - Shows professional company presence

6. **Publish**
   - Click "Publish" to create employer

#### Post-Creation Steps

1. Send login credentials to employer
2. Direct them to employer dashboard
3. Guide them through profile completion
4. Show them how to post jobs

### Method 2: Frontend Registration

#### User Self-Registration Flow

1. **User Visits Register Employer Page**

   - From login page → "Register as Employer"
   - Or direct link to registration page

2. **Fill Registration Form**

   - Personal information (name, email)
   - Choose username and password
   - Company name
   - Accept terms & conditions

3. **Account Created**

   - WordPress user account: `employer_team` role
   - Employer CPT profile created
   - User linked as owner
   - Welcome email sent

4. **First Login**
   - Redirected to employer dashboard
   - Prompted to complete profile
   - Can immediately post jobs

#### Registration Email

User receives:

- Login credentials
- Dashboard link
- Getting started guide
- Support contact

---

## Managing Applicants

### Method 1: Backend Creation

#### When to Use

- Manual applicant import
- Admin creating test accounts
- Converting guest applications

#### Step-by-Step Process

1. **Create WordPress User First**

   - Go to **Users → Add New**
   - Role: `applicant`
   - Fill email, username, password
   - Click "Add New User"

2. **Create Applicant Profile**

   - Go to **Job Listings → Applicants → Add New**
   - **Title**: Applicant's full name
   - **Featured Image**: Profile photo (optional)

3. **Personal Information Meta Box**

   - Link to WordPress user (select from dropdown)
   - Email (should match user email)
   - Phone number
   - Location

4. **Professional Information**

   - **Current Position**: Job title
   - **Years of Experience**: Select range
   - **Skills**: Comma-separated list
   - **LinkedIn URL**: Profile link
   - **Portfolio URL**: Work samples

5. **Education** (Repeater Field)

   - Click "Add Education"
   - Fill: Degree, Field, Institution, Year
   - Add multiple entries as needed

6. **Work Experience** (Repeater Field)

   - Click "Add Experience"
   - Fill: Title, Company, Duration, Description
   - Add multiple positions

7. **Certifications** (Repeater Field)

   - Click "Add Certification"
   - Fill: Name, Issuer, Date
   - Add multiple certifications

8. **Additional Sections**

   - Professional Summary: Career overview
   - Career Objectives: Goals
   - Why Hire Me: Value proposition
   - Languages: Language proficiency

9. **Resume Upload**

   - Upload PDF/DOC resume
   - Stored securely in uploads directory

10. **Publish**
    - Click "Publish" to create profile

### Method 2: Frontend Registration

#### Applicant Self-Registration

1. **Visit Register Applicant Page**

   - From login page → "Register as Applicant"
   - Or direct link to registration

2. **Fill Registration Form**

   - Full name
   - Email address
   - Username
   - Password
   - Accept terms

3. **Account Created**

   - WordPress user: `applicant` role
   - Applicant profile created
   - Welcome email sent
   - Redirected to dashboard

4. **Complete Profile**
   - Prompted to add details
   - Can apply for jobs immediately
   - Profile strength indicator shows completion

### Guest Application Conversion

#### Automatic Flow

1. Guest applies for job without account
2. Guest data stored in application
3. Later, guest registers with same email
4. System automatically:
   - Links applications to new account
   - Creates applicant profile
   - Sends notification email
   - Converts guest data to user data

---

## Frontend User Flows

### Applicant Journey

#### 1. Discovery

- Visit Jobs Listing page
- Use search and filters
- Browse job categories
- View job details

#### 2. Application

**Option A: Logged-In Applicant**

- Click "Quick Apply Now"
- Pre-filled with profile data
- Upload additional documents
- Submit application

**Option B: Guest Application**

- Fill complete application form
- Provide contact information
- Upload resume
- Create account later (optional)

#### 3. Dashboard Access

- View all applications
- Track application status
- Update profile
- Manage saved jobs (bookmarks)

### Employer Journey

#### 1. Registration

- Register employer account
- Complete company profile
- Profile strength indicator guides completion

#### 2. Post Jobs

- Access employer dashboard
- Click "Post a Job"
- Fill job details
- Publish immediately or save as draft

#### 3. Manage Applications

- View applicants for each job
- Review resumes and profiles
- Update application status
- Contact candidates

#### 4. Team Management

- Add team members
- Grant job management access
- Remove team members

---

## Detailed Settings Guide

### General Tab

#### Search & Filter Settings

```
✅ Enable Category Filter
✅ Enable Job Type Filter
✅ Enable Location Filter
✅ Enable Employer Filter
✅ Enable Salary Filter
✅ Enable Date Posted Filter
✅ Enable Sort Options
```

**Filter Position Options:**

- Left Sidebar (default): Filters on left, jobs on right
- Right Sidebar: Jobs on left, filters on right
- Top Bar: Filters above job listings (horizontal layout)

**Filter Order:**

- Drag and drop to reorder filters
- Affects display order on frontend

#### Job Listing Columns

- 1 Column: Full-width job cards (recommended for detailed view)
- 2 Columns: Grid layout (good for tablets/desktop)
- 3 Columns: Compact grid (best for many jobs)

### Appearance Tab

#### Primary Brand Color

- Default: #FF8200 (orange)
- Used for:
  - All primary buttons
  - Active states
  - Links and accents
  - Icons and highlights

#### Container Width

- 1000px: Narrow (better for reading)
- 1200px: Standard (recommended)
- 1400px: Wide (more content visible)
- 100%: Full width (edge-to-edge)

### Email Tab

#### From Information

- **From Name**: "Your Site Name Jobs" or "Careers Team"
- **From Email**: noreply@yoursite.com or careers@yoursite.com
  - Must be valid email from your domain
  - Configure SPF/DKIM for deliverability

#### Email Templates

- Customizable subjects and bodies
- Variables available: {applicant_name}, {job_title}, {company_name}
- HTML email support (future enhancement)

---

## Add New Job - Detailed Walkthrough

### Backend Method (Admin)

#### Section 1: Title & Content

```
Job Title: [Enter descriptive title]
Example: "Senior Full Stack Developer - Remote"

Content Editor: [Optional additional details]
- Use for extra information not in structured fields
- Displays in "Additional Information" section
```

#### Section 2: Employer Selection ⚠️

```
Employer: [Select from dropdown]
CRITICAL: If no employer selected:
- Job won't show company logo
- No company information displayed
- Professional appearance affected
```

#### Section 3: Location Settings

```
Job Location: [Click field, start typing]
- Autocomplete suggests locations
- Select from dropdown for accuracy
- Coordinates stored for radius searches

✅ Remote Position: Check if fully remote
- Displays "Remote" badge
- Still searchable by location
```

#### Section 4: Dates

```
Opening Date: [When job opened]
- Defaults to today
- Used for "Recently Posted" sorting

Closing Date: [Application deadline]
- Required for expiry countdown
- Jobs auto-draft after this date
- Shows urgency to applicants
```

#### Section 5: Salary

```
Salary Mode:
○ Range: For salary brackets
  Example: "$60,000 - $80,000 per year"

○ Numeric: For exact salary
  Example: 75000 (displays as "$75,000")

Benefits:
- Numeric enables salary filtering
- Range shows flexibility
```

#### Section 6: Application Method

```
Application Type:
○ Internal: Use CareerNest forms
  - Full applicant tracking
  - Resume storage
  - Status management

○ External: Redirect elsewhere
  Options:
  - URL: External application site
  - Email: Direct email application
```

#### Section 7: Job Content

```
Job Overview: [Brief 2-3 sentence summary]
- First thing applicants see
- Keep concise and appealing

Who We Are: [Company background]
- Company culture
- Team description
- Why join this company

What We Offer: [Benefits & perks]
- Use bullet points
- Highlight competitive advantages
- Include non-salary benefits

Key Responsibilities: [Main duties]
- Clear, specific tasks
- Day-to-day activities
- Expected outcomes

How to Apply: [Application instructions]
- Any special requirements
- Timeline expectations
- Next steps in process
```

#### Section 8: Categories & Types

```
Job Categories: ✅ [Select one or more]
- Technology
- Healthcare
- etc.

Job Type: ✅ [Select primary type]
- Full-Time
- Part-Time
- Contract
- etc.
```

#### Section 9: Publish

```
Status Options:
- Published: Live immediately
- Draft: Save for later
- Pending Review: Awaiting approval

Position Filled: ✅ Check when hired
- Marks job as filled
- Prevents new applications
- Keeps job visible for reference
```

### Frontend Method (Employer Dashboard)

#### Access Point

```
Employer Dashboard → "Post a Job" button
OR
Employer Dashboard → Manage Jobs → Add New Job
```

#### Form Fields (Same as Backend)

1. Job title
2. Job location (with map picker)
3. Remote position checkbox
4. Opening date
5. Closing date
6. Salary information
7. Application method
8. Job content sections
9. Categories and types

#### Key Differences

- Employer auto-selected (logged-in user's company)
- Better UX with real-time validation
- Mobile-friendly interface
- Can't change employer (security)

#### After Submission

- Redirects to "Manage Jobs"
- Job appears in employer's job list
- Can edit/delete from frontend
- Can view applications

---

## Add New Employer - Detailed Walkthrough

### Backend Method

#### CRITICAL: Two-Step Process

**Step 1: Create WordPress User**

```
Users → Add New

Username: [company-admin]
Email: [owner@company.com]
Role: Employer Team ⚠️ (NOT employer_team, use the proper role)
Password: [Generate or set]

✅ Click "Add New User"
```

**Step 2: Create Employer Profile**

```
Job Listings → Employers → Add New

Title: [Company Name]
Example: "Tech Innovations Inc."

Featured Image: [Upload company logo]
- Recommended size: 500x500px minimum
- Square format works best
- PNG with transparency ideal
```

#### Company Information

```
Contact Email: [public@company.com]
- Shown on public profile
- Used for inquiries

Phone: [(555) 123-4567]
Website: [https://company.com]
Location: [Company HQ address]
- Use autocomplete for accuracy
```

#### Company Details

```
Tagline: [One-line description]
Example: "Innovative Tech Solutions Since 2010"

Industry: [Primary industry]
Example: "Information Technology"

Company Size:
- 1-10 employees
- 11-50 employees
- 51-200 employees
- etc.

Founded: [Year established]
Specialities: [Core competencies]
Example: "Web Development, Mobile Apps, Cloud Solutions"
```

#### Content Sections

```
About: [Full company description]
- Company history
- What you do
- Company culture
- Team size and structure

Mission Statement: [Company mission]
- Core values
- Vision
- Purpose

Company Spotlight: [Achievements]
- Recent wins
- Awards
- Notable projects

Interested in Working for Us:
- Employee benefits
- Growth opportunities
- Work environment
```

#### Link Owner Account ⚠️

```
Owner User: [Select user created in Step 1]

CRITICAL: This links:
- User account to company profile
- Grants dashboard access
- Enables job management
- Allows application viewing

If not linked:
- User can't access employer dashboard
- Can't post jobs
- Can't manage company
```

#### Profile Completeness

```
Aim for 70%+ completion:
✅ Logo uploaded
✅ All contact info filled
✅ About section complete
✅ Industry and size set
✅ Website URL added

Benefits of 70%+:
- Public profile accessible
- Professional appearance
- Better candidate trust
- SEO benefits
```

### Frontend Method

#### User Self-Registration

**Page**: Register Employer

**Form Fields:**

1. Personal Information

   - Full Name
   - Email Address
   - Phone Number

2. Account Details

   - Username (auto-suggested)
   - Password
   - Confirm Password

3. Company Information

   - Company Name
   - Company Website (optional)
   - Company Location (optional)

4. Terms & Conditions
   - Must accept to proceed

**Submit → Account Created**

**What Happens:**

1. WordPress user created (`employer_team` role)
2. Employer CPT profile created
3. User linked as owner automatically
4. Welcome email sent
5. Password set (or reset link sent)
6. Redirected to employer dashboard

**First Time Dashboard:**

- Profile completion prompt
- "Post Your First Job" guide
- Profile strength indicator
- Quick start tips

---

## Add New Applicant - Detailed Walkthrough

### Backend Method

#### When to Use

- Converting guest applications to accounts
- Manual data import
- Creating test accounts

#### Two-Step Process

**Step 1: Create User Account**

```
Users → Add New

Username: [applicant-name]
Email: [applicant@email.com]
Role: Applicant ⚠️
Password: [Set or generate]

✅ Add New User
```

**Step 2: Create Applicant Profile**

```
Job Listings → Applicants → Add New

Title: [Full Name]
Example: "John Smith"

Featured Image: [Profile photo]
- Optional
- Recommended for professional appearance
```

#### Personal Information

```
Linked User: [Select user from Step 1]
Email: [Must match user email]
Phone: [Contact number]
Location: [City, State]
```

#### Professional Details

```
Current Position: [Job title]
Example: "Senior Software Engineer"

Years of Experience:
- 0-1 years
- 2-5 years
- 6-10 years
- 10+ years

Skills: [Comma-separated]
Example: "JavaScript, React, Node.js, AWS"

LinkedIn: [Profile URL]
Portfolio: [Website URL]
```

#### Education (Repeater)

```
Click "Add Education"

For each entry:
- Degree: [e.g., "Bachelor of Science"]
- Field of Study: [e.g., "Computer Science"]
- Institution: [e.g., "MIT"]
- Graduation Year: [e.g., "2020"]

Add multiple degrees as needed
```

#### Work Experience (Repeater)

```
Click "Add Experience"

For each position:
- Job Title: [Position name]
- Company: [Employer name]
- Start Date: [MM/YYYY]
- End Date: [MM/YYYY or "Present"]
- Description: [Key responsibilities and achievements]

Tips:
- Use bullet points in description
- Quantify achievements
- Include technologies used
```

#### Certifications (Repeater)

```
Click "Add Certification"

For each cert:
- Certification Name
- Issuing Organization
- Issue Date
- Expiry Date (if applicable)
```

#### Content Sections

```
Professional Summary: [Career overview]
- 2-3 paragraphs
- Key strengths
- Career highlights

Career Objectives: [Goals]
- What you're looking for
- Career direction
- Ideal role

Why Hire Me: [Value proposition]
- Unique skills
- What sets you apart
- How you add value

Languages: [Language skills]
- English (Native)
- Spanish (Fluent)
- etc.
```

#### Resume Upload

```
Upload resume file:
- Supported: PDF, DOC, DOCX
- Max size: 5MB (configurable)
- Stored in: /uploads/resumes/
- Accessible to: Employers viewing applications
```

### Frontend Method

#### Applicant Self-Registration

**Registration Form:**

1. Personal info (name, email, phone)
2. Account setup (username, password)
3. Basic professional info
4. Terms acceptance

**Post-Registration:**

- Redirected to applicant dashboard
- Profile completion wizard (optional)
- Can apply for jobs immediately
- Profile strength indicator

---

## Additional Features

### Job Bookmarking (Applicants Only)

#### How It Works

- Applicants can bookmark jobs
- Saves to user meta
- Accessible from dashboard
- Quick access to saved jobs

#### Usage

1. Browse job listings
2. Click bookmark icon (top right of job card)
3. Icon fills when bookmarked
4. View saved jobs in dashboard

### Guest Applications

#### Flow

1. Guest finds job
2. Clicks apply
3. Fills application form (all fields required)
4. Uploads resume
5. Submits application
6. Receives confirmation
7. **Optional**: Creates account later (applications auto-linked)

### Email Notifications

#### Applicants Receive

- Application confirmation
- Status update notifications
- Interview invitations (manual)

#### Employers Receive

- New application alerts
- Team member requests

### Profile Completeness

#### Employers

- Calculated from 11 weighted fields
- 70% threshold for public profile
- Indicator shows missing fields
- Guides profile improvement

#### Applicants

- Based on filled fields
- No strict threshold
- Helps improve applications
- Shows professionalism

---

## Troubleshooting

### Common Issues

#### "Jobs Page Shows 404"

**Solution:**

1. Go to Settings → Permalinks
2. Click "Save Changes"
3. Flushes rewrite rules
4. Fixes custom post type URLs

#### "Can't Upload Resume"

**Check:**

- File size under limit (default 5MB)
- File type allowed (PDF, DOC, DOCX)
- PHP upload_max_filesize setting
- WordPress media upload working

#### "Employer Can't Access Dashboard"

**Verify:**

1. User has `employer_team` role
2. User is linked as owner in employer profile
3. Employer dashboard page exists and is published
4. User is logged in

#### "Guest Application Not Linked After Registration"

**Confirm:**

- Same email used for both
- Registration completed successfully
- System had time to process (may take a moment)
- Check user meta for `_bookmarked_jobs`

#### "Maps Not Working"

**Check:**

1. API key entered in settings
2. API key has required permissions
3. Billing enabled on Google Cloud (required for Maps API)
4. JavaScript API and Places API enabled
5. Check browser console for errors

---

## Best Practices

### For Admins

#### Job Management

✅ **Do:**

- Set closing dates on all jobs
- Use consistent job titles
- Fill all metadata fields
- Assign proper categories
- Monitor expiring jobs

❌ **Don't:**

- Leave employer field blank
- Use vague job titles
- Skip location information
- Forget to set closing dates

#### Employer Profiles

✅ **Do:**

- Ensure 70%+ completion before making public
- Upload high-quality logos
- Write detailed company descriptions
- Keep contact information current
- Link owner accounts properly

❌ **Don't:**

- Publish incomplete profiles
- Use placeholder content
- Skip verification steps
- Forget to create owner user

#### Data Hygiene

- Regular cleanup of expired jobs
- Archive filled positions
- Remove duplicate profiles
- Update outdated information
- Monitor application volume

### For Employers

#### Job Posting

- Write clear, specific job titles
- Include salary information (increases applications)
- Set realistic closing dates
- Use bullet points for readability
- Proofread before publishing

#### Application Management

- Respond promptly to applications
- Update statuses regularly
- Keep candidates informed
- Use professional communication
- Track hiring metrics

### For Security

#### Passwords

- Require strong passwords for all users
- Enable two-factor authentication (via plugin)
- Regular password updates
- Don't share admin credentials

#### Data Protection

- Regular backups
- SSL certificate required
- Secure file uploads
- GDPR compliance
- Privacy policy updated

---

## Support & Maintenance

### Regular Tasks

#### Weekly

- [ ] Review new applications
- [ ] Update filled positions
- [ ] Check expiring jobs
- [ ] Monitor email delivery

#### Monthly

- [ ] Archive old jobs
- [ ] Review analytics
- [ ] Update categories if needed
- [ ] Clean spam applications

#### Quarterly

- [ ] Full system backup
- [ ] Security audit
- [ ] Performance review
- [ ] Update WordPress and plugins
- [ ] Review user accounts

#### Annually

- [ ] Full content audit
- [ ] SEO optimization
- [ ] Feature assessment
- [ ] Performance benchmarking

---

## Quick Reference

### User Roles

| Role                | Capabilities                                       | Dashboard Access    |
| ------------------- | -------------------------------------------------- | ------------------- |
| **AES Admin**       | Full plugin control, all data access               | WordPress admin     |
| **Employer Team**   | Manage jobs, view applications, team management    | Employer Dashboard  |
| **Applicant**       | Apply for jobs, manage profile, track applications | Applicant Dashboard |
| **WordPress Admin** | Full site control (not CareerNest specific)        | WordPress admin     |

### Page Purposes

| Page                | Purpose                  | URL Slug                |
| ------------------- | ------------------------ | ----------------------- |
| Jobs Listing        | Browse and search jobs   | `/jobs/`                |
| Login/Register      | User authentication      | `/login/`               |
| Forgot Password     | Password reset           | `/forgot-password/`     |
| Employer Dashboard  | Employer management hub  | `/employer-dashboard/`  |
| Applicant Dashboard | Applicant management hub | `/applicant-dashboard/` |
| Apply for Job       | Application form         | `/apply-job/`           |
| Register Employer   | Employer signup          | `/register-employer/`   |
| Register Applicant  | Applicant signup         | `/register-applicant/`  |
| Register Employee   | Team member signup       | `/register-employee/`   |

### Key Concepts

**Profile Completeness (Employers)**

- Calculation based on 11 weighted fields
- 70% threshold for public visibility
- Fields: Logo, contact info, about, mission, industry, size, location, etc.
- Shows in dashboard with missing fields list

**Application Status Workflow**

1. Pending (newly submitted)
2. Under Review (employer viewing)
3. Interview Scheduled
4. Rejected
5. Accepted

**Job Expiry System**

- Automatic draft conversion after closing date
- Daily cron job checks expiring jobs
- Visible countdown on job cards
- Color-coded urgency (normal, warning, urgent, expired)

---

## Support & Contact

### Getting Help

**Documentation:**

- This guide (CLIENT_SETUP_GUIDE.md)
- Technical documentation (PLUGIN_OVERVIEW.md)
- Inline help text in admin panels

**Common Resources:**

- WordPress Codex: https://codex.wordpress.org/
- Google Maps API: https://developers.google.com/maps
- PHP Documentation: https://www.php.net/docs.php

### Contact Information

**Plugin Developer:**

- Development Team: [Your Contact Info]
- Support Email: [Your Support Email]
- Documentation: [Your Docs URL]

**Emergency Contacts:**

- Critical Bugs: [Emergency Contact]
- Security Issues: [Security Contact]
- Billing/License: [Billing Contact]

---

## Appendix

### A. Shortcodes

**Available Shortcodes:**

`[careernest_login]` - Display login form

- Can be used on any page
- Redirects after successful login

`[careernest_job_search]` - Job search widget

- Compact search form
- Links to jobs page
- Customizable via attributes

`[careernest_job_categories]` - Job categories grid

- Visual category browser
- Shows job counts
- Links to filtered listings

`[careernest_jobs_by_category]` - Jobs organized by category

- Tabbed interface
- Category-based filtering
- Customizable display

`[careernest_employer_carousel]` - Featured employers slider

- Rotating company logos
- Links to employer profiles
- Customizable settings

### B. Template Override

**To customize templates in your theme:**

1. Create folder: `/your-theme/careernest/`
2. Copy template from plugin
3. Modify as needed
4. Plugin uses your version automatically

**Overridable Templates:**

- All files in `/templates/` directory
- Single job, employer, applicant templates
- Dashboard templates
- Registration forms

### C. Hooks & Filters

**For Developers:**

The plugin provides numerous WordPress hooks for customization:

**Actions:**

- `careernest_before_job_content`
- `careernest_after_job_content`
- `careernest_application_submitted`
- `careernest_application_status_changed`

**Filters:**

- `careernest_job_meta_fields`
- `careernest_employer_completeness_fields`
- `careernest_email_content`
- `careernest_search_query_args`

See developer documentation for complete list.

### D. Database Tables

**Custom Post Types:**

- `job_listing` - Job posts
- `employer` - Company profiles
- `applicant` - Applicant profiles
- `job_application` - Applications

**Taxonomies:**

- `job_category` - Job categories (hierarchical)
- `job_type` - Job types (flat)

**Options:**

- `careernest_options` - Plugin settings
- `careernest_pages` - Page IDs
- `careernest_appearance` - Theme settings

**User Meta:**

- `_employer_id` - Links user to employer
- `_applicant_id` - Links user to applicant
- `_bookmarked_jobs` - Saved jobs array

---

## Version History

### Current Version: 1.0.0

**Features:**

- Complete job posting system
- Employer and applicant management
- Guest application system
- Application tracking
- Profile management
- Team collaboration
- Search and filtering
- Mobile responsive design
- Google Maps integration
- Email notifications

**Known Limitations:**

- LinkedIn sharing requires public URL (not localhost)
- Maps require API key with billing enabled
- Email delivery depends on server configuration

---

## Final Checklist

### Before Launching to Production

- [ ] All test data removed
- [ ] Real company profiles added
- [ ] Actual jobs posted
- [ ] Email sending tested
- [ ] All forms tested
- [ ] Mobile responsiveness verified
- [ ] Browser compatibility checked (Chrome, Firefox, Safari, Edge)
- [ ] SSL certificate installed
- [ ] Google Maps API configured
- [ ] Backup system in place
- [ ] Analytics tracking set up
- [ ] SEO plugin configured
- [ ] Terms & conditions updated
- [ ] Privacy policy includes job data handling
- [ ] GDPR compliance verified (if applicable)
- [ ] Social sharing tested
- [ ] Application process end-to-end tested
- [ ] Admin trained on system
- [ ] User documentation provided
- [ ] Support contacts established
- [ ] Launch announcement prepared

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-27  
**Prepared For**: CareerNest Plugin Clients  
**Support**: contact@yoursite.com

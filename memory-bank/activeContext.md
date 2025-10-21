# CareerNest - Active Context

## Current Work Focus

### Milestone Status: M6 - Frontend Job Listing System

**Current Priority:** Implementing frontend job listing and single job display with filters and pagination

**Progress:** In development phase

- Template routing system completed and tested
- Guest application system fully functional
- Applicant dashboard comprehensive and production-ready
- Single job templates created but need content implementation

## Recent Changes (Last Completed)

### ✅ M5 Completion - Template Routing & Dashboards (August 2025)

Major achievements in the most recent development cycle:

#### Template Routing System Implementation

- **File Modified**: `includes/class-plugin.php`
- **Feature**: Complete template loader with `template_include` filter
- **Benefit**: All CareerNest pages and CPTs load correct plugin templates
- **Status**: Production ready

#### Guest Application System

- **File Created**: Enhanced `templates/template-apply-job.php`
- **Features Implemented**:
  - Guest users can apply without registration
  - Automatic WordPress user account creation
  - Email notifications with password reset links
  - Resume file upload with validation (PDF, DOC, DOCX)
  - Application linking to newly created user accounts via `user_register` hook
- **Status**: Fully functional and tested

#### Comprehensive Applicant Dashboard

- **Files**: `templates/template-applicant-dashboard.php`, `assets/css/applicant-dashboard.css`, `assets/js/applicant-dashboard.js`
- **Major Features Implemented**:
  - **Statistics Cards**: Application counts with status breakdown
  - **In-Place Editing**: Toggle between view and edit modes
  - **Profile Management**: Complete profile with structured data
  - **Dynamic Repeater Fields**: Unlimited entries for education, experience, certifications, links
  - **Smart Form Logic**: Current job checkbox disables end date
  - **Public Profile Access**: View profile button opens in new tab
  - **Responsive Design**: Mobile-first CSS with full responsive support

#### Profile Data Structure Enhancement

- **Complex Arrays**: Education, work experience, licenses, links stored as post meta arrays
- **Sanitization**: Comprehensive form processing with proper data sanitization
- **Validation**: Client and server-side validation with user feedback
- **Data Display**: Professional formatting with fallbacks for empty data

#### Asset Management System

- **Conditional Loading**: Page-specific CSS/JS loading in `includes/class-plugin.php`
- **Performance Optimization**: Assets only load when needed
- **Mobile Optimization**: Responsive design with mobile-first approach

## Active Decisions & Considerations

### Current Architecture Decisions

#### Template System

- **Decision**: Using `template_include` filter with theme override support
- **Rationale**: Provides flexibility while maintaining WordPress standards
- **Implementation**: Plugin templates with `/careernest/` theme override directory
- **Status**: Implemented and working

#### Data Storage Strategy

- **Decision**: WordPress post meta with serialized arrays for complex data
- **Rationale**: Native WordPress approach, no external dependencies
- **Examples**: Education, experience, licenses stored as array structures
- **Benefits**: Integrates with WordPress ecosystem, searchable, extensible

#### Role-Based Access Control

- **Decision**: Three-tier system (AES Admin, Employer Team, Applicant)
- **Current Implementation**: Basic role creation and capability assignment
- **Status**: Implemented in M3, working as designed

### Pending Decisions

#### Job Listing Display Strategy

- **Question**: How to handle job filtering and search functionality?
- **Options**:
  1. WordPress native search with meta queries
  2. Custom AJAX-based filtering system
  3. Hybrid approach with progressive enhancement
- **Recommendation**: Start with WordPress native, enhance with AJAX later

#### Pagination Approach

- **Question**: Standard WordPress pagination vs. AJAX infinite scroll?
- **Current Lean**: Standard WordPress pagination for SEO benefits
- **Consideration**: May add AJAX enhancement later

## Next Steps (Immediate Focus)

### M6: Job Listing Implementation

#### 1. Job Listing Template Enhancement

**File**: `templates/template-jobs.php`
**Tasks**:

- [ ] Add job query with proper filtering
- [ ] Implement search functionality
- [ ] Add category/type filtering
- [ ] Create pagination
- [ ] Add sorting options (date, relevance)
- [ ] Handle empty states

**Priority**: High - Core functionality

#### 2. Single Job Display Enhancement

**File**: `templates/single-job_listing.php`
**Tasks**:

- [ ] Display all job meta fields properly
- [ ] Add application button/link
- [ ] Show company information
- [ ] Add social sharing
- [ ] Related jobs section

**Priority**: High - User experience

#### 3. Job Application Integration

**Consideration**: Link single job view to existing apply-job template
**Tasks**:

- [ ] Pass job ID to application form
- [ ] Pre-populate job information
- [ ] Ensure guest application flow works
- [ ] Test user application flow

**Priority**: High - Critical functionality

### M7: Registration Flow (Next Major Milestone)

**Files to Create/Enhance**:

- `templates/template-register-employer.php`
- `templates/template-register-applicant.php`

**Key Requirements**:

- WordPress user creation with role assignment
- CPT profile creation (employer/applicant)
- Email notifications
- Login redirection to appropriate dashboards

## Technical Debt & Improvement Areas

### Current System Strengths

- **Security**: Comprehensive nonce verification and capability checks
- **Performance**: Conditional asset loading working well
- **User Experience**: Applicant dashboard provides professional experience
- **Data Integrity**: Proper sanitization and validation throughout

### Areas for Enhancement

#### Code Organization

- **Current**: Good namespace structure and separation of concerns
- **Enhancement**: Could benefit from more service-oriented classes
- **Priority**: Low - current structure is functional

#### Error Handling

- **Current**: Basic WordPress error handling
- **Enhancement**: More user-friendly error messages and recovery flows
- **Priority**: Medium - affects user experience

#### Testing Coverage

- **Current**: Manual testing only
- **Enhancement**: Automated testing framework
- **Priority**: Medium - would improve reliability

## Integration Points

### Current Integrations Working Well

- **Google Maps API**: Location fields with autocomplete and map picking
- **WordPress Media API**: Resume file uploads
- **WordPress User System**: Seamless integration with WP users and roles
- **WordPress Template System**: Theme override support

### Future Integration Considerations

- **Email Templates**: Currently using basic wp_mail(), could enhance with templates
- **Notification System**: Could add more comprehensive notification system
- **Search Enhancement**: Consider integration with search plugins
- **Performance Monitoring**: Could add performance tracking

## User Feedback & Observations

### Positive Aspects

- Guest application system reduces friction significantly
- Applicant dashboard provides comprehensive profile management
- Mobile-responsive design works well across devices
- Professional UI matches commercial job platforms

### Potential Improvements

- Job search and filtering will be critical for user adoption
- Employer dashboard needs equal attention to applicant dashboard
- Admin tools need refinement for managing large numbers of applications
- Email templates could be more professional and customizable

## Development Environment Notes

### Current Setup Working Well

- WordPress 6.0+ with PHP 8.0+ providing modern features
- Local development environment with debug mode
- Version control with Git
- File structure is clean and navigable

### Tools Being Used

- VS Code for development
- WordPress coding standards (PHPCS)
- Manual testing across browsers
- Chrome DevTools for responsive testing

### Performance Observations

- Page load times are acceptable (<3 seconds)
- Asset loading is optimized
- Database queries are efficient
- Mobile performance is good

## Security Status

### Current Security Measures

- All forms have nonce verification
- Capability checks on all actions
- Data sanitization comprehensive
- File upload validation working
- Role-based access control implemented

### Security Monitoring

- No current security issues identified
- Regular WordPress updates applied
- Code follows WordPress security best practices
- File permissions properly configured

This active context reflects the current state of CareerNest development as we transition from the comprehensive M5 completion into the M6 job listing implementation phase.

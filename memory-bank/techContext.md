# CareerNest - Technical Context

## Technology Stack

### Core Platform

- **WordPress**: 6.0+ (minimum requirement)
- **PHP**: 8.0+ (minimum requirement)
- **MySQL**: 5.6+ (via WordPress requirements)
- **Web Standards**: HTML5, CSS3, ES6+ JavaScript

### WordPress APIs Used

- **Post Types API**: Custom post type registration and management
- **Taxonomy API**: Job categories and types classification
- **Meta API**: Structured data storage and retrieval
- **Settings API**: Plugin configuration management
- **Roles & Capabilities API**: User permission system
- **Template Hierarchy**: Theme integration and template loading
- **Hook System**: Actions and filters for extensibility
- **Media API**: File upload and attachment management

### Frontend Technologies

#### CSS Architecture

- **Modern CSS**: CSS Grid, Flexbox, Custom Properties
- **Mobile-First**: Responsive design with breakpoints
- **Component-Based**: Modular CSS with BEM-like naming
- **Performance Optimized**: Conditional loading, minimal dependencies

#### JavaScript Implementation

- **Vanilla JavaScript**: No external libraries or frameworks
- **ES6+ Features**: Arrow functions, const/let, template literals, destructuring
- **DOM Manipulation**: Modern DOM APIs and event handling
- **Async Operations**: Modern async/await patterns where needed

#### Asset Management

- **Conditional Loading**: Page-specific CSS/JS enqueuing
- **Version Control**: Asset versioning using plugin version
- **Dependency Management**: Proper WordPress script/style dependencies
- **Performance**: Minimal HTTP requests, optimized file sizes

## Development Environment

### Required Tools

- **PHP Development Environment**: XAMPP, WAMP, Local, or similar
- **WordPress Installation**: Local WordPress development site
- **Code Editor**: VS Code, PHPStorm, or WordPress-friendly IDE
- **Version Control**: Git for source code management
- **Command Line**: Terminal access for development tasks

### Recommended Setup

- **Local Development**: Local by Flywheel or similar WordPress-specific environment
- **Database Management**: phpMyAdmin or Adminer for database inspection
- **Code Standards**: PHP_CodeSniffer with WordPress Coding Standards
- **Debugging**: WordPress debug mode enabled for development

### File Structure

```
career-nest/
├── careernest.php              # Main plugin file
├── readme.txt                  # WordPress plugin readme
├── assets/                     # Frontend assets
│   ├── css/                   # Stylesheets
│   │   ├── admin.css          # Admin interface styles
│   │   ├── applicant-dashboard.css  # Applicant dashboard styles
│   │   └── employer-dashboard.css   # Employer dashboard styles
│   └── js/                    # JavaScript files
│       ├── admin.js           # Admin interface scripts
│       ├── applicant-dashboard.js  # Dashboard interactions
│       ├── employer-dashboard.js   # Employer dashboard scripts
│       └── maps.js           # Google Maps integration
├── includes/                   # PHP classes and logic
│   ├── class-*.php           # Core plugin classes
│   ├── Admin/                # Admin interface classes
│   ├── Data/                 # Data layer (CPTs, roles, etc.)
│   └── Security/             # Security and capabilities
├── templates/                 # Frontend templates
│   ├── template-*.php        # Page templates
│   └── single-*.php          # Single post templates
├── docs/                     # Documentation
└── memory-bank/              # Project memory and context
```

## Dependencies and Constraints

### WordPress Dependencies

- **WordPress Core**: Minimum 6.0 for modern APIs and security
- **PHP Version**: 8.0+ for modern language features and performance
- **Database**: MySQL 5.6+ or MariaDB equivalent
- **Server Requirements**: Standard WordPress hosting requirements

### External Services

- **Google Maps API**: Optional integration for location features
  - Requires API key configuration
  - Fallback functionality when not configured
  - Usage tracking and quota management

### Plugin Dependencies

- **Zero External Plugins**: Completely standalone, no plugin dependencies
- **Theme Independence**: Works with any properly coded WordPress theme
- **Core API Only**: Uses exclusively WordPress core functionality

## Performance Specifications

### Load Time Targets

- **Initial Page Load**: <3 seconds on average hosting
- **Dashboard Interactions**: <1 second response time
- **Asset Loading**: Conditional loading based on page context
- **Database Queries**: Optimized with proper indexing and caching

### Scalability Considerations

- **Query Optimization**: Efficient database queries with limits and indexing
- **Asset Management**: Conditional loading prevents unnecessary HTTP requests
- **Caching Strategy**: Leverages WordPress object cache where available
- **Memory Usage**: Optimized class loading and instantiation

### Browser Compatibility

- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Mobile Browsers**: iOS Safari 14+, Chrome Mobile 90+
- **Progressive Enhancement**: Graceful degradation for older browsers
- **Accessibility**: WCAG 2.1 AA compliance target

## Security Framework

### WordPress Security Standards

- **Nonce Verification**: All forms protected with WordPress nonces
- **Capability Checks**: Every action verified against user permissions
- **Data Sanitization**: All input sanitized using WordPress functions
- **Output Escaping**: All output escaped to prevent XSS attacks
- **SQL Injection Prevention**: Proper use of $wpdb and WP_Query

### File Upload Security

- **Type Validation**: Restricted to allowed file types (PDF, DOC, DOCX)
- **Size Limits**: Enforced file size restrictions
- **Storage Security**: Files stored in WordPress uploads directory
- **Access Control**: Proper file permissions and access restrictions

### Authentication & Authorization

- **Role-Based Access**: Custom roles with granular capabilities
- **Session Management**: WordPress native session handling
- **Password Security**: WordPress password hashing and validation
- **Login Protection**: Integration with WordPress login system

## API Integration Patterns

### Google Maps API

```php
// Conditional API loading
$api_key = get_option('careernest_options')['maps_api_key'] ?? '';
if (!empty($api_key) && $this->is_maps_page()) {
    wp_enqueue_script('google-maps-api',
        "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places",
        [], null, true);
}
```

**Integration Features:**

- Place autocomplete for address fields
- Interactive map picking for job locations
- Reverse geocoding for coordinates
- Fallback when API key not configured

### WordPress REST API

- **Current State**: REST API disabled for CareerNest CPTs
- **Future Consideration**: Can be enabled for mobile app integration
- **Security**: Would require proper authentication and capability checks
- **Endpoints**: Custom endpoints for job search and application submission

## Database Schema

### Custom Post Types

```sql
-- WordPress posts table stores CPT data
wp_posts.post_type IN ('job_listing', 'employer', 'applicant', 'job_application')

-- Meta data stored in postmeta
wp_postmeta.meta_key LIKE '_careernest_%'
```

### Meta Field Patterns

- **Simple Fields**: Single value meta fields (text, numbers, booleans)
- **Complex Fields**: Serialized arrays for repeatable data
- **Structured Data**: JSON-like arrays for education, experience, etc.
- **Relationships**: Post IDs for linking between CPTs

### Indexing Strategy

```sql
-- Important indexes for performance
INDEX (post_type, post_status)
INDEX (meta_key, meta_value) -- for meta queries
INDEX (post_date) -- for chronological ordering
```

## Coding Standards

### PHP Standards

- **WordPress Coding Standards**: PHPCS with WordPress ruleset
- **PSR Compatibility**: PSR-4 autoloading concepts (manual includes)
- **Documentation**: PHPDoc blocks for all functions and methods
- **Type Declarations**: PHP 8.0+ type hints where applicable

```php
/**
 * Example method with proper documentation
 *
 * @param int    $user_id The WordPress user ID
 * @param array  $data    Sanitized form data
 * @return bool|WP_Error  True on success, WP_Error on failure
 */
public function update_applicant_profile(int $user_id, array $data): bool|WP_Error
{
    // Implementation
}
```

### CSS Standards

- **BEM Methodology**: Block, Element, Modifier naming convention
- **Component-Based**: Modular CSS with reusable components
- **Mobile-First**: Min-width media queries for responsive design
- **CSS Grid/Flexbox**: Modern layout techniques

```css
/* BEM naming pattern */
.cn-dashboard-container {
}
.cn-dashboard-container__header {
}
.cn-dashboard-container__header--highlighted {
}
```

### JavaScript Standards

- **ES6+ Syntax**: Modern JavaScript features
- **Functional Programming**: Preference for pure functions where possible
- **Event Delegation**: Efficient event handling
- **Progressive Enhancement**: Works without JavaScript, enhanced with it

```javascript
// Modern JavaScript patterns
const initializeDashboard = () => {
  const dashboardElement = document.querySelector(".cn-dashboard");
  if (!dashboardElement) return;

  dashboardElement.addEventListener("click", handleDashboardClick);
};
```

## Testing Strategy

### Manual Testing Approach

- **Cross-Browser Testing**: Verification across supported browsers
- **Device Testing**: Mobile and desktop testing
- **User Flow Testing**: Complete user journey verification
- **Edge Case Testing**: Error conditions and boundary testing

### Automated Testing Potential

- **Unit Tests**: PHPUnit for WordPress (future consideration)
- **Integration Tests**: WordPress test suite integration
- **JavaScript Tests**: Jest or similar framework
- **End-to-End Tests**: Puppeteer or similar tool

### Quality Assurance Checklist

- **Security Audit**: Regular security review and testing
- **Performance Testing**: Load time and query optimization verification
- **Accessibility Testing**: Screen reader and keyboard navigation
- **Code Review**: Peer review of all changes

## Deployment Strategy

### Production Requirements

- **WordPress Hosting**: Standard WordPress-compatible hosting
- **PHP Version**: 8.0+ with common extensions
- **Database**: MySQL 5.6+ or MariaDB equivalent
- **File Permissions**: Standard WordPress file permission requirements

### Installation Process

1. Upload plugin files to `/wp-content/plugins/career-nest/`
2. Activate plugin through WordPress admin
3. Plugin creates required pages and sets up data structures
4. Configure Google Maps API key (optional)
5. Set up user roles and permissions as needed

### Update Strategy

- **WordPress Updates**: Regular WordPress core updates
- **Plugin Updates**: Version-controlled plugin updates
- **Database Migration**: Handled automatically on plugin update
- **Backward Compatibility**: Maintained for at least one major version

## Monitoring and Maintenance

### Performance Monitoring

- **Page Load Times**: Regular monitoring of critical pages
- **Database Query Performance**: Slow query log analysis
- **Error Logging**: WordPress debug logging for error tracking
- **User Feedback**: Support ticket and user feedback analysis

### Security Monitoring

- **WordPress Security Updates**: Timely application of security patches
- **Plugin Security**: Regular security audit of custom code
- **File Integrity**: Monitoring for unauthorized file changes
- **Access Logging**: Monitoring for suspicious access patterns

### Backup Strategy

- **Database Backups**: Regular automated database backups
- **File Backups**: Complete WordPress site backups
- **Version Control**: Git repository for code versioning
- **Recovery Testing**: Regular backup restoration testing

This technical context provides the foundation for all development decisions and ensures consistency across the CareerNest platform while maintaining WordPress best practices and modern web standards.

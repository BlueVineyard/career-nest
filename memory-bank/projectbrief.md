# CareerNest Plugin - Project Brief

## Project Overview

**CareerNest** is a standalone WordPress job portal plugin designed to transform any WordPress site into a comprehensive job board platform. The plugin uses exclusively WordPress core APIs without reliance on third-party plugins or frameworks, ensuring maximum compatibility and performance.

## Core Requirements

### Primary Goals

- **Standalone Architecture**: Built entirely with WordPress core APIs (no ACF, no external frameworks)
- **Role-Based Access**: Secure, role-aware workflows for employers, applicants, and administrators
- **Scalable Design**: Efficient queries and architecture prepared for future enhancements
- **Clean Separation**: Clear distinction between admin (CPTs, meta, settings) and frontend (templates, dashboards)

### Non-Goals

- No external plugin dependencies
- No data deletion on deactivation (optional full uninstall via settings)
- No reliance on third-party frameworks or libraries

## Target Specifications

- **WordPress Version**: 6.0+ minimum
- **PHP Version**: 8.0+ minimum
- **Author**: Rohan T George
- **Version**: 1.0.0
- **Text Domain**: careernest

## Project Scope

The plugin implements a complete job portal solution including:

1. **Job Management System**: Full CRUD operations for job listings with advanced meta fields
2. **User Role Management**: Custom roles (AES Admin, Employer Team, Applicant) with granular capabilities
3. **Application System**: Guest and registered user applications with automatic account creation
4. **Dashboard Interfaces**: Comprehensive dashboards for applicants and employers
5. **Template System**: Complete frontend template routing with theme override support
6. **Profile Management**: Advanced profile systems with complex data structures
7. **Asset Management**: Conditional loading and optimization strategies

## Success Criteria

### Technical Standards

- WordPress Coding Standards compliance (PHPCS)
- Secure implementation with nonces, capability checks, and data sanitization
- Performance optimization with efficient queries and conditional asset loading
- Extensible architecture with hooks and filters

### User Experience

- Intuitive dashboard interfaces for all user roles
- Mobile-responsive design across all templates
- Professional UI/UX matching commercial job platforms
- Seamless guest-to-registered user conversion flow

### Administrative Efficiency

- Comprehensive admin interface with custom columns and filtering
- Hierarchical menu structure with role-appropriate access
- Settings management via WordPress Settings API
- Proper activation/deactivation/uninstall procedures

## Development Approach

### Architecture Philosophy

- **Object-Oriented Design**: Namespaced classes grouped by domain responsibility
- **WordPress Best Practices**: Leverages WordPress hooks, filters, and core functionality
- **Security-First**: Every action includes proper authorization and validation
- **Performance-Conscious**: Optimized queries, conditional loading, and caching considerations

### Code Organization

```
CareerNest/
├── Data/           # CPTs, taxonomies, roles
├── Admin/          # Backend interfaces and functionality
├── Security/       # Capabilities and access control
├── Frontend/       # Template routing and user interfaces
└── Settings/       # Configuration management
```

### Quality Assurance

- Manual testing checklists per milestone
- Security auditing for CSRF, XSS, and injection vulnerabilities
- Performance testing for query efficiency and asset loading
- Cross-browser compatibility verification

## Current Status

The project has completed **5 out of 11 major milestones**, representing significant foundational work:

### ✅ Completed Milestones (M1-M5)

- Plugin scaffold with proper activation/deactivation
- Custom Post Types and Taxonomies
- Role and capability management
- Meta boxes with Google Maps integration
- Template routing system with guest applications and applicant dashboard

### 🚧 In Progress

- Frontend job listing and filtering system (M6)

### 📋 Remaining Work

- Registration flows and user management (M7)
- Job application processing and notifications (M8)
- Settings pages and API integrations (M9)
- Ownership restrictions and query filtering (M10)
- Final polishing and security hardening (M11)

## Key Technical Decisions

### Data Storage Strategy

- **WordPress Post Meta**: Used for structured data with array serialization
- **Custom Post Types**: Primary entities (job_listing, employer, applicant, job_application)
- **Taxonomies**: Classification system (job_category, job_type)
- **User Integration**: Seamless linking between WordPress users and CPT profiles

### Template Architecture

- **Template Hierarchy**: Respects WordPress template hierarchy with plugin fallbacks
- **Theme Override Support**: Allows themes to override plugin templates
- **Conditional Asset Loading**: Page-specific CSS/JS loading for performance
- **Role-Based Access**: Template-level security with proper redirects

### Security Implementation

- **Nonce Verification**: All forms protected against CSRF attacks
- **Capability Checks**: Role-based access control throughout
- **Data Sanitization**: Comprehensive input sanitization and output escaping
- **File Upload Security**: Type validation and secure handling

This project brief serves as the foundation for all development decisions and architectural choices within the CareerNest plugin ecosystem.

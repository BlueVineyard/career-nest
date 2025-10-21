# CareerNest - System Patterns

## Architectural Patterns

### Namespace Organization

CareerNest uses a domain-driven namespace structure for clear code organization:

```php
namespace CareerNest;

CareerNest\Data\           // Data layer: CPTs, taxonomies, roles
CareerNest\Admin\          // Admin interface and functionality
CareerNest\Security\       // Security, capabilities, access control
CareerNest\Plugin          // Core plugin orchestration
```

**Key Benefits:**

- Clear separation of concerns
- Prevents naming conflicts
- Easy to locate and maintain code
- Facilitates future refactoring

### Hook-Based Architecture

The plugin follows WordPress hook patterns with carefully managed priorities:

```php
// Core registration at priority 5
add_action('init', function () {
    \CareerNest\Data\CPT::register();
    \CareerNest\Data\Taxonomies::register();
}, 5);

// Template routing at high priority (99)
add_filter('template_include', [$this, 'template_loader'], 99);

// Asset stripping at priority 100 (after other scripts)
add_action('admin_enqueue_scripts', [$this, 'strip_block_assets_on_cpt_edit'], 100);
```

**Pattern Rules:**

- Early registration (priority 5) for CPTs/taxonomies before other plugins
- Late template filtering (priority 99) to override theme templates
- Asset manipulation after other enqueues (priority 100+)
- Security checks run early in request lifecycle

### Class Instantiation Pattern

Classes are instantiated conditionally based on context:

```php
// In main plugin bootstrap
add_action('plugins_loaded', function () {
    if (class_exists('\\CareerNest\\Plugin')) {
        (new \CareerNest\Plugin())->run();
    }

    if (is_admin()) {
        (new \CareerNest\Admin\Admin())->hooks();
    }
    (new \CareerNest\Security\Caps())->hooks();
    \CareerNest\Data\Roles::ensure_caps();
});
```

**Benefits:**

- Performance optimization (admin classes only load in admin)
- Conditional functionality based on context
- Clear initialization flow
- Memory efficiency

## Data Patterns

### CPT Registration Pattern

Consistent CPT registration with standardized options:

```php
// Standard CPT pattern
public static function register(): void {
    register_post_type('job_listing', [
        'labels' => [...],
        'public' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'rewrite' => ['slug' => 'jobs'],
        'show_in_rest' => false,  // Block editor disabled
        'menu_icon' => 'dashicons-portfolio',
    ]);
}
```

**Key Decisions:**

- Block editor disabled for all CareerNest CPTs (classic editor only)
- Public visibility for job_listing, employer, applicant (for public profiles)
- Private visibility for job_application (sensitive data)
- Consistent rewrite slug patterns
- Dashicon integration for admin UI consistency

### Meta Data Architecture

Complex data stored as serialized arrays in post meta:

```php
// Repeater field pattern
'_education' => [
    [
        'institution' => 'University Name',
        'certification' => 'Degree Name',
        'end_date' => '2020-05',
        'complete' => true
    ],
    // ... unlimited entries
]
```

**Storage Strategy:**

- Arrays for complex, repeatable data
- Single values for simple fields
- Consistent key prefixing with underscores (private meta)
- Validation before storage
- Sanitization on retrieval

### Role and Capability System

Three-tier role system with custom capabilities:

```php
// Role hierarchy
'aes_admin' => [
    'manage_careernest',
    'edit_jobs',
    'edit_employers',
    'edit_applicants',
    'edit_job_applications'
]

'employer_team' => [
    'read',
    'edit_own_jobs',
    'view_applications'
]

'applicant' => [
    'read',
    'edit_own_profile',
    'apply_to_jobs'
]
```

**Access Control Pattern:**

- Custom capabilities for granular control
- Ownership-based restrictions via meta caps
- Frontend admin bar hiding for non-admin roles
- Dashboard redirect based on role

## Template Patterns

### Template Loading System

Hierarchical template loading with theme override support:

```php
public function template_loader(string $template): string {
    // 1. Check for single CPT templates
    if (is_singular('job_listing')) {
        $plugin_template = $this->locate_template('single-job_listing.php');
        if ($plugin_template) {
            $template = $plugin_template;
        }
    }

    // 2. Check for page templates by stored ID
    if (is_page()) {
        $page_id = get_queried_object_id();
        $pages = get_option('careernest_pages', []);

        foreach ($page_templates as $page_slug => $template_file) {
            if (isset($pages[$page_slug]) && (int)$pages[$page_slug] === $page_id) {
                $template = $this->locate_template($template_file);
            }
        }
    }

    return $template;
}

private function locate_template(string $template_name) {
    // Theme override check first
    $theme_template = locate_template([
        'careernest/' . $template_name,
        $template_name,
    ]);

    if ($theme_template) {
        return $theme_template;
    }

    // Plugin fallback
    return CAREERNEST_DIR . 'templates/' . $template_name;
}
```

**Template Hierarchy:**

1. Theme override in `/careernest/` subdirectory
2. Theme override in root theme directory
3. Plugin template fallback
4. WordPress default template

### Asset Loading Strategy

Conditional asset loading based on page detection:

```php
public function enqueue_frontend_assets(): void {
    $pages = get_option('careernest_pages', []);
    $applicant_dashboard_id = isset($pages['applicant-dashboard']) ? (int)$pages['applicant-dashboard'] : 0;

    if ($applicant_dashboard_id && is_page($applicant_dashboard_id)) {
        wp_enqueue_style('careernest-applicant-dashboard', ...);
        wp_enqueue_script('careernest-applicant-dashboard', ...);
    }
}
```

**Performance Benefits:**

- Assets only load on relevant pages
- Reduced HTTP requests
- Smaller page payloads
- Better Core Web Vitals scores

## Form Processing Patterns

### Security-First Form Handling

All forms follow consistent security patterns:

```php
public function process_form() {
    // 1. Nonce verification
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'action_name')) {
        wp_die('Security check failed');
    }

    // 2. Capability check
    if (!current_user_can('required_capability')) {
        wp_die('Insufficient permissions');
    }

    // 3. Data sanitization
    $data = [
        'field1' => sanitize_text_field($_POST['field1']),
        'field2' => sanitize_email($_POST['field2']),
        'field3' => esc_url_raw($_POST['field3'])
    ];

    // 4. Validation
    if (empty($data['field1'])) {
        // Handle validation error
    }

    // 5. Data processing
    update_post_meta($post_id, '_meta_key', $data);

    // 6. Redirect to prevent resubmission
    wp_safe_redirect($redirect_url);
    exit;
}
```

### Repeater Field Management

Dynamic repeater fields use consistent JavaScript patterns:

```javascript
// Add repeater item
function addRepeaterItem(container, template) {
  const newItem = template.cloneNode(true);
  const index = container.children.length;

  // Update field names with proper indexing
  newItem.querySelectorAll("[name]").forEach((field) => {
    field.name = field.name.replace("[0]", `[${index}]`);
  });

  container.appendChild(newItem);
  initializeRemoveButtons();
}

// Remove with reindexing
function removeRepeaterItem(item) {
  item.remove();
  reindexRepeaterItems();
}
```

## Admin Interface Patterns

### Hierarchical Menu Structure

Admin menus use section headers for logical grouping:

```php
// Section headers (non-clickable)
add_submenu_page(
    'careernest',
    '',
    '📋 Jobs Section',
    'manage_careernest',
    '',
    null,
    10
);

// Actual menu items
add_submenu_page(
    'careernest',
    'All Jobs',
    '&nbsp;&nbsp;&nbsp;All Jobs',
    'manage_careernest',
    'edit.php?post_type=job_listing',
    null,
    11
);
```

**UX Benefits:**

- Clear visual hierarchy
- Logical grouping of related functions
- Professional appearance
- Easy navigation

### Meta Box Pattern

Consistent meta box implementation across CPTs:

```php
public function add_meta_boxes(): void {
    $screens = ['job_listing', 'employer', 'applicant'];

    foreach ($screens as $screen) {
        add_meta_box(
            'careernest_' . $screen . '_details',
            $screen . ' Details',
            [$this, 'render_' . $screen . '_meta_box'],
            $screen,
            'normal',
            'high'
        );
    }
}

public function render_job_listing_meta_box($post): void {
    wp_nonce_field('careernest_save_job', '_careernest_job_nonce');

    // Render form fields with current values
    $current_value = get_post_meta($post->ID, '_meta_key', true);
    echo '<input type="text" name="meta_key" value="' . esc_attr($current_value) . '">';
}
```

## User Experience Patterns

### Guest-to-User Conversion Flow

Seamless conversion from guest applications to registered users:

```php
// 1. Guest submits application
// 2. Application stored with email
// 3. User account created automatically
// 4. Application linked via user_register hook
add_action('user_register', [$this, 'link_guest_applications_to_user']);

public function link_guest_applications_to_user(int $user_id): void {
    $user = get_user_by('id', $user_id);
    $user_email = $user->user_email;

    // Find applications by email
    $guest_applications = new \WP_Query([
        'post_type' => 'job_application',
        'meta_query' => [
            ['key' => '_applicant_email', 'value' => $user_email, 'compare' => '=']
        ]
    ]);

    // Link applications to user
    foreach ($guest_applications->posts as $application) {
        update_post_meta($application->ID, '_user_id', $user_id);
        update_post_meta($application->ID, '_was_guest_application', true);
    }
}
```

### Frontend Editing Toggle System

In-place editing with clean state management:

```javascript
// Edit mode toggle
function enterEditMode() {
  document.querySelectorAll(".profile-display").forEach((el) => {
    el.style.display = "none";
  });
  document.querySelectorAll(".profile-edit").forEach((el) => {
    el.style.display = "block";
  });
  document.querySelector(".edit-profile-btn").textContent = "Cancel Edit";
}

function exitEditMode() {
  document.querySelectorAll(".profile-display").forEach((el) => {
    el.style.display = "block";
  });
  document.querySelectorAll(".profile-edit").forEach((el) => {
    el.style.display = "none";
  });
  document.querySelector(".edit-profile-btn").textContent = "Edit Profile";
}
```

## Error Handling Patterns

### Graceful Degradation

System designed to handle missing data gracefully:

```php
// Safe array access with defaults
$education = get_post_meta($applicant_id, '_education', true) ?: [];
$education = is_array($education) ? $education : [];

// Display with fallbacks
$institution = !empty($edu['institution']) ? esc_html($edu['institution']) : 'Institution not specified';
```

### Validation with User Feedback

Client and server-side validation with clear messaging:

```php
// Server-side validation
$errors = [];
if (empty($name)) {
    $errors[] = 'Name is required';
}
if (!is_email($email)) {
    $errors[] = 'Valid email is required';
}

if (!empty($errors)) {
    wp_die('Validation failed: ' . implode(', ', $errors));
}
```

## Performance Patterns

### Query Optimization

Efficient database queries with proper indexing:

```php
// Optimized meta queries
$query = new \WP_Query([
    'post_type' => 'job_listing',
    'posts_per_page' => 10,
    'fields' => 'ids',          // Return only IDs when possible
    'no_found_rows' => true,    // Skip pagination count query
    'meta_query' => [
        ['key' => '_closing_date', 'value' => $today, 'compare' => '>', 'type' => 'DATE']
    ]
]);
```

### Conditional Loading

Load resources only when needed:

```php
// Maps API only when key exists and on relevant screens
$api_key = get_option('careernest_options')['maps_api_key'] ?? '';
$screen = get_current_screen();

if (!empty($api_key) &&
    $screen &&
    in_array($screen->post_type, ['job_listing', 'employer', 'applicant']) &&
    in_array($screen->base, ['post', 'post-new'])) {

    wp_enqueue_script('google-maps-api', ...);
}
```

## Security Patterns

### Input Sanitization Matrix

Consistent sanitization based on data type:

```php
$sanitization_map = [
    'text'     => 'sanitize_text_field',
    'email'    => 'sanitize_email',
    'url'      => 'esc_url_raw',
    'textarea' => 'sanitize_textarea_field',
    'rich'     => 'wp_kses_post',
    'array'    => 'array_map("sanitize_text_field", $input)'
];
```

### Capability-Based Access Control

Granular permissions throughout the system:

```php
// Template-level access control
if (!current_user_can('edit_own_profile')) {
    wp_safe_redirect(wp_login_url());
    exit;
}

// Admin screen access
if (!current_user_can('manage_careernest')) {
    wp_die('Insufficient permissions');
}
```

These patterns ensure consistency, security, and maintainability throughout the CareerNest codebase while providing a solid foundation for future enhancements.

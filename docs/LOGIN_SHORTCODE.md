# CareerNest Login Shortcode Documentation

## Overview

The `[careernest_login]` shortcode provides a complete login solution with a modal popup interface. It automatically adapts based on user authentication status.

## Usage

### Basic Usage

```
[careernest_login]
```

### With Custom Button Text

```
[careernest_login button_text="Sign In"]
```

### With Custom Redirect

```
[careernest_login redirect="https://example.com/custom-page"]
```

### Combined Attributes

```
[careernest_login button_text="Member Login" redirect="/members-area"]
```

## Shortcode Attributes

| Attribute     | Type   | Default | Description                                                                  |
| ------------- | ------ | ------- | ---------------------------------------------------------------------------- |
| `button_text` | string | "Login" | Text displayed on the login button                                           |
| `redirect`    | string | ""      | URL to redirect to after successful login (defaults to role-based dashboard) |

## Features

### When User is Logged Out

- **Login Button**: Displays customizable button text
- **Modal Popup**: Click opens a modal with:
  - Username/Email field
  - Password field
  - Remember Me checkbox
  - Submit button with loading state
  - "Forgotten Password?" link
  - "Don't have an account?" section with signup links

### When User is Logged In

- **User Display**: Shows user's display name with icon
- **Dashboard Link**: Name links to role-appropriate dashboard
- **Logout Button**: Quick logout with icon

## Role-Based Dashboard Redirects

After successful login, users are automatically redirected based on their role:

| Role            | Redirect Destination      |
| --------------- | ------------------------- |
| `aes_admin`     | WordPress Admin Dashboard |
| `administrator` | WordPress Admin Dashboard |
| `employer_team` | Employer Dashboard page   |
| `applicant`     | Applicant Dashboard page  |

If a custom redirect URL is specified via the shortcode attribute, it takes precedence over the role-based redirect.

## Technical Details

### Files Created

- `includes/class-plugin.php` - Shortcode registration and handler methods
- `assets/css/login-modal.css` - Modal and widget styling
- `assets/js/login-modal.js` - Modal functionality and AJAX login

### AJAX Login

The shortcode uses AJAX for seamless login without page refresh:

- Action: `careernest_login`
- Nonce: `careernest_login`
- Returns: Success/error message and redirect URL

### Security

- **Nonce verification** on all login requests
- **Input sanitization** for username/email
- **WordPress core authentication** via `wp_signon()`
- **Capability checks** for dashboard redirects
- **HTTPS support** for secure authentication

### Modal Features

- **Keyboard support**: ESC key to close
- **Click-to-close**: Click overlay to dismiss
- **Focus management**: Auto-focus on username field
- **Body scroll lock**: Prevents background scrolling
- **Loading states**: Visual feedback during submission
- **Error handling**: User-friendly error messages
- **Success feedback**: Confirmation before redirect

## Styling

The modal follows CareerNest's design system:

- Primary color: `#0073aa`
- Hover states and transitions
- Mobile-responsive design
- Accessibility features (focus states, ARIA labels)
- Reduced motion support
- High contrast mode support

### CSS Classes

- `.cn-login-widget` - Login button/widget container
- `.cn-login-btn` - Login button
- `.cn-logged-in` - Logged-in state container
- `.cn-user-link` - Dashboard link
- `.cn-logout-btn` - Logout button
- `.cn-modal-overlay` - Modal backdrop
- `.cn-modal-content` - Modal box
- `.cn-modal-close` - Close button
- `.cn-login-messages` - Message container
- `.cn-alert-success` / `.cn-alert-error` - Alert messages

## Responsive Design

The modal adapts to different screen sizes:

- **Desktop**: Centered modal with max-width 480px
- **Tablet**: Full-width with padding
- **Mobile**: Full-screen modal without border radius

## Accessibility

- **ARIA labels** on interactive elements
- **Focus visible states** for keyboard navigation
- **Semantic HTML** structure
- **Color contrast** meets WCAG standards
- **Screen reader** friendly labels
- **Tab navigation** support

## Integration Examples

### In Header/Navigation

Add to your theme's header or navigation menu:

```php
echo do_shortcode('[careernest_login]');
```

### In Widget Areas

Use in any widget-enabled sidebar or footer.

### In Page Content

Add directly in page/post content editor.

### In Theme Templates

```php
<?php echo do_shortcode('[careernest_login button_text="Member Area"]'); ?>
```

## Customization

### Custom Styling

Override styles in your theme's CSS:

```css
.cn-login-btn {
  background: #your-color;
}

.cn-modal-content {
  border-radius: 12px;
}
```

### Theme Integration

The modal automatically uses your theme's font family and respects WordPress color schemes.

## Troubleshooting

### Login Button Not Showing Modal

- Check browser console for JavaScript errors
- Ensure jQuery is loaded
- Verify assets are enqueued

### Redirect Not Working

- Check that dashboard pages exist in CareerNest settings
- Verify user roles are assigned correctly
- Check custom redirect URL is valid

### Styling Issues

- Clear browser cache
- Check for CSS conflicts with theme
- Verify plugin version is up to date

## Browser Support

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Notes

- Assets are only loaded when shortcode is present on page
- AJAX login requires JavaScript enabled
- Falls back gracefully if JavaScript is disabled
- Compatible with WordPress multisite
- Works with WordPress 6.0+

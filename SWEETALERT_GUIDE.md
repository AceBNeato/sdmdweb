# SweetAlert2 Integration Guide

## Overview
The application now uses SweetAlert2 instead of toast notifications for a better user experience. SweetAlert2 provides beautiful, responsive, and customizable alert boxes.

## Features

### 1. **Beautiful Modal Notifications**
- Success (green), Error (red), Warning (yellow), Info (blue) themes
- Centered positioning with smooth animations
- Custom icons and backgrounds for each type

### 2. **Toast Notifications**
- Small, top-right corner notifications
- Auto-dismiss after 3 seconds
- Progress bar indicator
- Pause on hover

### 3. **Confirmation Dialogs**
- Customizable confirm/cancel buttons
- Different button colors for different actions
- Promise-based API for handling user responses

### 4. **Loading States**
- Loading spinner during AJAX operations
- Prevents user interaction during processing
- Auto-close on completion

## Usage Examples

### Basic Notifications
```javascript
// Success
window.SweetAlert.success('Equipment created successfully!');

// Error
window.SweetAlert.error('Failed to create equipment. Please try again.');

// Warning
window.SweetAlert.warning('Are you sure you want to proceed?');

// Info
window.SweetAlert.info('System maintenance scheduled for tonight.');
```

### Toast Notifications
```javascript
// Small toast notification
window.SweetAlert.toast('success', 'Changes saved automatically!');
```

### Confirmation Dialogs
```javascript
// Delete confirmation
window.SweetAlert.confirm('Are you sure you want to delete this equipment?')
    .then((result) => {
        if (result.isConfirmed) {
            // User confirmed, proceed with deletion
            deleteEquipment();
        }
    });
```

### Loading States
```javascript
// Show loading
window.SweetAlert.loading('Processing...');

// Close loading when done
setTimeout(() => {
    window.SweetAlert.close();
}, 2000);
```

### Success with Redirect
```javascript
// Show success and redirect
window.SweetAlert.successWithRedirect('Equipment updated!', '/equipment', 1500);
```

### Error with Reload Option
```javascript
// Show error with reload option
window.SweetAlert.errorWithReload('An error occurred. Would you like to reload the page?');
```

## AJAX Helper Integration

### Form Submissions
```javascript
// Automatic form submission with SweetAlert feedback
window.AjaxHelper.submitForm(formElement, {
    loadingMessage: 'Saving changes...',
    successMessage: 'Changes saved successfully!',
    errorMessage: 'Failed to save changes.',
    reloadOnSuccess: true
});
```

### Delete Operations
```javascript
// Delete with confirmation
window.AjaxHelper.delete('/equipment/123', {
    confirmMessage: 'Are you sure you want to delete this equipment?',
    successMessage: 'Equipment deleted successfully!',
    errorMessage: 'Failed to delete equipment.'
});
```

### Status Updates
```javascript
// Update status
window.AjaxHelper.updateStatus('/equipment/123/status', {
    status: 'active'
}, {
    loadingMessage: 'Updating status...',
    successMessage: 'Status updated successfully!'
});
```

### Modal Content Loading
```javascript
// Load content into modal
window.AjaxHelper.loadModal('/equipment/123/edit', '#editModal', '#modalContent', {
    loadingMessage: 'Loading form...',
    errorMessage: 'Failed to load form.'
});
```

## Automatic Classes

The system automatically handles elements with these classes:

### `.ajax-delete`
```html
<button class="ajax-delete" data-url="/equipment/123/delete">
    Delete Equipment
</button>
```

### `.ajax-status`
```html
<button class="ajax-status" data-url="/equipment/123/status" data-params='{"status": "active"}'>
    Activate Equipment
</button>
```

### `.ajax-form`
```html
<form class="ajax-form" action="/equipment/123/update" method="POST">
    <!-- Form fields -->
    <button type="submit">Save Changes</button>
</form>
```

## Server Response Format

For AJAX operations, the server should respond with:

### Success Response
```json
{
    "success": true,
    "message": "Operation completed successfully!",
    "redirect": "/equipment" // Optional
}
```

### Error Response
```json
{
    "success": false,
    "message": "An error occurred",
    "errors": { // Optional validation errors
        "field_name": ["This field is required"]
    }
}
```

## Session Messages

Laravel flash messages automatically display as SweetAlert notifications:

```php
// In Controller
return redirect()->route('equipment.index')
    ->with('success', 'Equipment created successfully!');

return redirect()->back()
    ->with('error', 'Failed to create equipment.');
```

## Customization

### Custom Options
```javascript
window.SweetAlert.success('Success!', {
    title: 'Custom Title',
    confirmButtonText: 'Awesome!',
    confirmButtonColor: '#custom-color',
    timer: 5000,
    customClass: {
        popup: 'custom-popup-class'
    }
});
```

### Global Configuration
You can modify the default options in `sweetalert-system.js`:

```javascript
this.defaultOptions = {
    // Modify default SweetAlert options here
};
```

## Migration from Toast

The old `showToast()` function is automatically redirected to SweetAlert toast:

```javascript
// This now uses SweetAlert toast
showToast('Message', 'success');
```

## Browser Support

SweetAlert2 supports all modern browsers:
- Chrome 50+
- Firefox 45+
- Safari 10+
- Edge 79+

## Performance

- Lightweight (~50KB gzipped)
- No dependencies on jQuery (but works with it)
- Smooth animations using CSS3
- Memory efficient with automatic cleanup

## Troubleshooting

### SweetAlert Not Working
1. Check if SweetAlert2 is loaded in the page
2. Verify jQuery is loaded (for AJAX helper)
3. Check browser console for JavaScript errors

### AJAX Issues
1. Ensure CSRF token is included
2. Check network tab for failed requests
3. Verify server response format

### Styling Issues
1. Check if SweetAlert2 CSS is loaded
2. Verify no CSS conflicts
3. Check custom CSS overrides

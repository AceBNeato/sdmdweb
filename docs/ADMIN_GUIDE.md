# SDMD Equipment Management System - Administrator Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Administrator Dashboard](#administrator-dashboard)
3. [User Management](#user-management)
4. [Equipment Management](#equipment-management)
5. [Office & Campus Management](#office--campus-management)
6. [Role & Permission Management](#role--permission-management)
7. [System Settings](#system-settings)
8. [Backup & Recovery](#backup--recovery)
9. [Reports & Analytics](#reports--analytics)
10. [System Maintenance](#system-maintenance)

---

## System Overview

The SDMD Equipment Management System is a comprehensive platform for tracking equipment and maintenance lifecycle within the Systems and Data Management Division. As an administrator, you have full access to all system features and configuration options.

### Key Responsibilities
- User account management and role assignments
- Equipment inventory oversight
- System configuration and settings
- Backup management and data integrity
- Report generation and analysis

---

## Administrator Dashboard

### Accessing the Dashboard
1. Login at: `http://your-domain/login`
2. Use admin credentials:
   - Email: `superadmin@sdmd.ph` or `arthurdalemicaroz@gmail.com`
   - Password: As provided during setup

### Dashboard Features
- **System Overview**: Quick stats on equipment, users, and recent activities
- **Quick Actions**: Direct links to common tasks
- **Recent Activity**: Latest system updates and changes
- **Navigation Menu**: Access to all administrative functions

---

## User Management

### Creating New Users

#### Method 1: Through Accounts Page
1. Navigate to **Accounts** → **User Management**
2. Click **"Add User"** button
3. Fill in user details:
   - **Name**: Full name of the user
   - **Email**: Unique email address
   - **Password**: Initial password (user can change later)
   - **Role**: Select appropriate role (Admin, Technician, Staff)
   - **Office**: Assign to specific office/campus
   - **Status**: Active or Inactive

#### Method 2: Modal Interface
1. From the accounts index page
2. Click **"Create User"** button
3. Complete the form in the modal dialog
4. Click **"Save User"**

### Managing Existing Users

#### Viewing User Details
1. Go to **Accounts** → **User Management**
2. Click on any user's name or row
3. View complete user profile and permissions

#### Editing User Information
1. From user details view
2. Click **"Edit"** button
3. Modify required fields
4. Click **"Update User"**

#### Changing User Roles
1. Edit the user account
2. Select new role from dropdown
3. **Important**: Role changes trigger automatic logout for security
4. User must login again with new role permissions

#### User Status Management
- **Active**: User can login and access assigned features
- **Inactive**: User cannot login (preserves data)
- Toggle status using the status switch in user list

### Email Verification
- New users receive verification emails automatically
- Resend verification: **User** → **Actions** → **Resend Verification**
- Users must verify email before full system access

---

## Equipment Management

### Adding New Equipment

1. Navigate to **Equipment** → **Add Equipment**
2. Complete equipment details:
   - **Equipment Name**: Descriptive identifier
   - **Category**: Equipment type/classification
   - **Serial Number**: Unique serial or asset number
   - **Office**: Assigned location/office
   - **Status**: Current operational status
   - **Description**: Detailed equipment information
   - **Purchase Date**: When equipment was acquired
   - **Warranty Expiry**: Warranty period end date

3. Click **"Save Equipment"**
4. System automatically generates QR code

### Equipment Status Management
- **Available**: Ready for use
- **In Use**: Currently deployed
- **Under Repair**: Being serviced
- **Retired**: No longer in service

### QR Code Management
- **Generate**: Automatically created with new equipment
- **Print**: Individual or batch QR code printing
- **Scan**: Use mobile or web scanner for quick lookup

### Equipment History
- **View History**: Track all maintenance and repairs
- **Add History**: Record maintenance activities
- **Edit History**: Update existing records (with permissions)

---

## Office & Campus Management

### Managing Offices
1. Navigate to **Settings** → **Office Management**
2. **Add New Office**:
   - Office name
   - Campus assignment
   - Address/location details
   - Contact information

### Campus Organization
- Multiple campuses can be configured
- Offices are organized under campuses
- Equipment and users are assigned to specific offices

---

## Role & Permission Management

### Understanding Roles
- **Super Administrator**: Full system access
- **Administrator**: Equipment and user management
- **Technician**: Equipment maintenance and repairs
- **Staff**: Equipment viewing and basic operations

### Permission Categories
- **users.view**: View user accounts
- **users.create**: Create new users
- **users.edit**: Modify user information
- **users.delete**: Remove user accounts
- **equipment.view**: View equipment inventory
- **equipment.create**: Add new equipment
- **equipment.edit**: Modify equipment details
- **equipment.delete**: Remove equipment records
- **reports.view**: Access system reports
- **reports.generate**: Create and export reports
- **settings.manage**: Access system settings

### Managing Permissions
1. Navigate to **Accounts** → **Role Management**
2. Select role to modify
3. Check/uncheck permission boxes
4. Save changes

---

## System Settings

### Accessing Settings
Navigate to **Settings** → **System Configuration**

### Configuration Options

#### Email Settings
- SMTP server configuration
- Email templates
- Notification preferences

#### Session Management
- **Session Lockout**: Inactivity timeout (minutes)
- **Auto-logout**: Security settings
- **Multi-guard**: Different session types for different roles

#### System Preferences
- Date/time formats
- Currency settings
- Language preferences

#### Backup Configuration
- **Automatic Schedule**: Daily, weekly, monthly
- **Backup Type**: Database only or full system
- **Retention Period**: How long to keep backups

---

## Backup & Recovery

### Automated Backups
- **Daily Database Backups**: Runs automatically every minute
- **Cleanup Schedule**: Removes old backups (daily at midnight)
- **Storage Location**: Configurable backup directory

### Manual Backup Operations
1. Navigate to **Settings** → **Backup Management**
2. **Create Backup**:
   - Choose backup type (Database/Files)
   - Add description
   - Click **"Create Backup"**

### Restore Procedures
1. **Select Backup**: Choose from backup list
2. **Verify Backup**: Check backup integrity
3. **Initiate Restore**: 
   - System will prompt for confirmation
   - Restore process may take several minutes
   - Users will be logged out during restore

### Backup Management
- **Download**: Save backups locally
- **Delete**: Remove old or unnecessary backups
- **Schedule**: Configure automatic backup timing

---

## Reports & Analytics

### Equipment Reports
1. Navigate to **Reports** → **Equipment History**
2. **Filter Options**:
   - Date range
   - Equipment category
   - Office location
   - Status type

### Export Options
- **PDF**: Formatted reports with headers/footers
- **Excel**: Data for further analysis
- **CSV**: Raw data export

### Report Types
- **Equipment Inventory**: Complete equipment listing
- **Maintenance History**: Service and repair records
- **User Activity**: System usage statistics
- **Asset Valuation**: Equipment value reports

---

## System Maintenance

### Regular Tasks
- **User Account Review**: Monthly audit of active users
- **Equipment Audit**: Quarterly inventory verification
- **Backup Verification**: Weekly restore testing
- **Log Review**: Check system logs for issues

### Performance Optimization
- **Cache Clearing**: `php artisan optimize:clear`
- **Database Maintenance**: `php artisan migrate:fresh --seed` (with caution)
- **Asset Compilation**: `npm run build` for production

### Troubleshooting Common Issues

#### Login Problems
- Check user status (active/inactive)
- Verify email confirmation
- Reset password if needed
- Clear browser cache

#### Equipment Not Displaying
- Verify office assignments
- Check user permissions
- Refresh equipment cache

#### Backup Issues
- Verify storage permissions
- Check disk space
- Review backup logs

---

## Security Best Practices

### Password Policies
- Minimum 8 characters
- Include numbers and special characters
- Regular password changes recommended

### Session Security
- Automatic logout after inactivity
- Session lockout feature
- Multi-guard authentication

### Access Control
- Principle of least privilege
- Regular permission reviews
- Immediate deactivation of departed users

---

## Contact & Support

### Technical Support
- System Administrator: Contact via internal channels
- Emergency Issues: Use system emergency contacts
- Documentation: Check this guide first

### System Information
- Version: Laravel 12 based application
- Database: MySQL/MariaDB
- Frontend: Vite, TailwindCSS, Alpine.js

---

## Quick Reference

### Common Commands
```bash
# Clear system cache
php artisan optimize:clear

# Run database migrations
php artisan migrate --seed

# Create storage link
php artisan storage:link

# View scheduled tasks
php artisan schedule:list

# Run scheduled tasks manually
php artisan schedule:run
```

### Default Credentials
- **Super Admin**: superadmin@sdmd.ph / superadmin123
- **Admin**: arthurdalemicaroz@gmail.com / 12345678

### Important File Locations
- **Environment**: `.env`
- **Logs**: `storage/logs/laravel.log`
- **Backups**: Configurable in settings
- **Uploads**: `storage/app/public`

---

*Last Updated: November 2025*
*System Version: Current*
*For internal use only*
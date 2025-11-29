# SDMD Equipment Management System - User Manual

## Table of Contents
1. [System Setup & Installation](#system-setup--installation)
2. [Welcome to SDMD](#welcome-to-sdmd)
3. [Getting Started](#getting-started)
4. [System Navigation](#system-navigation)
5. [User Roles & Access](#user-roles--access)
6. [Equipment Overview](#equipment-overview)
7. [QR Code Scanner](#qr-code-scanner)
8. [Basic Operations](#basic-operations)
9. [Reports & Information](#reports--information)
10. [Account Management](#account-management)
11. [Frequently Asked Questions](#frequently-asked-questions)

---

## System Setup & Installation

### System Requirements

| Component | Minimum Version | Notes |
|-----------|-----------------|-------|
| PHP | 8.2.x | Enable `fileinfo`, `openssl`, `pdo_mysql`, `mbstring`, `curl`, `zip` |
| Composer | 2.6+ | Used for PHP dependency management |
| Node.js / npm | Node 18+, npm 9+ | Required for Vite asset build |
| Database | MySQL 8 / MariaDB 10.5 | Update `.env` with credentials |
| Git | latest | Source control |

> **Windows users:** Laragon includes PHP, MySQL, and Node out of the box. Ensure PHP is added to your PATH before running artisan commands from terminals outside Laragon.

### Quick Installation Guide

#### Step 1: Clone the Project
```bash
git clone https://github.com/<org>/sdmdweb.git
cd sdmdweb
```

#### Step 2: Install PHP Dependencies
```bash
composer install
```

#### Step 3: Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### Step 4: Configure Environment File
Edit the `.env` file with your settings:

```bash
# Database Configuration
DB_DATABASE=sdmd
DB_USERNAME=root
DB_PASSWORD=secret
APP_URL=http://sdmd.test (or your domain)

# Email Configuration (for user verification)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="SDMD"
```

#### Step 5: Database Setup
```bash
# Run migrations and seeders (creates roles, offices, default users)
php artisan migrate --seed
```

#### Step 6: Storage Link Setup
```bash
# Link storage for uploaded avatars, QR codes, etc. (REQUIRED)
php artisan storage:link
```

#### Step 7: Frontend Assets
```bash
# Install Node.js dependencies
npm install

# Compile assets for production
npm run build
# Or use npm run dev for development with hot reload
```

#### Step 8: Start the Application
```bash
# Start Laravel development server
php artisan serve
# Visit: http://127.0.0.1:8000
```

### Default Administrator Accounts

After installation, these accounts are created:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@sdmd.ph | superadmin123 |
| Super Admin (2) | superadmin2@sdmd.ph | superadmin123 |
| Admin | arthurdalemicaroz@gmail.com | 12345678 |

> **Important:** Update these credentials immediately in production environments.

### Useful Commands

#### Database & Maintenance
```bash
# Reset database and reseed demo data
php artisan migrate:fresh --seed

# Clear all system caches
php artisan optimize:clear

# Clear config and cache separately
php artisan config:clear && php artisan cache:clear
```

#### Asset Management
```bash
# Watch assets during development
npm run dev

# Build production assets
npm run build
```

#### Development Tools
```bash
# Run queued jobs (for notifications/async tasks)
php artisan queue:work

# Start development server with queue and Vite
composer run dev
```

### Troubleshooting Installation

#### Common Issues

**"Class not found" errors:**
```bash
# Run composer autoloader
composer dump-autoload
```

**Storage link issues:**
```bash
# Remove and recreate storage link
rm public/storage
php artisan storage:link
```

**Permission issues (Linux/Mac):**
```bash
# Set proper permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Database connection errors:**
- Verify database credentials in `.env`
- Ensure database server is running
- Check database exists and user has permissions

**Asset compilation issues:**
```bash
# Clear npm cache and reinstall
npm cache clean --force
rm -rf node_modules
npm install
npm run build
```

### Production Deployment

For production deployment:

1. **Environment Variables:**
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure production database
   - Set proper mail settings

2. **Optimization:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

3. **Security:**
   - Update default passwords
   - Configure HTTPS
   - Set up proper file permissions
   - Configure firewalls

4. **Backups:**
   - Set up automated database backups
   - Backup `.env` file
   - Document recovery procedures

---

## Welcome to SDMD

The SDMD Equipment Management System is a comprehensive platform designed to help you track, manage, and maintain equipment efficiently. Whether you're an administrator, technician, or staff member, this system provides the tools you need to manage equipment throughout its lifecycle.

### What You Can Do
- **View Equipment**: Browse and search equipment inventory
- **Track Status**: Monitor equipment availability and condition
- **Scan QR Codes**: Quickly identify equipment using mobile devices
- **View History**: Access maintenance and service records
- **Generate Reports**: Create equipment and maintenance reports

---

## Getting Started

### System Requirements
- **Modern Web Browser**: Chrome, Firefox, Safari, or Edge
- **Internet Connection**: For most features
- **Device**: Desktop, laptop, tablet, or smartphone
- **Camera**: For QR code scanning (mobile devices)

### First Time Login
1. Open your web browser
2. Go to: `http://your-domain/login`
3. Select your user type (Admin/Technician/Staff)
4. Enter your credentials (provided by your administrator)
5. Click **"Login"**

### Login Credentials
- **Username**: Your email address
- **Password**: Initial password from administrator
- **Change Password**: Recommended on first login

---

## System Navigation

### Main Menu
The main navigation menu provides access to all system features:

- **Dashboard**: Your personalized home page
- **Equipment**: Equipment inventory and management
- **Reports**: System reports and analytics
- **Accounts**: User management (admin only)
- **Settings**: System configuration (admin only)

### Quick Actions
Common tasks are available as quick action buttons:
- **QR Scanner**: Quick equipment identification
- **Add Equipment**: Create new equipment records
- **Search**: Find equipment or users
- **Help**: Access documentation

### Breadcrumb Navigation
- Shows your current location in the system
- Click any part to navigate back
- Helps you understand where you are

---

## User Roles & Access

### Role Overview

#### Administrator
- **Full Access**: Can manage all system features
- **User Management**: Create and manage user accounts
- **System Settings**: Configure system preferences
- **Backup Management**: Handle data backup and recovery

#### Technician
- **Equipment Management**: Update equipment status and details
- **Maintenance Records**: Add and edit maintenance history
- **QR Operations**: Scan and manage QR codes
- **Reports**: Generate equipment and maintenance reports

#### Staff
- **View Equipment**: Browse equipment inventory
- **Basic Operations**: View equipment details and history
- **QR Scanning**: Identify equipment using QR codes
- **Limited Reports**: Access basic equipment information

### Permission Levels
- **View**: Read access to information
- **Create**: Add new records
- **Edit**: Modify existing information
- **Delete**: Remove records (limited by role)
- **Export**: Download reports and data

---

## Equipment Overview

### Equipment List
The equipment list shows all equipment you have access to:

#### Columns Displayed
- **Equipment Name**: Descriptive equipment identifier
- **Category**: Equipment type or classification
- **Serial Number**: Unique identifier
- **Status**: Current operational state
- **Office**: Assigned location
- **Last Updated**: Most recent modification

#### Status Indicators
- **🟢 Available**: Ready for use
- **🔵 In Use**: Currently deployed
- **🟡 Under Repair**: Being serviced
- **🔴 Retired**: No longer in service

### Equipment Details
Click any equipment item to view comprehensive information:

#### Basic Information
- **Name**: Equipment designation
- **Category**: Type classification
- **Serial Number**: Unique ID
- **Description**: Detailed specifications
- **Purchase Date**: Acquisition date
- **Warranty**: Warranty period information

#### Current Status
- **Status**: Operational state
- **Location**: Assigned office/position
- **Condition**: Working condition notes
- **Last Updated**: Status change timestamp

#### Maintenance History
- **Service Records**: Complete maintenance timeline
- **Repair Details**: Parts and labor information
- **Cost Tracking**: Maintenance expenses
- **Next Service**: Scheduled maintenance dates

---

## QR Code Scanner

### Using the QR Scanner
1. Navigate to **QR Scanner** from the main menu
2. **Allow Camera Access** when prompted (mobile devices)
3. Point your camera at the equipment QR code
4. System automatically displays equipment information

### Scanner Features
- **Auto-Focus**: Automatically focuses on QR codes
- **Instant Recognition**: Quick equipment identification
- **Manual Entry**: Alternative to camera scanning
- **History Log**: Tracks recent scans

### Manual QR Entry
If camera scanning is not available:
1. Click **"Manual Entry"** in the scanner interface
2. Enter the QR code value or equipment ID
3. Click **"Search"**
4. View equipment details

### QR Code Information
Each QR code contains:
- **Equipment ID**: Unique system identifier
- **Quick Lookup**: Direct access to equipment details
- **Mobile Friendly**: Optimized for mobile scanning
- **Offline Access**: Basic info available offline

---

## Basic Operations

### Searching Equipment
1. Navigate to **Equipment** section
2. Use the search bar to find equipment:
   - **By Name**: Equipment designation
   - **By Serial**: Unique serial number
   - **By Category**: Equipment type
   - **By Status**: Current operational state

### Filtering Equipment
Use filter options to narrow results:
- **Office Location**: Filter by assigned office
- **Equipment Type**: Filter by category
- **Date Range**: Filter by purchase or update dates
- **Status**: Filter by current status

### Sorting Equipment
Sort equipment list by:
- **Name**: Alphabetical order
- **Date**: Purchase or update date
- **Status**: Operational state
- **Category**: Equipment type

### Viewing Equipment History
1. Open equipment details
2. Click **"History"** tab
3. View chronological maintenance records
4. Filter by date or maintenance type

---

## Reports & Information

### Available Reports
Based on your role, you can access:

#### Equipment Reports
- **Inventory List**: Complete equipment catalog
- **Status Summary**: Equipment by current status
- **Location Report**: Equipment by office/location
- **Age Analysis**: Equipment by purchase date

#### Maintenance Reports (Technician+)
- **Service History**: Complete maintenance timeline
- **Cost Analysis**: Maintenance expense tracking
- **Downtime Report**: Equipment availability statistics
- **Upcoming Service**: Scheduled maintenance alerts

### Export Options
- **PDF**: Formatted reports for printing
- **Excel**: Data for spreadsheet analysis
- **CSV**: Raw data for import into other systems

### Report Features
- **Date Range Selection**: Custom time periods
- **Filter Options**: Narrow report scope
- **Custom Columns**: Choose displayed data
- **Scheduled Reports**: Automated report generation

---

## Account Management

### Profile Information
View and manage your personal account:
- **Name**: Your display name
- **Email**: Contact email address
- **Role**: Assigned user role
- **Office**: Your assigned location
- **Last Login**: Previous access timestamp

### Updating Profile
1. Click your name in the top navigation
2. Select **"Profile"**
3. Click **"Edit Profile"**
4. Update your information:
   - **Display Name**: How you appear in the system
   - **Email**: Contact email
   - **Phone**: Contact number (optional)
5. Click **"Update Profile"**

### Password Management
1. From your profile, click **"Change Password"**
2. Enter your **current password**
3. Set a **new password** (minimum 8 characters)
4. **Confirm** the new password
5. Click **"Update Password"**

### Security Settings
- **Session Timeout**: Automatic logout after inactivity
- **Login History**: Track your access times
- **Security Alerts**: Notifications for account changes

---

## Mobile Access

### Smartphone Usage
- **Responsive Design**: Optimized for mobile screens
- **Touch Interface**: Easy tap and swipe navigation
- **Camera Integration**: Built-in QR code scanning
- **Offline Features**: Basic functions without internet

### Tablet Usage
- **Larger Display**: Better for detailed viewing
- **Split Screen**: Multi-tasking capability
- **Stylus Support**: Precision input options
- **Keyboard Support**: External keyboard compatibility

### Mobile Features
- **Quick Access**: Frequently used functions
- **Gesture Navigation**: Intuitive controls
- **Auto-Fill**: Form completion assistance
- **Push Notifications**: System alerts and reminders

---

## Troubleshooting

### Common Issues

#### Login Problems
- **Forgot Password**: Contact your administrator
- **Account Locked**: Wait 15 minutes or contact admin
- **Wrong Credentials**: Verify username and password
- **Browser Issues**: Clear cache and cookies

#### Scanner Not Working
- **Camera Permission**: Allow camera access in browser
- **Lighting**: Ensure good lighting conditions
- **QR Code Damage**: Use manual entry if QR is damaged
- **Device Compatibility**: Try different device or browser

#### Equipment Not Found
- **Search Terms**: Try different keywords
- **Filters**: Remove or adjust search filters
- **Permissions**: Verify you have access to equipment
- **Contact Admin**: Report missing equipment

#### System Slow
- **Internet Connection**: Check connection speed
- **Browser Update**: Use latest browser version
- **Clear Cache**: Remove temporary files
- **Close Tabs**: Reduce browser load

### Error Messages
- **Access Denied**: You don't have permission for this action
- **Session Expired**: Login again to continue
- **Equipment Locked**: Another user is editing this item
- **System Busy**: Try again in a few moments

### Getting Help
1. **Check This Guide**: Review relevant sections
2. **Contact Administrator**: Your primary support contact
3. **Document Issues**: Keep records of problems
4. **System Status**: Check for known system issues

---

## Best Practices

### Daily Usage
- **Login Regularly**: Check for updates and assignments
- **Update Status**: Keep equipment information current
- **Document Activities**: Record all equipment interactions
- **Logout**: Secure your session when finished

### Data Quality
- **Accurate Information**: Ensure data correctness
- **Complete Records**: Fill all required fields
- **Timely Updates**: Make changes promptly
- **Consistent Format**: Use standard naming conventions

### Security
- **Strong Passwords**: Use complex, unique passwords
- **Session Security**: Lock your computer when away
- **Report Issues**: Notify admin of security concerns
- **Access Control**: Only access what you need

---

## Frequently Asked Questions

### General Questions

**Q: How do I reset my password?**
A: Contact your system administrator for password reset assistance.

**Q: Can I access the system from my phone?**
A: Yes, the system is mobile-friendly and works on smartphones and tablets.

**Q: What if I forget to logout?**
A: The system automatically logs you out after a period of inactivity.

### Equipment Questions

**Q: How do I report equipment damage?**
A: Contact your technician or administrator to report equipment issues.

**Q: Can I print QR codes?**
A: Yes, QR codes can be printed from the equipment details page.

**Q: How often should I update equipment status?**
A: Update status immediately when equipment conditions change.

### Technical Questions

**Q: Why can't I see certain equipment?**
A: Equipment visibility is based on your role and office assignment.

**Q: What browsers are supported?**
A: Modern browsers including Chrome, Firefox, Safari, and Edge.

**Q: Is my data secure?**
A: Yes, the system uses secure connections and encryption.

---

## System Updates

### New Features
The system is regularly updated with new features:
- **Enhanced Reports**: Improved reporting capabilities
- **Mobile Improvements**: Better mobile experience
- **Security Updates**: Enhanced protection measures
- **Performance**: Faster operation and response times

### Update Notifications
- **System Messages**: In-app notifications
- **Email Alerts**: Important update information
- **Training**: Documentation for new features
- **Support**: Assistance with changes

---

## Contact Information

### Technical Support
- **System Administrator**: Primary contact for system issues
- **Department Head**: For equipment and policy questions
- **IT Support**: For login and access problems
- **Help Desk**: General assistance and guidance

### Emergency Contacts
- **System Failure**: Immediate notification required
- **Data Issues**: Report data integrity problems
- **Security Breaches**: Report security concerns immediately
- **Equipment Emergencies**: Follow established procedures

---

## Quick Reference

### Navigation Shortcuts
```
Dashboard: Home icon
Equipment: Equipment menu
Scanner: QR Scanner button
Reports: Reports menu
Profile: Your name in header
```

### Status Meanings
```
🟢 Available: Ready to use
🔵 In Use: Currently deployed
🟡 Under Repair: Being serviced
🔴 Retired: Out of service
```

### Common Tasks
```
Find Equipment: Equipment → Search
Scan QR Code: QR Scanner button
View History: Equipment → [Select] → History
Update Profile: Your name → Profile
```

---

*Last Updated: November 2025*
*System Version: Current*
*For All User Levels*
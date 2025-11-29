# SDMD Equipment Management System - Technician Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Technician Dashboard](#technician-dashboard)
3. [Equipment Management](#equipment-management)
4. [Maintenance & Repairs](#maintenance--repairs)
5. [QR Code Operations](#qr-code-operations)
6. [Reports & History](#reports--history)
7. [Profile Management](#profile-management)
8. [Troubleshooting](#troubleshooting)

---

## System Overview

The SDMD Equipment Management System provides technicians with tools to manage equipment maintenance, track repair history, and perform equipment inspections. As a technician, you play a crucial role in maintaining the equipment lifecycle and ensuring operational readiness.

### Your Key Responsibilities
- Perform equipment maintenance and repairs
- Update equipment status and history records
- Generate job orders and repair documentation
- Scan and identify equipment using QR codes
- Create maintenance reports

---

## Technician Dashboard

### Accessing Your Dashboard
1. Login at: `http://your-domain/login`
2. Select **Technician** login option
3. Enter your credentials (provided by administrator)
4. You will be redirected to your technician dashboard

### Dashboard Features
- **Profile Overview**: Your account information and assigned office
- **Quick Actions**: Common tasks like QR scanner and equipment search
- **Recent Activities**: Your latest maintenance and repair activities
- **Equipment Summary**: Quick stats on equipment under your care

---

## Equipment Management

### Viewing Equipment List
1. Navigate to **Equipment** from the main menu
2. View all equipment assigned to your office
3. Use search and filter options to find specific items

### Equipment Details
Click on any equipment item to view:
- **Basic Information**: Name, serial number, category
- **Current Status**: Available, In Use, Under Repair, Retired
- **Location**: Assigned office and position
- **Purchase Information**: Date, warranty details
- **Maintenance History**: Complete service record

### Updating Equipment Status
1. Open equipment details
2. Click **"Edit Equipment"**
3. Update status as needed:
   - **Available**: Equipment is ready for use
   - **Under Repair**: Equipment is being serviced
   - **In Use**: Equipment is currently deployed
4. Add notes about the status change
5. Click **"Update Equipment"**

---

## Maintenance & Repairs

### Creating Maintenance Records

#### Adding New Maintenance Entry
1. Navigate to equipment details
2. Click **"Add Maintenance History"**
3. Fill in maintenance information:
   - **Maintenance Type**: Routine, Repair, Inspection, Upgrade
   - **Date Performed**: When the work was completed
   - **Technician**: Your name (auto-filled)
   - **Description**: Detailed work performed
   - **Parts Used**: Any replacement parts
   - **Cost**: Labor and parts costs
   - **Next Due Date**: When next service is required

4. Click **"Save Maintenance Record"**

#### Job Order Generation
1. While adding maintenance, click **"Generate JO Number"**
2. System creates unique job order identifier
3. Include JO number in all documentation

### Editing Maintenance Records
1. Go to equipment history section
2. Find the maintenance record to edit
3. Click **"Edit"** (requires appropriate permissions)
4. Update required fields
5. Click **"Update Record"**

### Maintenance Workflow
1. **Receive Equipment**: Equipment assigned for maintenance
2. **Initial Inspection**: Assess condition and required work
3. **Perform Repairs**: Complete necessary maintenance
4. **Update Status**: Mark as "Under Repair" during work
5. **Complete Work**: Update to "Available" when finished
6. **Document**: Create detailed maintenance record

---

## QR Code Operations

### QR Code Scanner
1. Navigate to **QR Scanner** from the menu
2. Allow camera access on your device
3. Point camera at equipment QR code
4. System automatically displays equipment details

### Manual QR Code Entry
If camera scanning is not available:
1. Click **"Manual Entry"** in scanner
2. Enter QR code or equipment ID
3. Click **"Search"**
4. View equipment information

### QR Code Features
- **Instant Lookup**: Quick equipment identification
- **Mobile Friendly**: Works on smartphones and tablets
- **Offline Capability**: Some features work without internet
- **History Tracking**: Scan logs maintained

---

## Reports & History

### Equipment History Reports
1. Navigate to **Reports** → **Equipment History**
2. Select equipment or date range
3. View complete maintenance timeline
4. Export options available:
   - **PDF**: Formatted report for printing
   - **Excel**: Data for analysis

### Creating Custom Reports
1. Use filters in reports section:
   - **Date Range**: Specific time periods
   - **Equipment Type**: Filter by category
   - **Maintenance Type**: Repairs, inspections, etc.
   - **Status**: Current equipment status
2. Click **"Generate Report"**
3. Choose export format

### Report Information Included
- **Equipment Details**: Basic information and specifications
- **Maintenance Timeline**: All service records chronologically
- **Cost Analysis**: Parts and labor costs over time
- **Downtime Tracking**: Equipment availability statistics

---

## Profile Management

### Viewing Your Profile
1. Click your name in the top navigation
2. Select **"Profile"**
3. View your account information and permissions

### Updating Profile Information
1. From profile page, click **"Edit Profile"**
2. Update personal information:
   - **Name**: Display name
   - **Email**: Contact email
   - **Phone**: Contact number
   - **Office**: Assigned location (admin controlled)

### Password Management
1. From profile, access **"Change Password"**
2. Enter current password
3. Set new password (minimum 8 characters)
4. Confirm new password
5. Click **"Update Password"**

### Session Management
- **Auto Lockout**: Session locks after inactivity
- **Manual Lock**: Click lock icon to secure session
- **Logout**: Always logout when finished

---

## Equipment Status Guide

### Status Definitions
- **Available**: Equipment is ready for deployment
- **In Use**: Currently assigned and operational
- **Under Repair**: Being serviced or maintained
- **Retired**: No longer in service

### Status Change Procedures
1. **Before Changing**: Ensure you have proper authorization
2. **Add Notes**: Always include reason for status change
3. **Update History**: Create maintenance record when applicable
4. **Notify**: Inform relevant parties of status changes

---

## Troubleshooting

### Common Issues

#### Equipment Not Found
- **Check QR Code**: Ensure QR code is clean and readable
- **Verify ID**: Double-check equipment or serial number
- **Contact Admin**: Report missing equipment to administrator

#### Scanner Not Working
- **Camera Permission**: Allow browser camera access
- **Lighting**: Ensure good lighting for QR code scanning
- **Manual Entry**: Use manual entry as backup

#### Cannot Update Equipment
- **Check Permissions**: Ensure you have edit rights
- **Contact Admin**: Request additional permissions if needed
- **Verify Status**: Some statuses may be restricted

#### Login Problems
- **Check Credentials**: Verify username and password
- **Account Status**: Ensure your account is active
- **Contact Admin**: Report login issues to administrator

### Error Messages
- **Permission Denied**: You don't have rights for this action
- **Equipment Locked**: Another user is editing this equipment
- **Invalid Status**: Requested status change is not allowed

### Getting Help
1. **Check This Guide**: Review relevant sections
2. **Ask Administrator**: Contact your system admin
3. **Document Issues**: Keep records of problems encountered

---

## Best Practices

### Maintenance Procedures
- **Document Everything**: Keep detailed records of all work
- **Use JO Numbers**: Generate job orders for tracking
- **Update Status**: Keep equipment status current
- **Quality Notes**: Provide clear, detailed descriptions

### Safety Guidelines
- **Follow Procedures**: Use established maintenance protocols
- **Report Hazards**: Document safety concerns immediately
- **Equipment Handling**: Use proper equipment handling techniques
- **Documentation**: Maintain accurate safety records

### Time Management
- **Priority System**: Handle urgent repairs first
- **Schedule Maintenance**: Plan routine service in advance
- **Update Records**: Complete documentation promptly
- **Communicate**: Keep stakeholders informed

---

## Mobile Usage

### Smartphone Access
- **Responsive Design**: System works on mobile devices
- **Camera Scanner**: Use phone camera for QR scanning
- **Touch Interface**: Optimized for touch screen operation
- **Offline Features**: Some functions work without internet

### Tablet Usage
- **Larger Screen**: Better for detailed equipment viewing
- **Split Screen**: Can reference documents while working
- **Stylus Support**: Compatible with stylus input
- **Docking**: Can connect to external keyboards

---

## Quick Reference

### Common Tasks
```
Scan Equipment: Equipment → QR Scanner
Add Maintenance: Equipment → [Select] → Add History
Update Status: Equipment → [Select] → Edit → Change Status
Generate Report: Reports → Equipment History → Export
```

### Status Flow
```
Available → In Use → Under Repair → Available
Available → Under Repair → Retired
```

### Maintenance Types
- **Routine**: Scheduled preventive maintenance
- **Repair**: Fixing broken or malfunctioning equipment
- **Inspection**: Regular condition checks
- **Upgrade**: Improving equipment capabilities

---

## Contact Information

### Technical Support
- **System Administrator**: Your primary contact
- **Department Head**: For equipment priority issues
- **IT Support**: For login and system access problems

### Emergency Procedures
- **Equipment Failure**: Report immediately to supervisor
- **Safety Issues**: Follow established safety protocols
- **System Downtime**: Use paper records until system restored

---

*Last Updated: November 2025*
*System Version: Current*
*For Technician Use Only*
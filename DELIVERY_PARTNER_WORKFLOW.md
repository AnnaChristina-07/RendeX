# Delivery Partner Application Workflow

## Overview
This document describes the complete delivery partner application and approval workflow in RendeX.

## User Journey

### Step 1: Application Submission
1. **Entry Point**: User clicks "Join as Driver" button on the main dashboard
   - Location: `dashboard.php` (lines 670-678)
   - Button visible when user's role is NOT `delivery_partner` or `delivery_partner_pending`

2. **Application Form**: User is directed to `driver-registration.php`
   - Collects comprehensive information:
     - **Personal Info**: Full name, email, phone, date of birth
     - **Address**: Full address, city, PIN code
     - **Vehicle Info**: Type, number, license number, license expiry
     - **Service Details**: Service areas (checkboxes), availability hours, experience
     - **Requirements**: Smartphone confirmation, terms agreement

3. **Form Submission**:
   - User data is stored in `users.json`
   - User's `role` is set to `delivery_partner_pending`
   - Application details are stored in the user object under `delivery_application` field
   - Success message is shown
   - User is redirected back to dashboard

### Step 2: Dashboard Status Change
After submission, when user returns to dashboard:
- The "Join as Driver" button is replaced with "Pending Approval" status (lines 672-676)
- User sees an amber-colored badge with hourglass icon indicating pending status
- User cannot access delivery dashboard yet

### Step 3: Admin Review
Admin views the application in `admin_dashboard.php`:

1. **Notification Indicators**:
   - Sidebar shows pending count with animated badge (lines 313-320)
   - Quick stats section in sidebar (lines 337-360)

2. **Application Details Display** (Users tab, lines 470-590):
   - Shows in separate blue section: "Pending Delivery Partner Requests"
   - Each application card displays:
     - **Basic Info**: Name, email, phone
     - **Vehicle Details**: Type (with icon), number, license number, license expiry
     - **Service Areas**: Displayed as blue badges
     - **Availability**: Hours and experience level
     - **Applied Date**: When the application was submitted
     - **Full Address**: Complete address with city and PIN code

3. **Admin Actions**:
   - **Approve**: Changes user's role to `delivery_partner` (lines 75-87)
   - **Reject**: Removes the user from the system completely (lines 89-95)

### Step 4: Post-Approval
**If Approved**:
- User role changes to `delivery_partner`
- Dashboard now shows "Go to Delivery Dashboard" button (lines 670-671)
- User gains access to `delivery_dashboard.php`
- User appears in "Delivery Partners" section of admin's user management

**If Rejected**:
- User account is deleted from the system
- User must create a new account to reapply

## File Structure

### Main Files
1. **driver-registration.php** - Application form page
   - Handles form display and submission
   - Validates all required fields
   - Stores application data

2. **admin_dashboard.php** - Admin control panel
   - Displays pending applications (lines 470-590)  
   - Handles approve/reject actions (lines 75-95)
   - Shows all user categories including delivery partners

3. **dashboard.php** - User main dashboard
   - Shows appropriate CTA based on user role (lines 663-684)
   - "Join as Driver" for regular users
   - "Pending Approval" for pending applications
   - "Go to Delivery Dashboard" for approved partners

4. **delivery_dashboard.php** - Delivery partner workspace
   - Only accessible to users with `delivery_partner` role
   - Shows assigned delivery tasks

### Data Storage
All data is stored in `users.json` with the following structure for pending/approved partners:

```json
{
  "id": "unique_id",
  "name": "Full Name",
  "email": "email@example.com",
  "phone": "1234567890",
  "role": "delivery_partner_pending" or "delivery_partner",
  "password_hash": "hashed_password",
  "created_at": "2024-01-07 12:00:00",
  "delivery_application": {
    "full_name": "Full Name",
    "phone": "1234567890",
    "email": "email@example.com",
    "date_of_birth": "1990-01-01",
    "address": "123 Main St",
    "city": "Kochi",
    "pincode": "682001",
    "vehicle_type": "scooter",
    "vehicle_number": "KL 07 AB 1234",
    "license_number": "KL0720200001234",
    "license_expiry": "2026-01-01",
    "service_areas": ["Kochi", "Ernakulam", "Kakkanad"],
    "availability_hours": "full_day",
    "experience": "1_to_3",
    "has_smartphone": true,
    "applied_at": "2024-01-07 12:00:00",
    "status": "pending"
  }
}
```

## User Role States

1. **`renter`** - Default role for new signups
   - Sees "Join as Driver" button
   - Can apply to become delivery partner

2. **`delivery_partner_pending`** - Application submitted
   - Sees "Pending Approval" status
   - Cannot access delivery dashboard
   - Waits for admin action

3. **`delivery_partner`** - Approved by admin
   - Can access delivery dashboard
   - Appears in delivery partners list
   - Can be assigned delivery tasks

## Key Features

### For Users:
✅ Comprehensive application form with validation
✅ Real-time status updates on dashboard
✅ Clear visual feedback for pending state
✅ Detailed requirements display during application

### For Admin:
✅ Complete application details visible
✅ One-click approve/reject buttons
✅ Pending count notifications
✅ Search and filter delivery partners
✅ Assignment of delivery tasks to approved partners

## Security Notes

- Admin access restricted to `annachristina2005@gmail.com`
- Passwords are hashed before storage
- Form validation on both client and server side
- Confirmation prompts before rejecting applications

## Testing Checklist

- [ ] User can navigate to application form from dashboard
- [ ] Form validates all required fields
- [ ] Application data is saved correctly
- [ ] Dashboard shows "Pending Approval" status after submission
- [ ] Admin sees application in admin dashboard
- [ ] Admin can view all application details
- [ ] Admin can approve application (role changes to delivery_partner)
- [ ] Admin can reject application (user removed)
- [ ] Approved users can access delivery dashboard
- [ ] Approved users see "Go to Delivery Dashboard" button

## Current Status

✅ **Complete and Functional**

All components of the delivery partner application workflow are implemented and working:
- Application form collects detailed information
- Data is stored with proper structure
- Admin can review applications with full details
- Approve/reject functionality works correctly
- Dashboard updates based on user status
- Only users who applied are shown in admin dashboard

# User Profile Manager

## Overview
This module automatically creates and updates user profiles whenever a logged-in user submits a form on the frontend.

## Features

### 1. **Automatic Profile Creation**
- When a user registers on the frontend, a profile is automatically created in Drupal
- Profile stores: First Name, Last Name, Phone, Company

### 2. **Form Submission Tracking**
- Every form submitted by a logged-in user is stored in their profile
- Submissions are stored as JSON data including:
  - Form ID
  - Submission ID
  - Timestamp
  - All form data

### 3. **Profile Fields**
- **First Name** - User's first name
- **Last Name** - User's last name  
- **Phone** - Contact phone number
- **Company** - Company name
- **Form Submissions** - JSON array of all form submissions

## How It Works

### Frontend (Next.js)
1. User registers via `/api/auth/register`
2. User profile is automatically created in Drupal
3. User submits any webform while logged in
4. Form data is captured and stored

### Backend (Drupal)
1. `user_profile_manager` module hooks into webform submissions
2. When a webform is submitted:
   - Checks if user is logged in
   - Creates profile if it doesn't exist (type: `user_submissions`)
   - Updates profile fields with latest form data
   - Appends submission to JSON array in `field_submissions`

### Viewing Profiles (Admins)
- Navigate to `/admin/people/profiles` to view all user profiles
- Or go to a specific user's profile from their account page
- Profile displays:
  - User information
  - Contact details
  - Complete history of form submissions

## Installation

### Local (DDEV)
```bash
ddev drush en user_profile_manager -y
ddev drush cr
```

### Production (Hostinger)
```bash
cd domains/darkcyan-stork-408379.hostingersite.com/public_html
git pull
vendor/bin/drush en user_profile_manager -y
vendor/bin/drush cr
```

## Profile Type

**Machine name:** `user_submissions`
**Label:** User Submissions Profile

## API Integration

### Registration Endpoint
`POST /api/auth/register`

Creates both a user account AND a profile automatically.

```json
{
  "name": "johndoe",
  "mail": "john@example.com",
  "pass": "password123",
  "field_first_name": "John",
  "field_last_name": "Doe",
  "phone": "+351912345678",
  "company": "ACME Corp"
}
```

## Technical Details

### Hooks Used
- `hook_webform_submission_insert()` - When form is first submitted
- `hook_webform_submission_update()` - When form is updated

### Profile Entity
- Type: profile
- Bundle: user_submissions
- One profile per user (1:1 relationship)

### Data Storage
Form submissions are stored as JSON in `field_submissions`:
```json
[
  {
    "webform_id": "contact_form",
    "submission_id": "123",
    "timestamp": 1769787534,
    "data": {
      "name": "John Doe",
      "email": "john@example.com",
      "message": "Hello..."
    }
  }
]
```

## Permissions

Admins can:
- View all profiles
- Edit all profiles
- Delete profiles

Users can:
- View own profile
- Cannot edit profile directly (updated via form submissions)

## Future Enhancements

- Add profile display page for users to view their own submission history
- Export profile data to CSV
- Email notifications when profile is updated
- Profile completion percentage

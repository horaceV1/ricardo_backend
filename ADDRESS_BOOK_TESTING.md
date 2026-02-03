# Address Book Implementation - Testing Guide

## Overview
The system now automatically saves address information to the user's profile address book (customer profile) when submitted from the frontend.

## Changes Made

### 1. Modified JWT Auth Controller
File: `web/modules/custom/jwt_auth_api/src/Controller/JwtAuthController.php`

**Registration (POST /api/auth/register)**
- Now creates TWO profiles:
  - `user_submissions` profile (existing - simple text fields)
  - `customer` profile (NEW - structured address field for address book)

**Profile Update (POST /api/auth/profile)**
- Updates both profiles
- Creates customer profile if it doesn't exist
- Logs all operations for debugging

### 2. Expected Data Format
The frontend should send address data in this format:

```json
{
  "field_first_name": "John",
  "field_last_name": "Doe",
  "field_phone": "+351912345678",
  "field_address": "Rua de Teste, 123",
  "field_city": "Lisboa",
  "field_postal_code": "1000-001",
  "field_country": "PT"
}
```

## Testing

### Step 1: Get a JWT Token
```bash
curl -X POST https://darkcyan-stork-408379.hostingersite.com/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "your_username",
    "pass": "your_password"
  }'
```

Save the `access_token` from the response.

### Step 2: Update Profile with Address
```bash
curl -X POST https://darkcyan-stork-408379.hostingersite.com/api/auth/profile \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    "field_first_name": "Test",
    "field_last_name": "User",
    "field_phone": "+351912345678",
    "field_address": "Rua de Teste, 123",
    "field_city": "Lisboa",
    "field_postal_code": "1000-001",
    "field_country": "PT"
  }'
```

### Step 3: Check the Logs
```bash
ddev drush watchdog:show --count=20 --type=jwt_auth_api
```

Look for log messages like:
- "Profile update request received for user X with data: ..."
- "Found X customer profiles for user X"
- "Creating new customer profile for user X" or "Updating existing customer profile X"
- "Saving customer profile with address: ..."
- "Customer profile saved successfully"

### Step 4: Verify in Drupal Admin
1. Go to: https://darkcyan-stork-408379.hostingersite.com/admin/people/profiles
2. Filter by "Customer" profile type
3. Find the user's profile
4. You should see their address properly formatted

## Troubleshooting

### No Customer Profile Created
**Check logs:**
```bash
ddev drush watchdog:show --type=jwt_auth_api --severity=Error
```

**Common issues:**
1. No address data sent: Check log "No address data in update request"
2. Error creating profile: Check error message in logs
3. Missing fields: Ensure all address fields are sent from frontend

### Profile Created but Address Empty
1. Check the data being sent matches the expected format
2. Verify the country_code is a valid 2-letter ISO code (e.g., "PT", "ES", "FR")
3. Check logs for the exact address data being saved

### Frontend Not Sending Data
Check what the frontend is actually sending:
```bash
ddev drush watchdog:show --type=jwt_auth_api | grep "Profile update request received"
```

This will show you the exact JSON data being received.

## Address Field Structure

The customer profile uses Drupal's Address module which stores:
- `country_code`: 2-letter ISO country code (e.g., "PT")
- `address_line1`: Street address
- `locality`: City
- `postal_code`: Postal/ZIP code
- `given_name`: First name
- `family_name`: Last name

## API Response

The `/api/auth/user` endpoint now includes address book information:

```json
{
  "uid": 1,
  "name": "username",
  ...
  "address_book": {
    "country_code": "PT",
    "address_line1": "Rua de Teste, 123",
    "address_line2": null,
    "locality": "Lisboa",
    "postal_code": "1000-001",
    "given_name": "Test",
    "family_name": "User"
  }
}
```

## Next Steps

1. **Test with actual frontend**: Have the frontend send a profile update with address information
2. **Monitor logs**: Watch for any errors or issues
3. **Verify in admin**: Check that customer profiles are being created with proper addresses
4. **Frontend integration**: Update frontend to handle the `address_book` field in responses

## Quick Test Script

Use the provided test script:
```bash
./test_address_update.php YOUR_JWT_TOKEN
```

This will simulate a frontend request and show you the result.

## Production Deployment & Debugging Guide

### Step 1: Deploy to Hostinger

SSH into Hostinger and run:
```bash
cd domains/darkcyan-stork-408379.hostingersite.com/public_html
git pull origin main
vendor/bin/drush cr
```

### Step 2: Check What Data the Frontend is Sending

After your frontend tries to update a profile, check the logs:

```bash
vendor/bin/drush watchdog:show --count=5 --type=jwt_auth_api
```

Look for the line that says:
```
"Profile update request received for user X with data: {..."
```

This will show you EXACTLY what data the frontend is sending.

### Step 3: Common Issues & Solutions

#### Issue 1: "No address data in update request"
**Problem:** Frontend isn't sending address fields
**Solution:** Check that frontend is sending `field_address`, `field_city`, `field_postal_code`, or `field_country`

#### Issue 2: "Failed to create customer profile: ..."
**Problem:** Error in the address data format
**Solution:** Check the error message in logs. Common issues:
- Invalid country code (must be 2-letter ISO: PT, ES, FR, etc.)
- Missing required address fields

#### Issue 3: Profile updates but no customer profile created
**Problem:** Address fields might have different names in frontend
**Solution:** Check the log to see what field names the frontend is actually using

### Step 4: Monitor in Real-Time

While testing the frontend, run this in SSH:
```bash
vendor/bin/drush watchdog:tail --type=jwt_auth_api
```

This will show logs as they happen.

### Step 5: Check What the Frontend Error Says

When the frontend shows "it fails", check:
1. **What HTTP status code?** (400, 401, 500?)
2. **What error message?**
3. **Browser console errors?**

Share this information so we can diagnose the exact issue.

### Quick Commands Reference

```bash
# Pull latest code
git pull origin main

# Clear cache (always after pulling)
vendor/bin/drush cr

# View recent logs
vendor/bin/drush watchdog:show --count=20 --type=jwt_auth_api

# View errors only
vendor/bin/drush watchdog:show --type=jwt_auth_api --severity=Error

# Monitor real-time
vendor/bin/drush watchdog:tail --type=jwt_auth_api

# Check customer profiles exist
vendor/bin/drush sqlq "SELECT COUNT(*) as total FROM profile WHERE type='customer'"

# View profiles table
vendor/bin/drush sqlq "SELECT id, type, uid FROM profile ORDER BY id DESC LIMIT 10"
```

### What to Share for Debugging

Please share:
1. The log entry showing "Profile update request received..." with the data
2. Any error messages from the logs
3. The HTTP response the frontend receives
4. What the frontend code is sending (the request payload)

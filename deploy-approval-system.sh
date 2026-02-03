#!/bin/bash
# Deploy Approval System to Production

echo "📋 Deploying Form Submission Approval System"
echo "=============================================="

# Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
git pull origin main

# Add database columns
echo "🗄️  Adding database columns..."
vendor/drush/drush/drush sqlq "ALTER TABLE dynamic_form_submission ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) DEFAULT 'pending';" 2>/dev/null || echo "Column approval_status already exists"
vendor/drush/drush/drush sqlq "ALTER TABLE dynamic_form_submission ADD COLUMN IF NOT EXISTS approval_note LONGTEXT;" 2>/dev/null || echo "Column approval_note already exists"
vendor/drush/drush/drush sqlq "ALTER TABLE dynamic_form_submission ADD COLUMN IF NOT EXISTS approval_date INT(11);" 2>/dev/null || echo "Column approval_date already exists"

# Clear cache
echo "🧹 Clearing caches..."
vendor/drush/drush/drush cr

echo ""
echo "✅ Approval system deployment complete!"
echo ""
echo "Features Added:"
echo "  ✓ Admin can approve/deny form submissions"
echo "  ✓ Admin can add notes to decisions"
echo "  ✓ Status badges (⏳ Pending, ✅ Approved, ❌ Denied)"
echo "  ✓ Frontend API endpoint: /api/recent-activity"
echo "  ✓ Automatic frontend updates when admin changes status"
echo ""
echo "Backend: Visit /admin/content/formularios-dinamicos/submissions"
echo "Frontend API: GET /api/recent-activity"
echo ""

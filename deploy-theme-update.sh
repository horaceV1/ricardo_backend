#!/bin/bash
# Deploy theme update to Hostinger production

echo "🎨 Deploying Ricardo Admin Theme to Production"
echo "=============================================="

# Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
git pull origin main

# Import configuration
echo "📋 Importing configuration..."
vendor/drush/drush/drush config:import -y

# Clear cache
echo "🧹 Clearing caches..."
vendor/drush/drush/drush cr

# Check theme status
echo "🔍 Verifying theme installation..."
vendor/drush/drush/drush status theme

echo ""
echo "✅ Theme deployment complete!"
echo "🌐 Visit your admin dashboard to see the new design"
echo ""
echo "Theme Features:"
echo "  ✓ Auto dark mode (adapts to system preferences)"
echo "  ✓ Horizontal toolbar for better space usage"
echo "  ✓ Medium layout density for optimal readability"
echo "  ✓ Professional blue accent color"
echo "  ✓ Sticky action buttons"
echo "  ✓ Enhanced form elements"
echo "  ✓ Smooth scroll-to-top button"
echo "  ✓ Modern card designs"
echo ""

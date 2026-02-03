#!/bin/bash
# Deploy script for Hostinger production

echo "========================================="
echo "Deploying to Hostinger Production"
echo "========================================="
echo ""

echo "Run these commands on your Hostinger SSH:"
echo ""
echo "cd domains/darkcyan-stork-408379.hostingersite.com/public_html"
echo "git pull origin main"
echo "vendor/bin/drush cr"
echo ""
echo "Then check the logs:"
echo "vendor/bin/drush watchdog:show --count=20 --type=jwt_auth_api"
echo ""
echo "========================================="
echo ""
echo "To monitor logs in real-time after deployment:"
echo "vendor/bin/drush watchdog:tail --type=jwt_auth_api"
echo ""
echo "========================================="

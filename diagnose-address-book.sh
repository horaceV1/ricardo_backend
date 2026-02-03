#!/bin/bash
# Diagnostic script for address book issues

# Find drush on the system
if [ -f "vendor/drush/drush/drush" ]; then
    DRUSH="vendor/drush/drush/drush"
elif [ -f "vendor/bin/drush" ]; then
    DRUSH="vendor/bin/drush"
elif [ -f "bin/drush" ]; then
    DRUSH="bin/drush"
elif command -v drush &> /dev/null; then
    DRUSH="drush"
else
    echo "ERROR: Drush not found!"
    echo "Please run this from: domains/darkcyan-stork-408379.hostingersite.com/public_html"
    exit 1
fi

echo "Using drush at: $DRUSH"
echo ""

echo "============================================"
echo "Address Book Diagnostic Report"
echo "============================================"
echo ""

echo "1. Checking if profile module and customer profile type exist:"
$DRUSH ev "echo 'Profile module: ' . (\\Drupal::moduleHandler()->moduleExists('profile') ? 'ENABLED' : 'DISABLED') . PHP_EOL;"
$DRUSH ev "\$types = \\Drupal::entityTypeManager()->getStorage('profile_type')->loadMultiple(); foreach (\$types as \$type) { echo '  - ' . \$type->id() . ' (' . \$type->label() . ')' . PHP_EOL; }"
echo ""

echo "2. Counting existing profiles by type:"
$DRUSH sqlq "SELECT type, COUNT(*) as count FROM profile GROUP BY type"
echo ""

echo "3. Checking recent JWT auth API logs:"
$DRUSH watchdog:show --count=15 --type=jwt_auth_api
echo ""

echo "4. Checking for errors:"
$DRUSH watchdog:show --count=5 --type=jwt_auth_api --severity=Error
echo ""

echo "5. Checking recent profile creations:"
$DRUSH sqlq "SELECT id, type, uid, created FROM profile ORDER BY created DESC LIMIT 10"
echo ""

echo "6. Checking if customer profiles have address data:"
$DRUSH ev "\$storage = \\Drupal::entityTypeManager()->getStorage('profile'); \$profiles = \$storage->loadByProperties(['type' => 'customer']); echo 'Total customer profiles: ' . count(\$profiles) . PHP_EOL; \$recent = array_slice(\$profiles, -5, 5, true); foreach (\$recent as \$p) { echo 'Profile ID ' . \$p->id() . ' (User ' . \$p->getOwnerId() . '): '; if (\$p->hasField('address')) { \$addr = \$p->get('address'); if (\$addr->isEmpty()) { echo 'EMPTY ADDRESS' . PHP_EOL; } else { \$a = \$addr->first(); echo \$a->country_code . ' - ' . (\$a->address_line1 ?: 'no street') . ', ' . (\$a->locality ?: 'no city') . PHP_EOL; } } else { echo 'NO ADDRESS FIELD' . PHP_EOL; } }"
echo ""

echo "============================================"
echo "Diagnostic Complete"
echo "============================================"

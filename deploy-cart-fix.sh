#!/bin/bash

# Deployment script for Hostinger production
# Upload the updated CommerceCartApiController.php to production

echo "Deploying cart controller to production..."

# SSH details (update with your credentials)
SSH_PORT="65002"
SSH_USER="u821792182"
SSH_HOST="147.93.92.158"
REMOTE_PATH="/home/u821792182/public_html/web/modules/custom/formulario_candidatura_dinamico/src/Controller/"

# Local file path
LOCAL_FILE="web/modules/custom/formulario_candidatura_dinamico/src/Controller/CommerceCartApiController.php"

# Upload file
echo "Uploading $LOCAL_FILE..."
scp -P $SSH_PORT "$LOCAL_FILE" "$SSH_USER@$SSH_HOST:$REMOTE_PATH"

if [ $? -eq 0 ]; then
    echo "✓ File uploaded successfully"
    
    # Clear Drupal cache via SSH
    echo "Clearing Drupal cache on production..."
    ssh -p $SSH_PORT "$SSH_USER@$SSH_HOST" "cd /home/u821792182/public_html && vendor/bin/drush cr"
    
    if [ $? -eq 0 ]; then
        echo "✓ Cache cleared successfully"
        echo "✓ Deployment complete!"
    else
        echo "✗ Failed to clear cache"
    fi
else
    echo "✗ Failed to upload file"
fi

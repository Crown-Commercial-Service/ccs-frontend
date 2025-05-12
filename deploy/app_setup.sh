#!/bin/bash
# System init/update

echo "Starting codedeploy app_setup.sh ..."

DEPLOY_PATH="/deploy"
WEB_PREV="/var/www.prev"
WEB_CURRENT="/var/www"

# Revert web files in the event of a deployment error
function rollback {
    echo -n "!!! Rolling back deployment state: "
    if [ -e "$WEB_PREV" ]; then
        if [ -e "$WEB_CURRENT" ]; then
            sudo rm -rf "$WEB_CURRENT"
        fi

        sudo mv -f "$WEB_PREV" "$WEB_CURRENT"
    fi

    echo "done."
    exit 1
}

# Move the existing web root out the way & recreate
if [ -e "$WEB_CURRENT" ]; then
    echo "> Moving existing web deployment out the way..."
    (sudo mv -f "$WEB_CURRENT" "$WEB_PREV" &&
        sudo mkdir -p "$WEB_CURRENT" &&
        sudo chown root:root "$WEB_CURRENT"
    ) || rollback
fi

echo "> Moving httpd.conf..."
    sudo mv -f "$DEPLOY_PATH/deploy/files/httpd.conf" /etc/httpd/conf/httpd.conf

# Prepare & move files into place
echo "> Preparing new web deployment files..."
(sudo rm -f "$DEPLOY_PATH/appspec.yml" &&
    sudo rm -rf "$DEPLOY_PATH/deploy" &&
    sudo mv -f "$DEPLOY_PATH/.env" "$DEPLOY_PATH/.env.test" "$DEPLOY_PATH/"* "$WEB_CURRENT" &&
    sudo ln -s "$WEB_CURRENT/public" "$WEB_CURRENT/html"
) || rollback

# Set permissions
echo "> Setting web deployment permissions..."
(sudo chown -R ec2-user:ec2-user /var/www &&
    sudo chown -R apache:ec2-user "$WEB_CURRENT/var" &&
    sudo chmod -R og+w "$WEB_CURRENT/var" &&
    sudo chmod 640 "$WEB_CURRENT/.env" &&
    sudo chgrp apache "$WEB_CURRENT/.env"
) || rollback

# Cleanup
echo "> Running cleanup..."
if [ -e "$DEPLOY_PATH" ]; then
    echo "> > Deploy path..."
    sudo rm -rf "$DEPLOY_PATH"
fi

if [ -e "$WEB_PREV" ]; then
    echo "> > Previous web deployment..."
    sudo rm -rf "$WEB_PREV"
fi

echo "Codedeploy app_setup.sh complete."

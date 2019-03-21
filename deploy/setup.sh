#!/bin/bash
# System init/update

echo "Starting codedeploy setup.sh ..."

DEPLOY_PATH="/deploy"
WEB_PREV="/var/www.prev"
WEB_CURRENT="/var/www"

# Revert web files in the event of a deployment error
function rollback {
    echo -n "Rolling back deployment state: "
    if [ -e "$WEB_PREV" ]; then
        if [ -e "$WEB_CURRENT" ]; then
            sudo rm -rf "$WEB_CURRENT"
        fi

        sudo mv -f "$WEB_PREV" "$WEB_CURRENT"
    fi

    echo "done."
    exit 1
}

# Update existing software
cd /root
sudo yum update -y || rollback

# Ensure we have a clean webroot to deploy into
# @TODO check we can recover an in-place instance (move web root out the way rather than trashing it)

# Move the existing web root out the way & recreate
if [ -e "$WEB_CURRENT" ]; then
    sudo mv -f "$WEB_CURRENT" "$WEB_PREV"
    sudo mkdir -p "$WEB_CURRENT"
    sudo chown root:root "$WEB_CURRENT"
fi

# Prepare & move files into place
sudo rm -f "$DEPLOY_PATH/appspec.yml"
sudo rm -rf "$DEPLOY_PATH/deploy"
sudo mv -f "$DEPLOY_PATH/.env" "$DEPLOY_PATH/.env.test" "$DEPLOY_PATH/"* "$WEB_CURRENT"
sudo ln -s "$WEB_CURRENT/public" "$WEB_CURRENT/html"

# Set permissions
sudo chown -R ec2-user:ec2-user /var/www
sudo chown -R apache:ec2-user "$WEB_CURRENT/var/log"
sudo chmod -R og+w "$WEB_CURRENT/var/log"

# Cleanup
if [ -e "$DEPLOY_PATH" ]; then
    sudo rm -rf "$DEPLOY_PATH"
fi

if [ -e "$WEB_PREV" ]; then
    sudo rm -rf "$WEB_PREV"
fi

echo "Codedeploy setup.sh complete."

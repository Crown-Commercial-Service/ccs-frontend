#!/bin/bash
# System init/update

echo "Starting codedeploy server_setup.sh ..."

SCRIPTDIR=$(dirname $0)
FIRST_RUN_PATH="/codedeploy.server_setup"

echo "> Updating system software..."
sudo yum update -y

if [ ! -e "$FIRST_RUN_PATH" ]; then
    echo "> Running once-only deployment tasks..."

    echo "> > Installing awslogs service..."
    sudo yum install -y awslogs

    echo "> > chown'ing awslogs config files..."
    sudo chown root:root \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf"

    echo "> > chmod'ing awslogs config files..."
    sudo chmod 640 \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf"

    echo "> > Movinging awslogs config files..."
    sudo mv -f \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf" \
        /etc/awslogs/

    echo "> > Adding additional package repos..."
    sudo yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
    sudo yum -y install https://centos7.iuscommunity.org/ius-release.rpm

    echo "> > Installing web packages..."
    sudo yum -y install \
        httpd \
        mod_php73 \
        php73-cli \
        php73-mysqlnd.x86_64 \
        php73-opcache \
        php73-xml \
        php73-gd \
        php73-devel \
        php73-intl \
        php73-mbstring \
        php73-bcmath \
        php73-soap \
        php73-json

    echo "> > Moving httpd.conf..."
    sudo mv -f "$SCRIPTDIR/files/httpd.conf" /etc/httpd/conf/httpd.conf

    echo "> > chown'ing php config file..."
    sudo chown root:root \
        "$SCRIPTDIR/$DEPLOYMENT_TYPE/files/99-custom.ini"
        
    echo "> > chmod'ing php config file..."
    sudo chmod 644 \
        "$SCRIPTDIR/$DEPLOYMENT_TYPE/files/99-custom.ini"

    echo "> > Moving php config file..."
    sudo mv -f \
        "$SCRIPTDIR/$DEPLOYMENT_TYPE/files/99-custom.ini" \
        /etc/php.d/
        
    echo "> > chown'ing logrotate config files..."
    sudo chown root:root \
        "$SCRIPTDIR/files/applogs"

    echo "> > chmod'ing logrotate config files..."
    sudo chmod 644 \
        "$SCRIPTDIR/files/applogs"

    echo "> > Moving logrotate config files..."
    sudo mv -f \
        "$SCRIPTDIR/files/applogs" \
        /etc/logrotate.d/

    echo "> > Marking first deployment tasks as completed..."
    sudo touch "$FIRST_RUN_PATH"
fi

echo "Codedeploy server_setup.sh complete."

#!/bin/bash
# System init/update

echo "Starting codedeploy server_setup.sh ..."

SCRIPTDIR=$(dirname $0)
FIRST_RUN_PATH="/codedeploy.server_setup"

echo "> Updating system software..."
sudo yum update -y

echo "> Set timezone..."
    sudo rm -f /etc/sysconfig/clock
    sudo mv -f \
        "$SCRIPTDIR/files/clock" \
        /etc/sysconfig/clock
    sudo ln -sf /usr/share/zoneinfo/Europe/London /etc/localtime

if [ ! -e "$FIRST_RUN_PATH" ]; then
    echo "> Running once-only deployment tasks..."

    echo "> > Installing awslogs service..."
    #sudo yum install -y awslogs
    sudo yum install -y amazon-cloudwatch-agent
    sudo systemctl enable amazon-cloudwatch-agent

    echo "> > chown'ing awslogs config files..."
    sudo chown root:root \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf"

    echo "> > chmod'ing awslogs config files..."
    sudo chmod 640 \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf"

    echo "> > Moving awslogs config files..."
    sudo mv -f \
        "$SCRIPTDIR/files/awscli.conf" \
        "$SCRIPTDIR/files/awslogs.conf" \
        /etc/awslogs/

    # echo "> > Adding additional package repos..."
    # sudo yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
    # sudo yum install -y https://repo.ius.io/ius-release-el$(rpm -E '%{rhel}').rpm

    # echo "> > Removing PHP 7.4..."
    # sudo yum remove -y php*
    # sudo amazon-linux-extras disable php7.4

    # echo "> > Installing web packages..."
    # sudo amazon-linux-extras enable php8.2
    sudo yum -y install \
        httpd \
        php8.2 \
        php-mysqlnd.x86_64 \
        php-opcache.x86_64 \
        php-xml.x86_64 \
        php-gd.x86_64 \
        php-devel.x86_64 \
        php-intl.x86_64 \
        php-mbstring.x86_64 \
        php-bcmath.x86_64 \
        php-soap.x86_64 \
        php-json.x86_64 

    echo "> > Moving httpd.conf..."
    sudo mv -f "$SCRIPTDIR/files/httpd.conf" /etc/httpd/conf/httpd.conf

    echo "> > chown'ing php config file..."
    sudo chown root:root \
        "$SCRIPTDIR/files/99-custom.ini"
        
    echo "> > chmod'ing php config file..."
    sudo chmod 644 \
        "$SCRIPTDIR/files/99-custom.ini"

    echo "> > Moving php config file..."
    sudo mv -f \
        "$SCRIPTDIR/files/99-custom.ini" \
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

    echo "> > chown'ing cache_clear..."
    sudo chown root:root "$SCRIPTDIR/files/cache_clear"

    echo "> > chmod'ing cache_clear..."
    sudo chmod 644 "$SCRIPTDIR/files/cache_clear"

    echo "> > Moving cache_clear..."
    sudo mv -f \
        "$SCRIPTDIR/files/cache_clear" \
        /etc/cron.d/

    echo "> > Marking first deployment tasks as completed..."
    sudo touch "$FIRST_RUN_PATH"
fi

echo "Codedeploy server_setup.sh complete."

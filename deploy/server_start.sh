#!/bin/bash
# Start/enable application-related services

echo "Starting codedeploy server_start.sh ..."

sudo systemctl is-enabled --quiet httpd \
    || sudo systemctl enable httpd

sudo systemctl is-active --quiet httpd \
    || sudo systemctl start httpd

echo "Codedeploy server_start.sh complete."

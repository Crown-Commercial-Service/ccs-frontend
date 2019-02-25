#!/bin/bash
# Stop application-related services

echo "Starting codedeploy server_stop.sh ..."

sudo systemctl is-active --quiet httpd \
    && sudo systemctl stop httpd.service

echo "Codedeploy server_stop.sh complete."

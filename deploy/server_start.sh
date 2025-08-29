#!/bin/bash
# Start/enable application-related services

echo "Starting codedeploy server_start.sh ..."

SERVICES=(
    "amazon-cloudwatch-agent"
    "httpd.service"
)

echo "> Starting services..."
for SERVICE in "${SERVICES[@]}"; do
    echo -n "> > Enabling & starting service [$SERVICE]: "

    sudo systemctl is-enabled --quiet "$SERVICE" \
        || sudo systemctl enable "$SERVICE"

    sudo systemctl is-active --quiet "$SERVICE" \
        || sudo systemctl start "$SERVICE"

    echo "done."
done

echo "Codedeploy server_start.sh complete."

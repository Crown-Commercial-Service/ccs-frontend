#!/bin/bash
# Stop application-related services

echo "Starting codedeploy server_stop.sh ..."

SERVICES=(
    "awslogsd.service"
    "httpd.service"
)

# Service will not be installed on the first run
echo "> Stopping services..."
for SERVICE in "${SERVICES[@]}"; do
    echo -n "> > Stopping service [$SERVICE]: "

    SERVICE_REGEX=$(echo "$SERVICE" | sed -e 's/\./\\./g')

    sudo systemctl list-unit-files "$SERVICE"|grep -q "^${SERVICE_REGEX}\\s"
    if [ $? -eq 0 ]; then
        sudo systemctl is-active --quiet "$SERVICE" \
            && sudo systemctl stop "$SERVICE"
    fi

    echo "done."
done

echo "Codedeploy server_stop.sh complete."

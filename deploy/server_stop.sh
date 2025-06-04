#!/bin/bash
# Stop application-related services

echo "Starting codedeploy server_stop.sh ..."

SERVICES=(
    "awslogsd.service"
    "httpd.service"
)

# Service will not be installed on the first run
echo "> Stopping services..."
echo "Step1: ${SERVICES[@]}"
for SERVICE in "${SERVICES[@]}"; do
    echo -n "> > Stopping service [$SERVICE]: "

    SERVICE_REGEX=$(echo "$SERVICE" | sed -e 's/\./\\./g')
    echo "Step2: under regex: $SERVICE_REGEX"

    #Temp Alpha Deploy Fix
    sudo yum reinstall httpd -y
    sudo systemctl start httpd
    sudo systemctl enable httpd
    echo "Step3: under my commands"

    sudo systemctl list-unit-files "$SERVICE"|grep -q "^${SERVICE_REGEX}\\s"
    echo "Step4: under list unit files"
    if [ $? -eq 0 ]; then
        echo "Step5: Now in IF" 
        sudo systemctl is-active --quiet "$SERVICE" \
            && echo "continuing just before stop." #new
            && sudo systemctl stop "$SERVICE"
            echo "Step6: Service Stopped" 
    fi

    echo "done."
done

echo "Codedeploy server_stop.sh complete."

#!/bin/bash

# Example usage: sh local_start.sh true
# Example usage: sh local_start.sh false

if [ $# -ne 1 ]; then
    echo "Do you want to start or terminate the website processes? Add 'true' or 'false' as an argument."
    exit 1
fi

if [ "$1" == "true" ]; then
    brew services start postgresql@13
    apachectl start
    Mysql.server start
    brew services start opensearch
    exit 1
elif [ "$1" == "false" ]; then
    brew services stop postgresql@13
    apachectl stop
    Mysql.server stop
    brew services stop opensearch
    exit 1
else
    echo "Invalid argument. Please use 'true' or 'false'."
    exit 1
fi

# brew services restart opensearch-dashboards
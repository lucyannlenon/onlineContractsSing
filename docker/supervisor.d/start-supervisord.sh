#!/bin/bash

echo "Starting supervisord..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf
sleep 1

echo "Starting apache..."
service apache2 start
exit 0
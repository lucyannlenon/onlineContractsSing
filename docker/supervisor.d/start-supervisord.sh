#!/bin/bash
sleep 20
# Remove stale supervisor socket file if it exists
SOCKET_FILE=/var/run/supervisor.sock

if [ -e $SOCKET_FILE ]; then
    echo "Unlinking stale socket $SOCKET_FILE"
    rm -f $SOCKET_FILE
fi

# Ensure /var/run directory exists
if [ ! -d /var/run ]; then
    mkdir -p /var/run
fi

# Start supervisord and stay running
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf

exit 0
#!/bin/bash
# Remove stale supervisor socket file if it exists
if [ -e /var/run/supervisor.sock ]; then
    echo "Unlinking stale socket /var/run/supervisor.sock"
    rm -f /var/run/supervisor.sock
fi

# Start supervisord
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

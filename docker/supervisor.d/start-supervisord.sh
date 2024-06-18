#!/bin/bash
# Verifique e remova o arquivo de socket obsoleto do supervisor, se ele existir
SOCKET_FILE=/var/run/supervisor.sock
sleep 10
echo "Chddddecking for stale socket..."
if [ -e $SOCKET_FILE ]; then
    echo "Unlinking stale socket $SOCKET_FILE"
    rm -f $SOCKET_FILE
    if [ $? -eq 0 ]; then
        echo "Successfully removed stale socket."
    else
        echo "Failed to remove stale socket."
        exit 1
    fi
else
    echo "No stale socket found."
fi

# Verifique se o diretório /var/run existe, caso contrário, crie-o
echo "Checking /var/run directory..."
if [ ! -d /var/run ]; then
    mkdir -p /var/run
    if [ $? -eq 0 ]; then
        echo "Successfully created /var/run directory."
    else
        echo "Failed to create /var/run directory."
        exit 1
    fi
fi

# Inicie o supervisord
echo "Starting supervisord..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf
if [ $? -eq 0 ]; then
    echo "Supervisord started successfully."
else
    echo "Failed to start supervisord."
    exit 1
fi
exit 0
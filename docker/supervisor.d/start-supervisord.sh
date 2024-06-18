#!/bin/bash
# Verifique e remova o arquivo de socket obsoleto do supervisor, se ele existir
SOCKET_FILE=/var/run/supervisor.sock

if [ -e $SOCKET_FILE ]; then
    echo "Unlinking stale socket $SOCKET_FILE"
    rm -f $SOCKET_FILE
fi

# Verifique se o diretório /var/run existe, caso contrário, crie-o
if [ ! -d /var/run ]; then
    mkdir -p /var/run
fi

# Inicie o supervisord
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Base image PHP 8.3 FPM (Debian bullseye)
FROM php:8.3-fpm-bullseye

# Debian bullseye went EOL: deb.debian.org/debian-security no longer serves it
# (404s on apt-get update). snapshot.debian.org has a *permanent* content gap
# for bullseye-security's pool/updates/main path specifically — several
# packages this build needs (openssl, curl, icu, libpng, libxml2-dev,
# librabbitmq, sqlite3, sudo, unzip) 404 there regardless of which snapshot
# date is picked (confirmed against two different dates), even though the
# same files are individually reachable via snapshot's by-hash /file/
# endpoint — so pinning to a snapshot date does not fix it, only dropping the
# security suite does. Pin to snapshot.debian.org's plain bullseye +
# bullseye-updates only (reliable) instead, and force the handful of runtime
# libs the base image already ships at a newer (security-patched) version
# down to the version "main" offers — their -dev packages pin an exact
# version match, so apt would otherwise refuse with "held broken packages".
# This trades the latest OS-level security patches for a working local/dev
# build only; production deploys are a separate pipeline
# (docker-compose-production.yml) untouched by this.
RUN { \
    echo 'deb http://snapshot.debian.org/archive/debian/20260915T082008Z bullseye main'; \
    echo 'deb http://snapshot.debian.org/archive/debian/20260915T082008Z bullseye-updates main'; \
    } > /etc/apt/sources.list \
 && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

RUN apt-get update && apt-get install -y --no-install-recommends --allow-downgrades \
    libcurl4=7.74.0-1.3+deb11u13 \
    libicu67=67.1-7 \
    libsqlite3-0=3.34.1-3 \
    libssl1.1=1.1.1w-0+deb11u1 \
    libxml2=2.9.10+dfsg-6.7+deb11u4 \
    unzip \
    zip \
    libcurl4-openssl-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    libmemcached-dev \
    librabbitmq-dev \
    sendmail \
    fontconfig \
    libxrender1 \
    libxext6 \
    libfreetype6 \
    libjpeg62-turbo \
    chromium \
    sudo \
    wget \
    supervisor \
 && apt-get install -y curl \
 && docker-php-ext-install \
    mysqli \
    bcmath \
    gd \
    intl \
    xml \
    curl \
    pdo_mysql \
    pdo_sqlite \
    dom \
    session \
    opcache \
    pcntl \
    zip \
    sockets \
 && pecl install amqp redis \
 && docker-php-ext-enable amqp redis \
 && curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer \
 && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash \
 && sudo apt install -y symfony-cli \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/*

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=22M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

RUN mkdir -p /app/var /app/config/certs \
    && chown -R www-data:www-data /app/var /app/config/certs

# Supervisord roda php-fpm + o consumidor do Symfony Scheduler no mesmo container
COPY supervisor.d/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor.d/php-fpm.conf /etc/supervisor/conf.d/php-fpm.conf
COPY supervisor.d/messenger-scheduler.conf /etc/supervisor/conf.d/messenger-scheduler.conf
COPY supervisor.d/messenger-async.conf /etc/supervisor/conf.d/messenger-async.conf
COPY supervisor.d/start-supervisord.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]

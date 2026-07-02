# Base image PHP 8.3 FPM (Debian bullseye)
FROM php:8.3-fpm-bullseye

RUN apt-get update && apt-get install -y \
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

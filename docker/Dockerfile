# Base image PHP 8.3 (Debian 12 bookworm)
FROM php:8.3-apache-bullseye

# Instala pacotes necessários, PHP extensions, composer e symfony-cli
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
 && pecl install amqp \
 && docker-php-ext-enable amqp \
 && curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer \
 && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash \
 && sudo apt install -y symfony-cli \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/*

COPY pakages/wkhtmltox_0.12.6.1-2.bullseye_amd64.deb /tmp/

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libcurl4-openssl-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    libjpeg62-turbo\
    wkhtmltopdf
#RUN cp /tmp/wkhtmltox/bin/wkhtmltopdf /usr/local/bin/ \
#    && chmod +x /usr/local/bin/wkhtmltopdf \
#    && rm -rf /tmp/wkhtmltox*

## Configuração do supervisor
RUN mkdir -p /var/log/supervisor

COPY supervisor.d/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor.d/start-supervisord.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Script de entrada
CMD ["/usr/local/bin/start.sh"]

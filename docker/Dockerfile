# Base image
FROM php:8.3-apache


# Run apt update and install some dependancies needed for docker-php-ext
RUN apt update && apt install -y apt-utils libcurl4-openssl-dev sendmail mariadb-client pngquant unzip zip libpng-dev libmcrypt-dev git \
  curl libicu-dev libxml2-dev libzip-dev openssl libssl-dev libcurl4  libsqlite3-dev libsqlite3-0 memcached snmpd libmemcached-tools libmemcached-dev




#RUN apt install sudo
#RUN apt install -y imagick 
#RUN apt install -y php8.3-imagick

#pecl install memcached
#RUN pecl install memcached

# Install PHP extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install gd
RUN docker-php-ext-install intl
RUN docker-php-ext-install xml
RUN docker-php-ext-install curl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install dom
RUN docker-php-ext-install session
RUN docker-php-ext-install opcache
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install zip
RUN docker-php-ext-install sockets
#RUN docker-php-ext-enable memcached

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
--install-dir=/usr/bin --filename=composer

# install memcached
#RUN git clone https://github.com/php-memcached-dev/php-memcached /usr/src/php/ext/memcached
#RUN cd /usr/src/php/ext/memcached && git checkout -b php7 origin/php7
#RUN docker-php-ext-configure memcached
#RUN docker-php-ext-install memcached




RUN apt-get -y install librabbitmq-dev
RUN pecl install amqp

RUN docker-php-ext-enable amqp

#RUN pecl install xdebug
#RUN docker-php-ext-enable xdebug

#COPY 90-xdebug.ini "${PHP_INI_DIR}/conf.d"

## teste
RUN apt install -y  wget  libssh2-1-dev  libssh2-1

#RUN wget https://pecl.php.net/get/ssh2-1.2.tgz
#
#RUN  tar -xzf ssh2-1.2.tgz && rm -rf ssh2-1.2.tgz &&  cd ssh2-1.2 && phpize \
#     && ./configure && make && make install  && cd .. &&  rm -rf ssh2-1.2
#
#RUN echo "extension=ssh2.so" > ${PHP_INI_DIR}/conf.d/docker-php-ext-ssh2.ini

#INSTALL symfony comment im prodution
RUN apt install -y sudo
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash

RUN sudo apt install symfony-cli

RUN sudo apt install -y wkhtmltopdf

RUN sudo apt-get install -y libssh2-1

RUN sudo pecl install ssh2

RUN docker-php-ext-enable ssh2

RUN sudo apt-get install  -y supervisor

RUN mkdir -p /var/log/supervisor

COPY supervisor.d/supervisord.conf /etc/supervisor/supervisord.conf

#CMD ["/usr/bin/supervisord"]

COPY supervisor.d/start-supervisord.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh
#
##
### Command to run the startup script
CMD [" /usr/local/bin/start.sh"]
FROM php:8.4-fpm
ARG TIMEZONE
ARG AWS_ID
ARG AWS_KEY

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    cron \
    acl \
    mariadb-client \
    ffmpeg \
    libpng-dev \
    libfreetype6-dev \
    libjpeg-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    libxrender1 \
    && rm -rf /var/lib/apt/lists/*



# Sources
#COPY ../../src /var/www/html/
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini
RUN "date"

# Type docker-php-ext-install to see available extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql gd && docker-php-ext-install zip

# install xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
# RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "display_startup_errors = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "display_errors = on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.discover_client_host=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.client_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "opcache.memory_consumption=256" >>/usr/local/etc/php/php.ini
# RUN echo "opcache.max_accelerated_files=20000" >>/usr/local/etc/php/php.ini
# RUN echo "opcache.validate_timestamps=0" >>/usr/local/etc/php/php.ini
# RUN echo "realpath_cache_size=4096K" >>/usr/local/etc/php/php.ini
# RUN echo "realpath_cache_ttl=600" >>/usr/local/etc/php/php.ini
# RUN echo "memory_limit = 30G" >>/usr/local/etc/php/php.ini
# RUN echo "max_execution_time = 3600" >>/usr/local/etc/php/php.ini
# RUN echo "post_max_size = 30G" >>/usr/local/etc/php/php.ini
# RUN echo "upload_max_filesize = 30G" >>/usr/local/etc/php/php.ini


# RUN echo "request_terminate_timeout = 3600" >>/usr/local/etc/php-fpm.d/www.conf
# RUN echo "pm.max_children = 500" >>/usr/local/etc/php-fpm.d/www.conf
# RUN echo "pm.start_servers = 20" >>/usr/local/etc/php-fpm.d/www.conf
# RUN echo "pm.min_spare_servers = 10" >>/usr/local/etc/php-fpm.d/www.conf
# RUN echo "pm.max_spare_servers = 20" >>/usr/local/etc/php-fpm.d/www.conf
#RUN echo "pm.max_requests = 5000" >>/usr/local/etc/php-fpm.d/www.conf


# RUN sed -i 's/;request_terminate_timeout = 0/request_terminate_timeout = 3600/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/pm.max_children = 5/pm.max_children = 500/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/pm.start_servers = 2/pm.start_servers = 20/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 10/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 20/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/;pm.max_requests = 500/pm.max_requests = 5000/' /usr/local/etc/php-fpm.d/www.conf


# RUN sed -i 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9003/' /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i 's/listen = 9000/listen = 9003/' /usr/local/etc/php-fpm.d/zz-docker.conf


RUN touch /var/log/app-cron.log
ADD cronjob /etc/cron.d/app-cron
RUN chmod 0644 /etc/cron.d/app-cron
RUN crontab /etc/cron.d/app-cron

RUN echo 'alias sf="php bin/console"' >> ~/.bashrc
RUN echo 'alias ll="la -la"' >> ~/.bashrc

RUN mkdir /var/www/.aws
RUN echo "[default]" >>/var/www/.aws/credentials
RUN echo "aws_access_key_id=${AWS_ID}" >>/var/www/.aws/credentials
RUN echo "aws_secret_access_key=${AWS_KEY}" >>/var/www/.aws/credentials

RUN mkdir /root/.aws/
RUN echo "[default]" >>/root/.aws/credentials
RUN echo "aws_access_key_id=${AWS_ID}" >>/root/.aws/credentials
RUN echo "aws_secret_access_key=${AWS_KEY}" >>/root/.aws/credentials

WORKDIR /var/www/html


# Backup
#COPY ./backup_scripts/* /backup/scripts/
#RUN chmod +x /backup/scripts/*

# Command script to initialize app at first launch
COPY ./init.sh /init.sh
RUN chmod +x /init.sh

#CMD php-fpm

CMD ["/init.sh"]

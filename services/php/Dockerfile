FROM php:7.2-apache-stretch
ARG TIMEZONE

RUN apt-get update && apt-get install -y gnupg \
    && curl -sL https://deb.nodesource.com/setup_8.x | bash - \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update && apt-get install -y \
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
    --no-install-recommends && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql xml zip \
    && echo '' | pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && sed -ri -e 's!/var/www/html!/var/www/html/web!g' /etc/apache2/sites-available/*.conf \
    && sed -i -e '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && a2enmod expires \
    && echo 'ExpiresActive On \n\
    ExpiresByType image/gif "access 1 month" \n\
    ExpiresByType image/jpg "access 1 month" \n\
    ExpiresByType image/jpeg "access 1 month" \n\
    ExpiresByType image/png "access 1 month" \n\
    ExpiresByType text/css "access 1 month" \n\
    ExpiresByType text/js "access 1 month" \n\
    ExpiresByType application/javascript "access 1 month"\n'\
    >> /etc/apache2/sites-available/default.conf
# Install Composer

# Sources
COPY ./src /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini
RUN "date"


# Web server settings and security policies
COPY services/php/php.ini /usr/local/etc/php/
COPY services/php/apache2.conf /etc/apache2/

# install xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=\"PHPSTORM\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN echo 'alias sf3="php bin/console"' >> ~/.bashrc

# Backup
COPY services/php/backup_scripts/* /backup/scripts/
RUN chmod +x /backup/scripts/*

# Setting up current dir
WORKDIR /var/www/html

# Initialization script
COPY  services/php/init.sh /init.sh
RUN chmod +x /init.sh
# Command script to initialize app at first launch
CMD ["/init.sh"]

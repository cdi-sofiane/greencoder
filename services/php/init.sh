#bin/bash
# init script to change workdir rights (files will be created with www-data user group

if [ ! -f /flag ]; then

    # increasing memory for composer, and possibly doctrine, provisionally
    sed -i 's/256M/2G/g' /usr/local/etc/php/php.ini
    composer install --no-interaction --no-ansi --optimize-autoloader --apcu-autoloader --classmap-authoritative
    
    php bin/console doctrine:schema:update --force
    if ! mysql -u ${MYSQL_USER} --host database -p${MYSQL_PASSWORD} -e "USE ${MYSQL_DATABASE}; SELECT 1 FROM user LIMIT 1;" 2> /dev/null; then
        php bin/console doctrine:schema:update --force
        php bin/console doctrine:fixtures:load -n
    fi
    echo 'changing permissions on workdir'
    chown -R www-data:www-data /var/www/html
    chmod -R g+s /var/www/html
    echo "changing default access rights to workdir"
    setfacl -Rdm g:www-data:rwx /var/www/html
    touch /flag
    echo 'flag put'

    sed -i 's/2G/256M/g' /usr/local/etc/php/php.ini
    php bin/console assets:install --symlink web
fi


# Initial CMD from the official php image
apache2-foreground
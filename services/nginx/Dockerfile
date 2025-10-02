FROM debian:bullseye

ARG APP_CONF
ARG APP_DOMAIN

RUN apt-get update && apt-get install -y --force-yes\
    nginx

ADD nginx.conf /etc/nginx/

ADD $APP_CONF /etc/nginx/sites-available/

RUN ln -s /etc/nginx/sites-available/$APP_CONF /etc/nginx/sites-enabled/$APP_CONF
RUN rm /etc/nginx/sites-enabled/default

# RUN echo "upstream php-upstream { server php:9003; }" > /etc/nginx/conf.d/upstream.conf
# RUN if [ "$APP_DOMAIN" = "app-l03.greenencoder.com" ] ; then echo "$APP_DOMAIN"; else echo "non $APP_DOMAIN"; fi
RUN if [ "$APP_DOMAIN" = "app-l03.greenencoder.com" ] ; then sed -i 's/fastcgi_pass php-upstream/fastcgi_pass php-fpm:9000/' /etc/nginx/sites-available/$APP_CONF; else sed -i 's/fastcgi_pass php-upstream/fastcgi_pass php:9000/' /etc/nginx/sites-available/$APP_CONF; fi
# RUN sed -i 's/fastcgi_pass php-upstream/fastcgi_pass php-fpm:9000/' /etc/nginx/sites-available/$APP_CONF

RUN usermod -u 1000 www-data

CMD ["nginx"]

EXPOSE 80
EXPOSE 443

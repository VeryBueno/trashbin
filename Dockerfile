FROM php:fpm
WORKDIR /var/www/html
COPY --chown=www-data:www-data ./composer.json ./composer.lock ./
COPY --chown=www-data:www-data src ./src
COPY --chown=www-data:www-data views ./views
COPY --chown=www-data:www-data web ./web
RUN chgrp www-data . && chown www-data . && \
    apt-get update && \
    apt-get install -y --no-install-recommends git zip && \
    (curl --silent --show-error https://getcomposer.org/installer | php) && \
    php composer.phar update --no-dev

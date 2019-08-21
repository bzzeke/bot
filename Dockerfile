ARG arch=amd64
FROM ${arch}/php:7.2.3-fpm-alpine3.7
WORKDIR /app
VOLUME ["/app", "/etc/ssl/acme", "/etc/acme"]

USER root

RUN apk update
RUN apk add --no-cache nginx supervisor
RUN openssl dhparam -dsaparam -out /etc/nginx/dhparam.pem 4096
RUN docker-php-ext-install -j$(nproc) sockets
RUN mkdir -p /run/nginx
RUN mkdir -p /etc/ssl/le

ADD config /
RUN chmod 755 /etc/periodic/daily/clean_tmp

# install composer
RUN EXPECTED_COMPOSER_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig) && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '${EXPECTED_COMPOSER_SIGNATURE}') { echo 'Composer.phar Installer verified'; } else { echo 'Composer.phar Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

EXPOSE 80 443

CMD ["/run.sh"]

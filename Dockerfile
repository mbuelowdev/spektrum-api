FROM debian:trixie

RUN apt update -y && apt install -y nginx curl zip unzip php8.4-fpm php8.4-mbstring php8.4-bcmath php8.4-pgsql php8.4-gd php8.4-xml php8.4-zip php8.4-iconv php8.4-ctype php8.4-pgsql php8.4-intl php8.4-curl php8.4-sqlite3

# Composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/spektrum

COPY docker/config/nginx/site-spektrum.conf /etc/nginx/sites-available/spektrum.conf
RUN chmod 0744 /etc/nginx/sites-available/spektrum.conf
RUN rm /etc/nginx/sites-enabled/default
RUN ln -s /etc/nginx/sites-available/spektrum.conf /etc/nginx/sites-enabled/spektrum.conf

WORKDIR /var/www/spektrum

RUN composer install

RUN chown -R www-data:www-data /var/www/spektrum

# Warum up the cache
RUN php bin/console cache:warmup

# Create logging pipe file
RUN mkdir -p /var/log
RUN touch /var/log/php-to-stdout-pipe
RUN chmod 0777 /var/log/php-to-stdout-pipe

# Startup script
COPY ./docker/startup.sh /startup.sh
CMD ["/bin/bash", "/startup.sh"]

EXPOSE 80

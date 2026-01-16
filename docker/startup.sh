#!/bin/bash

# Start PHP-FPM (non-blocking) (this passes env variables)
/etc/init.d/php8.4-fpm start

# Start nginx (non-blocking)
/usr/sbin/nginx

# Blocking call to logging pipe file
exec tail -f /var/log/php-to-stdout-pipe

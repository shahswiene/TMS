FROM php:8.1-fpm

# Install necessary PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install additional dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Set recommended PHP.ini settings
RUN { \
    echo 'expose_php = Off'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'max_execution_time = 30'; \
    echo 'memory_limit = 128M'; \
    echo 'post_max_size = 20M'; \
    echo 'upload_max_filesize = 10M'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.use_only_cookies = 1'; \
} > /usr/local/etc/php/conf.d/security.ini

# Copy application files
COPY ./public/ /var/www/html/

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Copy and run generate_ssl.sh
COPY ./generate_ssl.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/generate_ssl.sh
RUN mkdir -p /etc/ssl && /usr/local/bin/generate_ssl.sh

# Adjust permissions on SSL certificates
RUN chmod 644 /etc/ssl/cert.pem /etc/ssl/key.pem

# Debug: List contents of /etc/ssl
RUN ls -la /etc/ssl

# Debug: Check certificate content
RUN openssl x509 -in /etc/ssl/cert.pem -text -noout

# Expose port 9000 for PHP-FPM
EXPOSE 9000
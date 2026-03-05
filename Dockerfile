FROM php:8.2-apache

# Install system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libxml2-dev libicu-dev \
    libpng-dev libjpeg-dev libfreetype6-dev g++ \
    zip unzip curl git \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions one by one
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install zip
RUN docker-php-ext-install xml
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd
RUN docker-php-ext-configure intl && docker-php-ext-install intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install Laravel dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 \
    composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy all Laravel files
COPY . .

# Run post-install scripts
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache

# Remove old Apache config and write a clean one pointing to /public
RUN rm -f /etc/apache2/sites-available/000-default.conf && \
    echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Suppress ServerName warning globally
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
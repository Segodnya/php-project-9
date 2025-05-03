FROM php:8.4.6-cli

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install zip pdo pdo_pgsql

# Configure PHP to hide deprecation warnings
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" >> /usr/local/etc/php/conf.d/error-reporting.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-autoloader --no-scripts --no-interaction

# Copy the rest of the application files
COPY . .

# Generate autoloader files
RUN composer dump-autoload --optimize

# Create .env file if not exists
RUN touch .env

# Expose the port the app runs on
EXPOSE 8000

# Command to run the application
CMD PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:8000 -t public

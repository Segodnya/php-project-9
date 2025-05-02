FROM php:8.1-cli

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

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

# Expose the port the app runs on
EXPOSE 8000

# Command to run the application
CMD PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:8000 -t public 
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    libzip-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy composer files and run install
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-scripts --no-autoloader --no-dev

# Copy the rest of the application code
COPY . .

# Finish composer installation
RUN composer dump-autoload --optimize

# Ensure the SQLite database directory exists and is writable
RUN mkdir -p var/data && chmod -R 777 var

# Expose the port
ENV PORT=8080
EXPOSE 8080

# Start command - reset database before starting
CMD if [ -f database.sqlite ]; then rm database.sqlite; fi && \
    cat database.sql | sed 's/SERIAL PRIMARY KEY/INTEGER PRIMARY KEY AUTOINCREMENT/g' | sed 's/NOW()/CURRENT_TIMESTAMP/g' | sqlite3 database.sqlite && \
    php -S 0.0.0.0:$PORT -t public

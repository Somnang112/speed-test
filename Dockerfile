FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install zip pdo pdo_mysql mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs && apt-get clean

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build
RUN chmod -R 775 storage bootstrap/cache
RUN php artisan key:generate --force

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 80
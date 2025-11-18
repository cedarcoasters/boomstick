# Use the official PHP 8.3 FPM image as the base
FROM php:8.3-fpm

# Install system dependencies and PHP extensions (e.g., for PDO/MySQL)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    procps \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy your project files into the FPM document root
COPY . /var/www/html/

# Expose port 9000 for FPM
EXPOSE 9000

# Start PHP-FPM in the foreground
CMD ["php-fpm"]
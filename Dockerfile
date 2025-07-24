FROM php:8.2-apache

# Install system dependencies, PHP extensions, and FFmpeg
RUN apt-get update && \
    apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        zip \
        unzip \
        git \
        ffmpeg \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Optionally: Copy your app code (commented, since you mount with volumes)
# COPY . /var/www/html

# Optionally: Set permissions
# RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 80

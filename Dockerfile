# syntax=docker/dockerfile:1

FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    curl \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Laravel + MySQL
RUN docker-php-ext-install pdo_mysql mbstring zip gd bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js 22 (for Vite / npm)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy the entrypoint that boots the dev environment
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Laravel dev server (8000) and Vite dev server (5173)
EXPOSE 8000 5173

ENTRYPOINT ["entrypoint.sh"]

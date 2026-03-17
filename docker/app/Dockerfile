FROM php:8.3-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml \
    soap \
    opcache \
    sockets

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Swoole extension (for Laravel Octane)
RUN pecl install swoole && docker-php-ext-enable swoole

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /var/www

# Copy supervisor config
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

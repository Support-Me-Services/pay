FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev libzip-dev unzip git \
 && docker-php-ext-install pdo_mysql mbstring zip \
 && docker-php-ext-enable opcache \
 && rm -rf /var/lib/apt/lists/*

# opcache musi działać pod SAPI CLI (php artisan serve = wbudowany serwer PHP),
# inaczej każde żądanie parsuje cały vendor/ z wolnego bind-mountu (Windows/macOS).
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=30000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

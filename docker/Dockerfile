FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev libzip-dev unzip git ca-certificates \
 && docker-php-ext-install pdo_mysql mbstring zip \
 && docker-php-ext-enable opcache \
 && rm -rf /var/lib/apt/lists/*

# Środowisko z Avast HTTPS-scanningiem (MITM TLS własnym root CA). Ten root jest
# zaufany w Windows, ale nie w kontenerze — bez tego composer nie pobierze
# zależności (SSL certificate problem). Dodajemy go do zaufanych CA kontenera.
COPY avast-root.crt /usr/local/share/ca-certificates/avast-root.crt
RUN update-ca-certificates

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

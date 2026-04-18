FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# instalar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# copiar apenas composer primeiro (cache eficiente)
COPY app/composer.json /var/www/html/composer.json

# instalar dependências
RUN composer install --no-interaction --prefer-dist --no-dev

# copiar resto do código
COPY app/ /var/www/html/

# permissões
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html
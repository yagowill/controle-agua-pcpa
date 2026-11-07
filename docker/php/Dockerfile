# syntax=docker/dockerfile:1.7
#
# Imagem do SCEA/PCPA.
#
# Alvos (targets):
#   development -> ambiente local com bind mount e hot reload do Vite
#   production  -> imagem imutável, com assets compilados e dependências de produção
#
# A imagem base já contém todas as extensões exigidas pelo Laravel, pelo Dompdf
# e pelo PhpSpreadsheet, além do driver do PostgreSQL e do SQLite (usado nos testes).

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# Base comum
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        pgsql \
        zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Binário do Composer compartilhado entre os estágios
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-scea.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-scea-opcache.ini
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/entrypoint.sh /usr/local/bin/scea-entrypoint

RUN chmod +x /usr/local/bin/scea-entrypoint \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Desenvolvimento
# ---------------------------------------------------------------------------
# O código-fonte é montado via bind mount pelo compose.yaml. O vendor fica em
# um volume nomeado e é instalado no primeiro start pelo entrypoint.
FROM base AS development

ENV APP_ENV=local

ENTRYPOINT ["scea-entrypoint"]
CMD ["apache2-foreground"]

# ---------------------------------------------------------------------------
# Dependências PHP de produção
# ---------------------------------------------------------------------------
FROM base AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# ---------------------------------------------------------------------------
# Build dos assets (Vue/Vuetify/Tailwind)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./

# resources/js/app.js importa o Ziggy diretamente do vendor do Composer.
COPY --from=vendor /var/www/html/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

RUN npm run build

# ---------------------------------------------------------------------------
# Produção
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

# Em produção o OPcache não valida timestamps (código imutável)
COPY docker/php/opcache-production.ini /usr/local/etc/php/conf.d/99-scea-opcache.ini

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

ENTRYPOINT ["scea-entrypoint"]
CMD ["apache2-foreground"]

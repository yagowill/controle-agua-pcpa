# syntax=docker/dockerfile:1
FROM php:8.3-apache

# Dependências do sistema + extensões PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libicu-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pdo_mysql gd zip intl opcache bcmath \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Node.js (para compilar os assets Vite/Vue)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# DocumentRoot aponta para /public e porta 8080 (padrão Fly.io)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
# Substitui a configuração padrão do Apache por uma controlada (porta 8080)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default \
    && echo "Listen 8080" > /etc/apache2/ports.conf

# Otimizações do PHP para produção
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copia o código (respeitando .dockerignore)
COPY . .

# Instala dependências PHP (sem dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Compila os assets do frontend
RUN npm ci --no-audit --no-fund && npm run build && rm -rf node_modules

# Garante que os diretórios de runtime do storage existam
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Script de inicialização (migrations + cache + apache)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
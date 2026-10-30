#!/usr/bin/env bash
set -e

cd /var/www/html

# Garante que o .env exista (Fly.io injeta via secrets/env)
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Gera a chave da aplicação se ainda não existir
if [ -z "${APP_KEY}" ]; then
    php artisan key:generate --force
fi

# Otimizações de produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Executa as migrations automaticamente
php artisan migrate --force

exec "$@"
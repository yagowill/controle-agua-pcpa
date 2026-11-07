#!/bin/sh
#
# Entrypoint compartilhado entre desenvolvimento e produção.
#
# Desenvolvimento:
#   - cria .env a partir de .env.example quando necessário;
#   - instala o vendor no volume nomeado na primeira execução;
#   - gera APP_KEY e aplica as migrations.
#
# Produção:
#   - exige APP_KEY definida por variável de ambiente;
#   - respeita a variável PORT (Render e plataformas semelhantes);
#   - aplica migrations e otimiza os caches do Laravel.
#
# Comentários em português conforme as convenções do projeto.

set -eu

cd /var/www/html

log() {
    echo "[scea] $*"
}

if [ "${APP_ENV:-local}" = "production" ]; then
    # -----------------------------------------------------------------------
    # Produção
    # -----------------------------------------------------------------------
    if [ -z "${APP_KEY:-}" ]; then
        log "ERRO: APP_KEY não definida."
        log "Gere uma chave com: php artisan key:generate --show"
        exit 1
    fi

    # Algumas plataformas (ex.: Render) injetam a porta em PORT.
    if [ -n "${PORT:-}" ] && [ "${PORT}" != "80" ]; then
        log "Configurando o Apache para ouvir na porta ${PORT}"
        sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
        sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
    fi
else
    # -----------------------------------------------------------------------
    # Desenvolvimento
    # -----------------------------------------------------------------------
    if [ ! -f .env ]; then
        log "Criando .env a partir de .env.example"
        cp .env.example .env
    fi

    if [ ! -f vendor/autoload.php ]; then
        log "Instalando dependências PHP (primeira execução)..."
        composer install --no-interaction --prefer-dist
    fi

    if ! grep -q '^APP_KEY=base64:' .env; then
        log "Gerando APP_KEY..."
        php artisan key:generate --force
    fi
fi

# ---------------------------------------------------------------------------
# Aguarda o PostgreSQL aceitar conexões
# ---------------------------------------------------------------------------
if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    log "Aguardando PostgreSQL em ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
    until php -r '
        try {
            new PDO(
                sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    getenv("DB_HOST") ?: "postgres",
                    getenv("DB_PORT") ?: "5432",
                    getenv("DB_DATABASE") ?: "laravel"
                ),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: ""
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' 2>/dev/null; do
        sleep 2
    done
fi

# ---------------------------------------------------------------------------
# Migrations, diretórios e permissões
# ---------------------------------------------------------------------------
log "Aplicando migrations..."
php artisan migrate --force

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

php artisan storage:link >/dev/null 2>&1 || true

if [ "${APP_ENV:-local}" = "production" ]; then
    log "Otimizando caches do Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

log "Iniciando: $*"
exec "$@"

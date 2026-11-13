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
#   - respeita a variável PORT (Fly.io e plataformas semelhantes);
#   - aceita DB_URL (Neon, Aiven, Supabase) além de DB_HOST/DB_PORT;
#   - aplica migrations, roda o seeder quando RUN_SEEDER=true e otimiza os
#     caches do Laravel.
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

    # O Fly.io usa a porta interna definida em fly.toml (internal_port).
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
    if [ -n "${DB_URL:-}" ]; then
        log "Aguardando PostgreSQL externo (DB_URL)..."
    else
        log "Aguardando PostgreSQL em ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
    fi

    # Tenta por até 60 segundos. Depois disso o entrypoint prossegue e deixa
    # que o `migrate` mostre o erro real de conexão/credenciais.
    attempts=0
    until php -r '
        $url = getenv("DB_URL");

        try {
            if ($url) {
                // Banco externo (Neon, Aiven, Supabase...): extrai host, porta,
                // base e credenciais da própria connection string.
                $parts = parse_url($url);
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
                    $parts["host"] ?? "127.0.0.1",
                    $parts["port"] ?? 5432,
                    ltrim($parts["path"] ?? "", "/"),
                    getenv("DB_SSLMODE") ?: "require"
                );
                $user = isset($parts["user"]) ? rawurldecode($parts["user"]) : "";
                $password = isset($parts["pass"]) ? rawurldecode($parts["pass"]) : "";
            } else {
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    getenv("DB_HOST") ?: "postgres",
                    getenv("DB_PORT") ?: "5432",
                    getenv("DB_DATABASE") ?: "laravel"
                );
                $user = getenv("DB_USERNAME") ?: "root";
                $password = getenv("DB_PASSWORD") ?: "";
            }

            new PDO($dsn, $user, $password, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' 2>/dev/null; do
        attempts=$((attempts + 1))

        if [ "$attempts" -ge 30 ]; then
            log "AVISO: PostgreSQL não respondeu em 60s; prosseguindo mesmo assim."
            break
        fi

        sleep 2
    done
fi

# ---------------------------------------------------------------------------
# Migrations, diretórios e permissões
# ---------------------------------------------------------------------------
log "Aplicando migrations..."
php artisan migrate --force

# Popula o banco apenas quando explicitamente solicitado (primeiro deploy).
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    log "Executando seeders..."
    php artisan db:seed --force
fi

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

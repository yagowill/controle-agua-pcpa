# Deploy no Fly.io — SCEA

## Pré-requisitos
- Conta no Fly.io (https://fly.io)
- `flyctl` instalado: https://fly.io/docs/hands-on/install-flyctl/
- Banco PostgreSQL disponível (Supabase ou Fly Postgres)

## 1. Login e criação do app
```bash
fly auth login
# Ajuste o nome do app em fly.toml se necessário
fly launch --no-deploy --copy-config --name scea-controle-agua-pcpa
```

## 2. Configurar secrets (obrigatório)
```bash
fly secrets set APP_KEY="$(php artisan key:generate --show)"
fly secrets set APP_URL="https://<seu-app>.fly.dev"

# Banco (exemplo Supabase)
fly secrets set DB_CONNECTION=pgsql
fly secrets set DB_HOST=db.<projeto>.supabase.co
fly secrets set DB_PORT=5432
fly secrets set DB_DATABASE=postgres
fly secrets set DB_USERNAME=postgres
fly secrets set DB_PASSWORD="<senha>"
fly secrets set DB_SSLMODE=require
```

## 3. Deploy
```bash
fly deploy
```
O `entrypoint.sh` executa automaticamente `migrate --force` e os caches de produção.

## 4. (Opcional) Volume para storage persistente
Os uploads ficam em `storage/app/public`. Para persistência entre deploys:
```bash
fly volumes create scea_storage --size 1 --region gru
```
E monte em `fly.toml`:
```toml
[mounts]
  source = "scea_storage"
  destination = "/var/www/html/storage/app"
```

## 5. Seed do administrador
Após o primeiro deploy:
```bash
fly ssh console -C "php artisan db:seed --class=AdminUserSeeder"
```
Credenciais iniciais: `yago.martins` / `Admin@pcpa2026` (troca obrigatória no primeiro acesso).

## Observações
- Região padrão: `gru` (São Paulo).
- Health check: `GET /up` (configurado em `bootstrap/app.php`).
- Sessões, cache e filas usam o banco de dados (SESSION_DRIVER=database, CACHE_STORE=database).
- Para reduzir custo, `auto_stop_machines = "stop"` está ativo (máquina para quando inativa).
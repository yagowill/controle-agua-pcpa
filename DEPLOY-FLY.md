# Deploy no Fly.io — SCEA

## Pré-requisitos
- Conta no Fly.io (https://fly.io)
- `flyctl` instalado: https://fly.io/docs/hands-on/install-flyctl/
- Banco PostgreSQL disponível (Supabase ou Fly Postgres)

## 1. Via GitHub Actions (recomendado — sem CLI local)

O repositório já contém `.github/workflows/fly-deploy.yml`. Basta:

1. Criar um token em https://fly.io/user/personal_access_tokens (escopo da org `yago-martins-366`).
2. No GitHub: **Settings → Secrets and variables → Actions → New repository secret**, adicionar:
   - `FLY_API_TOKEN` — o token criado acima
   - `APP_KEY` — gere com `php artisan key:generate --show`
   - `DB_HOST` — host do Postgres (ex.: `db.<projeto>.supabase.co`)
   - `DB_DATABASE` — nome do banco (ex.: `postgres`)
   - `DB_USERNAME` — usuário
   - `DB_PASSWORD` — senha
3. Fazer push na branch `main`/`master` (ou rodar o workflow manualmente em **Actions → Deploy to Fly.io → Run workflow**).

O workflow cria o app (se não existir), sincroniza os secrets e faz o deploy. O `entrypoint.sh` roda as migrations automaticamente.

## 1b. Via CLI (alternativa)
```bash
fly auth login
fly launch --org yago-martins-366 --name scea-controle-agua-pcpa --region gru --no-deploy
fly secrets set APP_KEY="$(php artisan key:generate --show)" APP_URL="https://scea-controle-agua-pcpa.fly.dev"
fly secrets set DB_CONNECTION=pgsql DB_HOST=db.<projeto>.supabase.co DB_PORT=5432 DB_DATABASE=postgres DB_USERNAME=postgres DB_PASSWORD="<senha>" DB_SSLMODE=require
fly deploy
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
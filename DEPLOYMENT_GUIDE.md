# Deployment Guide

## 1. Server Requirements
- PHP 8.2+
- Composer
- Node 20+
- MySQL 8+
- Redis
- Supervisor (queue workers)
- Nginx or Apache

## 2. Environment
Set production `.env` values:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- Database credentials
- Redis credentials
- Mail credentials
- Payment gateway credentials

Required payment gateway env vars:
- `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_WEBHOOK_SECRET`
- `FLUTTERWAVE_PUBLIC_KEY`, `FLUTTERWAVE_SECRET_KEY`, `FLUTTERWAVE_WEBHOOK_SECRET`

Key naming note:
- Use standardized payment env names (`*_PUBLIC_KEY`, `*_SECRET_KEY`) for all deployments.

## 3. Install and Build
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Queue Workers
Start workers via Supervisor:
```ini
[program:ecommerce-queue]
command=php /var/www/ecommerce/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ecommerce/storage/logs/queue.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ecommerce-queue:*
```

## 5. Web Server
Point document root to `public/`.
Enable HTTPS with TLS certificate.

## 6. Post-Deploy Validation
```bash
php artisan test
php artisan route:list --path=api
curl https://your-domain/api/health
```

Gateway validation smoke checks:
```bash
curl https://your-domain/api/payments/gateways
php artisan route:list --path=payments/webhook
```

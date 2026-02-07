# Deployment Guide

## Production Deployment Checklist

### 1. Server Requirements

**Minimum Requirements:**
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+
- Node.js 18+
- Nginx or Apache
- SSL Certificate
- 2GB RAM minimum (4GB+ recommended)
- 20GB disk space

### 2. Environment Configuration

**Update `.env` for production:**

```env
APP_NAME="Your E-commerce"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# AWS S3 (for file storage)
FILESYSTEM_DRIVER=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Payment Gateways
STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret
STRIPE_WEBHOOK_SECRET=your-webhook-secret

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# Session
SESSION_DOMAIN=.yourdomain.com
SESSION_SECURE_COOKIE=true
```

### 3. Backend Deployment

**Step 1: Clone Repository**
```bash
git clone https://github.com/yourusername/ecommerce.git
cd ecommerce
```

**Step 2: Install Dependencies**
```bash
composer install --optimize-autoloader --no-dev
```

**Step 3: Set Permissions**
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Step 4: Generate Application Key**
```bash
php artisan key:generate
```

**Step 5: Run Migrations**
```bash
php artisan migrate --force
```

**Step 6: Seed Database (Optional)**
```bash
php artisan db:seed --force
```

**Step 7: Optimize Application**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Step 8: Link Storage**
```bash
php artisan storage:link
```

**Step 9: Start Queue Worker**
```bash
# Using Supervisor
sudo supervisorctl start laravel-worker:*

# Or using systemd
sudo systemctl start laravel-queue
```

### 4. Frontend Deployment

**Step 1: Install Dependencies**
```bash
npm ci
```

**Step 2: Build for Production**
```bash
npm run build
```

**Step 3: Copy Build Files**
The build files will be in `public/build/` and are automatically served by Laravel.

### 5. Nginx Configuration

**Create Nginx config file:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/ecommerce/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6. Supervisor Configuration (Queue Worker)

**Create `/etc/supervisor/conf.d/laravel-worker.conf`:**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ecommerce/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/ecommerce/storage/logs/worker.log
stopwaitsecs=3600
```

**Reload Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 7. Cron Jobs

**Add to crontab:**
```bash
* * * * * cd /var/www/ecommerce && php artisan schedule:run >> /dev/null 2>&1
```

### 8. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 9. Database Backup

**Create backup script `/usr/local/bin/backup-db.sh`:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mysql"
DB_NAME="your_database"
DB_USER="your_username"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

**Add to crontab:**
```bash
0 2 * * * /usr/local/bin/backup-db.sh
```

### 10. Monitoring & Logging

**Install Laravel Telescope (Development only):**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Configure Logging:**
Update `config/logging.php` to use appropriate channels for production.

**Monitor Application:**
- Use Laravel Horizon for queue monitoring
- Set up error tracking (Sentry, Bugsnag)
- Monitor server resources (CPU, RAM, Disk)
- Set up uptime monitoring

### 11. Performance Optimization

**Enable OPcache:**
Edit `/etc/php/8.2/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

**Redis Configuration:**
Edit `/etc/redis/redis.conf`:
```
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 12. Security Hardening

- [ ] Enable firewall (UFW)
- [ ] Disable root SSH login
- [ ] Use SSH keys instead of passwords
- [ ] Keep system packages updated
- [ ] Regular security audits
- [ ] Implement rate limiting
- [ ] Use strong passwords
- [ ] Enable 2FA for admin accounts
- [ ] Regular backups
- [ ] Monitor logs for suspicious activity

### 13. Post-Deployment

**Verify Installation:**
```bash
# Check application status
php artisan about

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue workers
sudo supervisorctl status

# Test cache
php artisan cache:clear
php artisan config:cache
```

**Monitor Logs:**
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### 14. Rollback Plan

**If deployment fails:**
```bash
# Restore database backup
gunzip < /var/backups/mysql/backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u user -p database

# Revert code
git reset --hard previous_commit_hash

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart laravel-worker:*
```

### 15. Scaling Considerations

**Horizontal Scaling:**
- Use load balancer (Nginx, HAProxy)
- Separate database server
- Redis cluster for caching
- CDN for static assets
- Multiple application servers

**Vertical Scaling:**
- Increase server resources
- Optimize database queries
- Implement caching strategies
- Use queue workers for heavy tasks

---

## Continuous Deployment

**Using GitHub Actions:**

Create `.github/workflows/deploy.yml`:
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/ecommerce
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart laravel-worker:*
```

---

## Support & Maintenance

- Regular updates (weekly/monthly)
- Security patches (immediate)
- Database optimization (monthly)
- Log rotation (weekly)
- Backup verification (weekly)
- Performance monitoring (daily)


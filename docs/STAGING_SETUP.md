# Staging Environment Quick Setup Guide

This guide provides step-by-step instructions to quickly set up the staging environment for database preparation and release testing.

## Prerequisites

- Server with SFTP access
- MySQL/MariaDB database server
- PHP 8.1+
- Nginx web server
- SSL certificate for staging.hypnose-stammtisch.de

## Quick Setup Steps

### 1. Create Staging Database

```bash
# Run the automated setup script
./scripts/setup-environment-db.sh staging create

# Or manually:
mysql -u root -p
```

```sql
CREATE DATABASE hypnose_stammtisch_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'staging_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON hypnose_stammtisch_staging.* TO 'staging_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Import Database Schema

```bash
# Import the initial schema
mysql -u staging_user -p hypnose_stammtisch_staging < backend/migrations/001_initial_schema.sql
```

### 3. Configure Backend Environment

```bash
# Copy the staging environment template
cp backend/.env.staging.example backend/.env.staging

# Edit the configuration
nano backend/.env.staging
```

Update these critical values:
```env
DB_PASS=your_secure_password
JWT_SECRET=generate_a_secure_random_string
CALENDAR_FEED_TOKEN=generate_another_secure_token
```

### 4. Configure Nginx

```bash
# Copy nginx configuration
sudo cp nginx.staging.conf /etc/nginx/sites-available/staging.hypnose-stammtisch.de

# Enable the site
sudo ln -s /etc/nginx/sites-available/staging.hypnose-stammtisch.de /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
```

### 5. Set Up Backend Service

Create systemd service file:
```bash
sudo nano /etc/systemd/system/hypnose-staging.service
```

Add this content:
```ini
[Unit]
Description=Hypnose Stammtisch Staging Backend
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/hypnose-stammtisch.de/staging/backend
ExecStart=/usr/bin/php -S localhost:8001 -t api
Restart=always
EnvironmentFile=/var/www/hypnose-stammtisch.de/staging/backend/.env.staging

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable hypnose-staging.service
sudo systemctl start hypnose-staging.service
sudo systemctl status hypnose-staging.service
```

### 6. Configure GitHub Actions Secrets

In your GitHub repository settings, add these secrets:
- `STAGING_SFTP_HOST` - Your staging server hostname
- `STAGING_SFTP_PORT` - SFTP port (usually 22)
- `STAGING_SFTP_USER` - SFTP username
- `STAGING_SFTP_PASS` - SFTP password
- `STAGING_SFTP_REMOTE_DIR` - Target directory (e.g., `/var/www/hypnose-stammtisch.de/staging`)

### 7. Create Staging Branch

```bash
# Create and push staging branch
git checkout -b staging
git push -u origin staging
```

### 8. Deploy to Staging

Option A: Automatic deployment (GitHub Actions)
```bash
# Push to staging branch triggers automatic deployment
git push origin staging
```

Option B: Manual deployment
```bash
# Build locally
npm run build:all

# Deploy via SFTP/rsync
rsync -avz --delete dist/ user@your-server:/var/www/hypnose-stammtisch.de/staging/dist/
rsync -avz backend/api/ user@your-server:/var/www/hypnose-stammtisch.de/staging/backend/api/
```

## Verification

### Check Services

```bash
# Check backend service
sudo systemctl status hypnose-staging.service

# Check nginx
sudo systemctl status nginx

# Test backend API
curl https://staging.hypnose-stammtisch.de/api/health
```

### Check Logs

```bash
# Backend logs
sudo journalctl -u hypnose-staging.service -f

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

## Common Use Cases

### Prepare Database for Release

```bash
# 1. Backup production database
./scripts/setup-environment-db.sh production backup

# 2. Copy production data to staging (if needed)
./scripts/setup-environment-db.sh staging restore

# 3. Test migrations on staging
cd backend
php cli/cli.php migrate

# 4. Verify data integrity
php cli/cli.php database status

# 5. Create staging backup before production deployment
./scripts/setup-environment-db.sh staging backup
```

### Test Database Migrations

```bash
# Test migration script
cd backend
php cli/cli.php migrate --dry-run

# Apply migrations
php cli/cli.php migrate

# Rollback if needed
php cli/cli.php migrate --rollback
```

### Sync Staging with Production Data

```bash
# Backup production
mysqldump -u root -p hypnose_stammtisch > prod_backup.sql

# Restore to staging
mysql -u staging_user -p hypnose_stammtisch_staging < prod_backup.sql
```

## Troubleshooting

### Backend Service Won't Start

```bash
# Check service logs
sudo journalctl -u hypnose-staging.service -n 50

# Check PHP errors
php -l backend/api/index.php

# Verify permissions
sudo chown -R www-data:www-data /var/www/hypnose-stammtisch.de/staging
```

### Database Connection Issues

```bash
# Test database connection
mysql -u staging_user -p hypnose_stammtisch_staging -e "SELECT 1;"

# Check .env.staging file
cat backend/.env.staging | grep DB_

# Verify user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'staging_user'@'localhost';"
```

### SSL Certificate Issues

```bash
# Generate Let's Encrypt certificate
sudo certbot --nginx -d staging.hypnose-stammtisch.de

# Verify certificate
sudo certbot certificates

# Renew certificate
sudo certbot renew
```

## Next Steps

1. Test your application at `https://staging.hypnose-stammtisch.de`
2. Verify database migrations work correctly
3. Test all critical features
4. When satisfied, promote to production

## Related Documentation

- [Full Deployment Environments Guide](./DEPLOYMENT_ENVIRONMENTS.md)
- [Backend Setup](../backend/README.md)
- [GitHub Actions Workflows](../.github/workflows/)

## Support

For issues or questions:
- Check logs: `sudo journalctl -u hypnose-staging.service`
- Review nginx logs: `sudo tail -f /var/log/nginx/error.log`
- Verify environment configuration: `cat backend/.env.staging`

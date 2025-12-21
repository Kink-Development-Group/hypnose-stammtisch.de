# Deployment Environments

This document describes the different deployment environments for the Hypnose Stammtisch application and how to set them up.

## Environment Overview

The application supports multiple deployment environments:

- **Production** (`main` branch) - Live environment at `hypnose-stammtisch.de`
- **Staging** (`staging` branch) - Pre-production environment for final testing and database preparation at `staging.hypnose-stammtisch.de`
- **Beta** (`beta` branch) - Testing environment for new features at `beta.hypnose-stammtisch.de`
- **Development** (local) - Local development environment

## Environment Purpose

### Production Environment
- **Branch**: `main`
- **Domain**: `hypnose-stammtisch.de`
- **Database**: `hypnose_stammtisch`
- **Backend Port**: 8000
- **Purpose**: Live production environment serving real users

### Staging Environment
- **Branch**: `staging`
- **Domain**: `staging.hypnose-stammtisch.de`
- **Database**: `hypnose_stammtisch_staging`
- **Backend Port**: 8001
- **Purpose**: Pre-production environment where the database can be prepared for release. This is the final testing environment before production deployment.

**Key Use Cases:**
- Database migration testing before production
- Data preparation and verification
- Final QA testing with production-like data
- Release candidate validation
- Database schema changes testing

### Beta Environment
- **Branch**: `beta`
- **Domain**: `beta.hypnose-stammtisch.de`
- **Database**: `hypnose_stammtisch_beta`
- **Backend Port**: 8002
- **Purpose**: Testing environment for experimental features and early testing

**Key Use Cases:**
- Feature testing before staging
- User acceptance testing (UAT)
- Integration testing
- Demo environment for stakeholders

## Environment Setup

### 1. Staging Environment Setup

#### Database Setup
```bash
# Create staging database
mysql -u root -p -e "CREATE DATABASE hypnose_stammtisch_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create staging user
mysql -u root -p -e "CREATE USER 'staging_user'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON hypnose_stammtisch_staging.* TO 'staging_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Import schema
mysql -u root -p hypnose_stammtisch_staging < backend/migrations/001_initial_schema.sql
```

#### Backend Configuration
```bash
# Copy staging environment template
cp backend/.env.staging.example backend/.env.staging

# Edit the configuration file
nano backend/.env.staging
```

Update the following values:
- `DB_PASS`: Set the database password
- `JWT_SECRET`: Generate a secure secret
- `MAIL_*`: Configure email settings
- `CALENDAR_FEED_TOKEN`: Generate a secure token

#### Nginx Configuration
```bash
# Copy staging nginx configuration
sudo cp nginx.staging.conf /etc/nginx/sites-available/staging.hypnose-stammtisch.de
sudo ln -s /etc/nginx/sites-available/staging.hypnose-stammtisch.de /etc/nginx/sites-enabled/

# Update SSL certificate paths in the configuration
sudo nano /etc/nginx/sites-available/staging.hypnose-stammtisch.de

# Test and reload nginx
sudo nginx -t
sudo systemctl reload nginx
```

#### Backend PHP Service
Create a systemd service for the staging backend:

```bash
sudo nano /etc/systemd/system/hypnose-staging.service
```

Add the following content:
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
sudo systemctl enable hypnose-staging.service
sudo systemctl start hypnose-staging.service
sudo systemctl status hypnose-staging.service
```

### 2. Beta Environment Setup

Follow the same steps as staging, but use:
- Database: `hypnose_stammtisch_beta`
- User: `beta_user`
- Config: `.env.beta`
- Nginx: `nginx.beta.conf`
- Domain: `beta.hypnose-stammtisch.de`
- Port: 8002
- Service: `hypnose-beta.service`

### 3. GitHub Actions Secrets

Configure the following secrets in your GitHub repository for automated deployments:

#### Staging Secrets
- `STAGING_SFTP_HOST`: Staging server hostname
- `STAGING_SFTP_PORT`: SFTP port (usually 22)
- `STAGING_SFTP_USER`: SFTP username
- `STAGING_SFTP_PASS`: SFTP password
- `STAGING_SFTP_REMOTE_DIR`: Remote directory path for staging

#### Beta Secrets
- `BETA_SFTP_HOST`: Beta server hostname
- `BETA_SFTP_PORT`: SFTP port (usually 22)
- `BETA_SFTP_USER`: SFTP username
- `BETA_SFTP_PASS`: SFTP password
- `BETA_SFTP_REMOTE_DIR`: Remote directory path for beta

#### Production Secrets (existing)
- `SFTP_HOST`: Production server hostname
- `SFTP_PORT`: SFTP port
- `SFTP_USER`: SFTP username
- `SFTP_PASS`: SFTP password
- `SFTP_REMOTE_DIR`: Remote directory path for production

## Deployment Workflow

### Typical Release Process

1. **Development**: Develop features locally
2. **Beta**: Merge to `beta` branch for initial testing
   - Automated deployment to `beta.hypnose-stammtisch.de`
   - Feature testing and UAT
3. **Staging**: Merge to `staging` branch for final validation
   - Automated deployment to `staging.hypnose-stammtisch.de`
   - Database migration testing
   - Final QA and release candidate validation
4. **Production**: Merge to `main` branch for production release
   - Automated deployment to `hypnose-stammtisch.de`

### Database Preparation in Staging

The staging environment is specifically designed for database preparation:

```bash
# Connect to staging database
mysql -u staging_user -p hypnose_stammtisch_staging

# Run migrations
cd /var/www/hypnose-stammtisch.de/staging/backend
php cli/cli.php migrate

# Seed test data if needed
php cli/cli.php migrate --seed

# Verify data
php cli/cli.php database status

# Create database backup before production
php cli/cli.php database backup
```

### Manual Deployment

If you need to deploy manually (without GitHub Actions):

#### Staging
```bash
# Build locally
npm run build:all

# Deploy via SFTP/rsync
rsync -avz --delete dist/ user@staging-server:/var/www/hypnose-stammtisch.de/staging/dist/
rsync -avz --delete backend/api/ user@staging-server:/var/www/hypnose-stammtisch.de/staging/backend/api/
```

## Environment Variables

Each environment should have its own `.env` file in the backend directory:
- `.env` (or `.env.production`) - Production
- `.env.staging` - Staging
- `.env.beta` - Beta

Key differences between environments:

| Variable | Production | Staging | Beta |
|----------|-----------|---------|------|
| `APP_ENV` | production | staging | beta |
| `APP_DEBUG` | false | true | true |
| `DB_NAME` | hypnose_stammtisch | hypnose_stammtisch_staging | hypnose_stammtisch_beta |
| `APP_URL` | https://hypnose-stammtisch.de | https://staging.hypnose-stammtisch.de | https://beta.hypnose-stammtisch.de |

## Monitoring

### Check Environment Status

```bash
# Check staging backend
curl https://staging.hypnose-stammtisch.de/api/health

# Check beta backend
curl https://beta.hypnose-stammtisch.de/api/health

# Check production backend
curl https://hypnose-stammtisch.de/api/health
```

### View Logs

```bash
# Staging logs
sudo journalctl -u hypnose-staging.service -f

# Beta logs
sudo journalctl -u hypnose-beta.service -f

# Production logs
sudo journalctl -u hypnose-production.service -f

# Nginx logs
sudo tail -f /var/log/nginx/staging.hypnose-stammtisch.de-access.log
sudo tail -f /var/log/nginx/staging.hypnose-stammtisch.de-error.log
```

## Troubleshooting

### Staging Environment Not Accessible
1. Check if the backend service is running: `sudo systemctl status hypnose-staging.service`
2. Check nginx configuration: `sudo nginx -t`
3. Check firewall rules: `sudo ufw status`
4. Check SSL certificates: `sudo certbot certificates`

### Database Connection Issues
1. Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`
2. Check user permissions: `mysql -u root -p -e "SHOW GRANTS FOR 'staging_user'@'localhost';"`
3. Test connection: `mysql -u staging_user -p hypnose_stammtisch_staging -e "SELECT 1;"`

### Deployment Failures
1. Check GitHub Actions logs in the repository
2. Verify SFTP credentials and secrets
3. Check file permissions on the server
4. Verify disk space: `df -h`

## Security Considerations

1. **Separate Databases**: Each environment uses its own database to prevent data contamination
2. **Separate Secrets**: Each environment has unique JWT secrets and API tokens
3. **Access Control**: Staging and beta environments should have IP restrictions if possible
4. **Email Configuration**: Use separate email addresses or prefixes to avoid confusion
5. **Debug Mode**: Only enable debug mode in non-production environments

## Best Practices

1. **Always test in beta first** before promoting to staging
2. **Use staging for database migrations** before applying to production
3. **Keep environments in sync** with similar configurations
4. **Document all manual changes** made to environments
5. **Regular backups** of all environment databases
6. **Monitor environment health** regularly
7. **Clean up old test data** periodically in beta/staging

## References

- [GitHub Actions Workflows](.github/workflows/)
- [Nginx Configurations](nginx.*.conf)
- [Backend Environment Examples](backend/.env.*.example)

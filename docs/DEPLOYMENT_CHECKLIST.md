# Deployment Checklist for Staging and Beta Environments

Use this checklist when setting up the staging and beta environments for the first time.

## Pre-Deployment Requirements

### Server Requirements
- [ ] Server with SFTP access configured
- [ ] MySQL/MariaDB 8.0+ or 10.6+ installed
- [ ] PHP 8.1+ installed with required extensions
- [ ] Nginx web server installed
- [ ] Sufficient disk space (at least 5GB free)
- [ ] SSL certificates ready or Let's Encrypt installed

### DNS Configuration
- [ ] DNS A record for `staging.hypnose-stammtisch.de` points to server IP
- [ ] DNS A record for `beta.hypnose-stammtisch.de` points to server IP
- [ ] DNS propagation completed (check with `nslookup staging.hypnose-stammtisch.de`)

### GitHub Configuration
- [ ] GitHub repository access configured
- [ ] SFTP credentials prepared for staging
- [ ] SFTP credentials prepared for beta

## Staging Environment Setup

### 1. GitHub Secrets Configuration
- [ ] Add `STAGING_SFTP_HOST` in repository secrets
- [ ] Add `STAGING_SFTP_PORT` in repository secrets (usually 22)
- [ ] Add `STAGING_SFTP_USER` in repository secrets
- [ ] Add `STAGING_SFTP_PASS` in repository secrets
- [ ] Add `STAGING_SFTP_REMOTE_DIR` in repository secrets (e.g., `/var/www/hypnose-stammtisch.de/staging`)

### 2. Server Directory Setup
```bash
# Create staging directory
sudo mkdir -p /var/www/hypnose-stammtisch.de/staging
sudo mkdir -p /var/www/hypnose-stammtisch.de/staging/backend
sudo chown -R www-data:www-data /var/www/hypnose-stammtisch.de/staging
```
- [ ] Staging directory created
- [ ] Proper ownership set

### 3. Database Setup
```bash
# Option 1: Use automated script (from repository root)
./scripts/setup-environment-db.sh staging create

# Option 2: Manual setup
mysql -u root -p
CREATE DATABASE hypnose_stammtisch_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'staging_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON hypnose_stammtisch_staging.* TO 'staging_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
- [ ] Database `hypnose_stammtisch_staging` created
- [ ] Database user `staging_user` created
- [ ] Privileges granted
- [ ] Database password securely stored

### 4. Import Database Schema
```bash
# After first deployment or manually:
mysql -u staging_user -p hypnose_stammtisch_staging < backend/migrations/001_initial_schema.sql
```
- [ ] Initial schema imported
- [ ] Schema verified: `./scripts/setup-environment-db.sh staging status`

### 5. Backend Configuration
```bash
# Copy environment template
cp backend/.env.staging.example /var/www/hypnose-stammtisch.de/staging/backend/.env

# Edit configuration
sudo nano /var/www/hypnose-stammtisch.de/staging/backend/.env
```

Update these values:
- [ ] `DB_PASS` - Set database password
- [ ] `JWT_SECRET` - Generate secure random string (32+ chars)
- [ ] `CALENDAR_FEED_TOKEN` - Generate secure random string
- [ ] `MAIL_HOST` - Configure email server
- [ ] `MAIL_USERNAME` - Set email username
- [ ] `MAIL_PASSWORD` - Set email password
- [ ] All email addresses updated with staging prefix

### 6. SSL Certificate Setup
```bash
# Using Let's Encrypt (recommended)
sudo certbot --nginx -d staging.hypnose-stammtisch.de

# Verify certificate
sudo certbot certificates
```
- [ ] SSL certificate obtained
- [ ] Certificate auto-renewal configured
- [ ] HTTPS working

### 7. Nginx Configuration
```bash
# Copy nginx configuration
sudo cp nginx.staging.conf /etc/nginx/sites-available/staging.hypnose-stammtisch.de

# Enable site
sudo ln -s /etc/nginx/sites-available/staging.hypnose-stammtisch.de /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
```
- [ ] Nginx configuration copied
- [ ] Site enabled
- [ ] Configuration tested
- [ ] Nginx reloaded

### 8. Backend Service Setup
```bash
# Create systemd service
sudo nano /etc/systemd/system/hypnose-staging.service
```

Add service configuration (see docs/STAGING_SETUP.md for full content)

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable hypnose-staging.service
sudo systemctl start hypnose-staging.service
sudo systemctl status hypnose-staging.service
```
- [ ] Systemd service created
- [ ] Service enabled
- [ ] Service started
- [ ] Service status is active

### 9. Create Staging Branch
```bash
# In local repository
git checkout -b staging
git push -u origin staging
```
- [ ] Staging branch created
- [ ] Branch pushed to GitHub
- [ ] Automatic deployment triggered

### 10. Verify Staging Deployment
```bash
# Check frontend
curl -I https://staging.hypnose-stammtisch.de

# Check backend API
curl https://staging.hypnose-stammtisch.de/api/health

# Check logs
sudo journalctl -u hypnose-staging.service -n 50
```
- [ ] Frontend accessible
- [ ] Backend API responding
- [ ] No errors in logs
- [ ] Test basic functionality in browser

## Beta Environment Setup

### 1. GitHub Secrets Configuration
- [ ] Add `BETA_SFTP_HOST` in repository secrets
- [ ] Add `BETA_SFTP_PORT` in repository secrets (usually 22)
- [ ] Add `BETA_SFTP_USER` in repository secrets
- [ ] Add `BETA_SFTP_PASS` in repository secrets
- [ ] Add `BETA_SFTP_REMOTE_DIR` in repository secrets (e.g., `/var/www/hypnose-stammtisch.de/beta`)

### 2. Server Directory Setup
```bash
sudo mkdir -p /var/www/hypnose-stammtisch.de/beta
sudo mkdir -p /var/www/hypnose-stammtisch.de/beta/backend
sudo chown -R www-data:www-data /var/www/hypnose-stammtisch.de/beta
```
- [ ] Beta directory created
- [ ] Proper ownership set

### 3. Database Setup
```bash
./scripts/setup-environment-db.sh beta create
```
- [ ] Database `hypnose_stammtisch_beta` created
- [ ] Database user `beta_user` created
- [ ] Privileges granted
- [ ] Database password securely stored

### 4. Import Database Schema
```bash
mysql -u beta_user -p hypnose_stammtisch_beta < backend/migrations/001_initial_schema.sql
```
- [ ] Initial schema imported
- [ ] Schema verified

### 5. Backend Configuration
```bash
cp backend/.env.beta.example /var/www/hypnose-stammtisch.de/beta/backend/.env
sudo nano /var/www/hypnose-stammtisch.de/beta/backend/.env
```
- [ ] All credentials updated
- [ ] Email addresses configured
- [ ] Secrets generated

### 6. SSL Certificate Setup
```bash
sudo certbot --nginx -d beta.hypnose-stammtisch.de
```
- [ ] SSL certificate obtained
- [ ] HTTPS working

### 7. Nginx Configuration
```bash
sudo cp nginx.beta.conf /etc/nginx/sites-available/beta.hypnose-stammtisch.de
sudo ln -s /etc/nginx/sites-available/beta.hypnose-stammtisch.de /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```
- [ ] Nginx configuration copied
- [ ] Site enabled
- [ ] Nginx reloaded

### 8. Backend Service Setup
```bash
sudo nano /etc/systemd/system/hypnose-beta.service
# Configure service for port 8002
sudo systemctl daemon-reload
sudo systemctl enable hypnose-beta.service
sudo systemctl start hypnose-beta.service
```
- [ ] Service created and started
- [ ] Service status is active

### 9. Create Beta Branch
```bash
git checkout -b beta
git push -u origin beta
```
- [ ] Beta branch created
- [ ] Branch pushed to GitHub
- [ ] Automatic deployment triggered

### 10. Verify Beta Deployment
```bash
curl -I https://beta.hypnose-stammtisch.de
curl https://beta.hypnose-stammtisch.de/api/health
```
- [ ] Frontend accessible
- [ ] Backend API responding
- [ ] No errors in logs

## Post-Deployment Verification

### Staging Environment
- [ ] Visit https://staging.hypnose-stammtisch.de in browser
- [ ] Check that page loads correctly
- [ ] Verify environment indicator (X-Environment: staging in headers)
- [ ] Test calendar functionality
- [ ] Test contact form (verify emails go to staging addresses)
- [ ] Check backend logs: `sudo journalctl -u hypnose-staging.service -f`

### Beta Environment
- [ ] Visit https://beta.hypnose-stammtisch.de in browser
- [ ] Check that page loads correctly
- [ ] Verify environment indicator (X-Environment: beta in headers)
- [ ] Test calendar functionality
- [ ] Test contact form
- [ ] Check backend logs: `sudo journalctl -u hypnose-beta.service -f`

## Workflow Testing

### Test Automated Deployment
```bash
# Make a test change
git checkout staging
echo "# Test" >> README.md
git add README.md
git commit -m "Test staging deployment"
git push origin staging

# Monitor GitHub Actions
# Visit: https://github.com/Kink-Development-Group/hypnose-stammtisch.de/actions

# Verify deployment
curl -I https://staging.hypnose-stammtisch.de
```
- [ ] GitHub Actions workflow triggered
- [ ] Deployment completed successfully
- [ ] Changes reflected on staging site

### Test Database Management Script
```bash
# Test backup
./scripts/setup-environment-db.sh staging backup

# Test status
./scripts/setup-environment-db.sh staging status

# Verify backup created
ls -lh backend/backups/
```
- [ ] Backup created successfully
- [ ] Status command shows correct information

## Security Checklist

### Staging Environment
- [ ] Unique JWT secret configured
- [ ] Unique calendar feed token configured
- [ ] Database password is strong and unique
- [ ] SFTP credentials are secure
- [ ] SSL/HTTPS is working
- [ ] Debug mode is enabled (acceptable for staging)
- [ ] File permissions are correct (644 for files, 755 for directories)

### Beta Environment
- [ ] Unique JWT secret configured
- [ ] Unique calendar feed token configured
- [ ] Database password is strong and unique
- [ ] SFTP credentials are secure
- [ ] SSL/HTTPS is working
- [ ] Debug mode is enabled (acceptable for beta)
- [ ] File permissions are correct

## Monitoring Setup

### Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/hypnose-environments
```
- [ ] Log rotation configured

### Monitoring Commands
```bash
# Staging logs
sudo journalctl -u hypnose-staging.service -f

# Beta logs
sudo journalctl -u hypnose-beta.service -f

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```
- [ ] Know how to access logs
- [ ] Logs are being written correctly

## Documentation Review
- [ ] Read [DEPLOYMENT_ENVIRONMENTS.md](./DEPLOYMENT_ENVIRONMENTS.md)
- [ ] Read [STAGING_SETUP.md](./STAGING_SETUP.md)
- [ ] Read [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md)
- [ ] Bookmark documentation for future reference

## Final Sign-Off

### Staging Environment
- [ ] All services running
- [ ] Application accessible and functional
- [ ] Database migrations tested
- [ ] Backups working
- [ ] Documentation updated

### Beta Environment
- [ ] All services running
- [ ] Application accessible and functional
- [ ] Ready for feature testing
- [ ] Documentation updated

### Team Communication
- [ ] Team notified of new environments
- [ ] Access credentials shared securely
- [ ] Deployment workflow documented
- [ ] Support contacts updated

---

## Quick Reference

### Useful Commands
```bash
# Check service status
sudo systemctl status hypnose-staging.service
sudo systemctl status hypnose-beta.service

# Restart services
sudo systemctl restart hypnose-staging.service
sudo systemctl restart hypnose-beta.service

# View logs
sudo journalctl -u hypnose-staging.service -f
sudo journalctl -u hypnose-beta.service -f

# Database management
./scripts/setup-environment-db.sh staging status
./scripts/setup-environment-db.sh beta backup

# Test connectivity
curl https://staging.hypnose-stammtisch.de/api/health
curl https://beta.hypnose-stammtisch.de/api/health
```

## Troubleshooting

If you encounter issues, refer to:
- [DEPLOYMENT_ENVIRONMENTS.md - Troubleshooting Section](./DEPLOYMENT_ENVIRONMENTS.md#troubleshooting)
- [STAGING_SETUP.md - Troubleshooting Section](./STAGING_SETUP.md#troubleshooting)

## Support

For questions or issues during setup:
1. Check the documentation files in the `docs/` directory
2. Review GitHub Actions logs for deployment issues
3. Check server logs for runtime issues
4. Verify all configuration values are correct

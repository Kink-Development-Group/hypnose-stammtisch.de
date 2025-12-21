# Staging and Beta Environment Implementation - Summary

## Overview
This implementation adds comprehensive support for multiple deployment environments (Production, Staging, and Beta) to the Hypnose Stammtisch application, with a focus on the staging environment for database preparation before production releases.

## What Was Implemented

### 1. Nginx Configurations
- **nginx.staging.conf** - Nginx configuration for staging environment
  - Domain: `staging.hypnose-stammtisch.de`
  - Backend port: 8001
  - Shorter cache durations for testing
  - X-Environment header for identification
  
- **nginx.beta.conf** - Nginx configuration for beta environment
  - Domain: `beta.hypnose-stammtisch.de`
  - Backend port: 8002
  - Even shorter cache for rapid iteration
  - X-Environment header for identification

### 2. Backend Environment Configurations
- **backend/.env.staging.example** - Staging environment template
  - Separate database: `hypnose_stammtisch_staging`
  - Staging-specific email addresses
  - Debug mode enabled
  - Staging-specific URLs and secrets

- **backend/.env.beta.example** - Beta environment template
  - Separate database: `hypnose_stammtisch_beta`
  - Beta-specific email addresses
  - Debug mode enabled
  - Beta-specific URLs and secrets

### 3. GitHub Actions Workflows
- **.github/workflows/deploy-staging.yml** - Automated staging deployment
  - Triggers on push to `staging` branch
  - Builds frontend and backend
  - Deploys via SFTP to staging server
  - Uses staging-specific secrets

- **.github/workflows/deploy-beta.yml** - Automated beta deployment
  - Triggers on push to `beta` branch
  - Builds frontend and backend
  - Deploys via SFTP to beta server
  - Uses beta-specific secrets

### 4. Documentation
- **docs/DEPLOYMENT_ENVIRONMENTS.md** - Comprehensive guide
  - Environment overview and purposes
  - Detailed setup instructions for all environments
  - Database preparation workflows
  - Monitoring and troubleshooting guides
  - Security considerations
  - Best practices

- **docs/STAGING_SETUP.md** - Quick setup guide
  - Step-by-step staging environment setup
  - Common use cases for staging
  - Database migration testing procedures
  - Troubleshooting tips

### 5. Automation Scripts
- **scripts/setup-environment-db.sh** - Database management script
  - Create databases for any environment
  - Run migrations
  - Seed test data
  - Backup and restore databases
  - Reset databases
  - Show database status
  - Supports: production, staging, beta, dev

### 6. Documentation Updates
- **README.md** - Updated with environment information
  - Added "Deployment Environments" section
  - Links to detailed environment documentation
  - Quick reference to all environments

## Environment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     GitHub Repository                        │
├─────────────────────────────────────────────────────────────┤
│  main branch      →  Production  (hypnose-stammtisch.de)    │
│  staging branch   →  Staging     (staging.hypnose-...)      │
│  beta branch      →  Beta        (beta.hypnose-...)         │
└─────────────────────────────────────────────────────────────┘
                              ↓
                    GitHub Actions Workflows
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                         Server                               │
├─────────────────────────────────────────────────────────────┤
│  /var/www/hypnose-stammtisch.de/                           │
│    ├── dist/              (production frontend)             │
│    ├── staging/           (staging environment)             │
│    │   ├── dist/          (staging frontend)                │
│    │   └── backend/       (staging backend on port 8001)    │
│    └── beta/              (beta environment)                │
│        ├── dist/          (beta frontend)                   │
│        └── backend/       (beta backend on port 8002)       │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    MySQL/MariaDB                             │
├─────────────────────────────────────────────────────────────┤
│  hypnose_stammtisch          (production database)          │
│  hypnose_stammtisch_staging  (staging database)             │
│  hypnose_stammtisch_beta     (beta database)                │
└─────────────────────────────────────────────────────────────┘
```

## Key Features

### Database Preparation Workflow
The staging environment specifically addresses the requirement for database preparation:

1. **Isolated Testing**: Separate database allows testing migrations without affecting production
2. **Data Verification**: Validate data integrity before production deployment
3. **Migration Testing**: Test database schema changes in a production-like environment
4. **Backup/Restore**: Easy backup and restore capabilities for safe testing

### Automated Deployment
- Push to `staging` branch automatically deploys to staging environment
- Push to `beta` branch automatically deploys to beta environment
- Push to `main` branch deploys to production (existing workflow)

### Environment Management
The setup script (`setup-environment-db.sh`) provides:
- One-command database creation
- Automated migration running
- Database backup/restore functionality
- Environment status checking
- Reset capabilities for testing

## Required GitHub Secrets

To enable automated deployments, the following secrets need to be configured:

### Staging Environment
- `STAGING_SFTP_HOST`
- `STAGING_SFTP_PORT`
- `STAGING_SFTP_USER`
- `STAGING_SFTP_PASS`
- `STAGING_SFTP_REMOTE_DIR`

### Beta Environment
- `BETA_SFTP_HOST`
- `BETA_SFTP_PORT`
- `BETA_SFTP_USER`
- `BETA_SFTP_PASS`
- `BETA_SFTP_REMOTE_DIR`

## Usage Examples

### Deploy to Staging
```bash
# Create and push staging branch
git checkout -b staging
git push -u origin staging

# Future deployments
git checkout staging
git merge main  # or cherry-pick specific commits
git push origin staging
```

### Setup Staging Database
```bash
# Create database and user
./scripts/setup-environment-db.sh staging create

# Run migrations
./scripts/setup-environment-db.sh staging migrate

# Check status
./scripts/setup-environment-db.sh staging status
```

### Prepare Database for Release
```bash
# 1. Backup production
./scripts/setup-environment-db.sh production backup

# 2. Test migration on staging
cd backend
php cli/cli.php migrate --dry-run

# 3. Apply migration
php cli/cli.php migrate

# 4. Verify
./scripts/setup-environment-db.sh staging status
```

## Testing Performed

✅ YAML syntax validation for all workflow files
✅ Shell script syntax validation
✅ Script help output verification
✅ Script status command functionality
✅ Configuration file structure review

## Benefits

1. **Risk Reduction**: Test database changes before production
2. **Parallel Development**: Beta and staging allow parallel workflows
3. **Automation**: Reduced manual deployment effort
4. **Isolation**: Each environment has its own database and configuration
5. **Flexibility**: Easy to switch between environments
6. **Documentation**: Comprehensive guides for setup and usage
7. **Monitoring**: Built-in status checking and logging

## Next Steps for Deployment

1. Configure GitHub secrets for staging and beta environments
2. Create `staging` and `beta` branches
3. Set up staging and beta subdomains with SSL certificates
4. Run the database setup script for each environment
5. Configure backend .env files with actual credentials
6. Set up systemd services for backend processes
7. Test deployments to each environment

## Files Added

- `.github/workflows/deploy-staging.yml`
- `.github/workflows/deploy-beta.yml`
- `backend/.env.staging.example`
- `backend/.env.beta.example`
- `nginx.staging.conf`
- `nginx.beta.conf`
- `docs/DEPLOYMENT_ENVIRONMENTS.md`
- `docs/STAGING_SETUP.md`
- `scripts/setup-environment-db.sh`

## Files Modified

- `README.md` - Added environment overview and documentation links

## Conclusion

This implementation provides a complete multi-environment deployment strategy with a focus on the staging environment for database preparation. The solution is automated, well-documented, and follows best practices for environment separation and security.

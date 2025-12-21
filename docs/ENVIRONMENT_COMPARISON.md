# Environment Comparison Matrix

This document provides a quick comparison of all deployment environments.

## Quick Reference Table

| Aspect | Production | Staging | Beta | Development |
|--------|-----------|---------|------|-------------|
| **Branch** | `main` | `staging` | `beta` | local |
| **Domain** | hypnose-stammtisch.de | staging.hypnose-stammtisch.de | beta.hypnose-stammtisch.de | localhost:5173 |
| **Database** | hypnose_stammtisch | hypnose_stammtisch_staging | hypnose_stammtisch_beta | hypnose_stammtisch_dev |
| **DB User** | hypnose_user | staging_user | beta_user | dev_user |
| **Backend Port** | 8000 | 8001 | 8002 | 8000 |
| **APP_ENV** | production | staging | beta | development |
| **Debug Mode** | ❌ false | ✅ true | ✅ true | ✅ true |
| **Cache Duration** | 1 year (static) | 1 hour | 30 min | none |
| **Auto Deploy** | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No |
| **SSL/HTTPS** | ✅ Required | ✅ Required | ✅ Required | ❌ Optional |

## Environment Purposes

### Production
- **Purpose**: Live environment serving real users
- **Data**: Real production data
- **Updates**: Only stable, tested releases
- **Access**: Public
- **Monitoring**: Critical - alerts required
- **Backup**: Daily automated backups
- **Testing**: Pre-deployment smoke tests only

### Staging
- **Purpose**: Pre-production database preparation and final validation
- **Data**: Production-like data or copy of production
- **Updates**: Release candidates only
- **Access**: Internal team + selected testers
- **Monitoring**: Important - review regularly
- **Backup**: Before major changes
- **Testing**: Full regression testing, database migration testing

### Beta
- **Purpose**: Early feature testing and integration testing
- **Data**: Test data, can be reset frequently
- **Updates**: New features as they're completed
- **Access**: Internal team + early testers
- **Monitoring**: Optional - check when issues reported
- **Backup**: Optional
- **Testing**: Feature testing, integration testing, UAT

### Development
- **Purpose**: Local development and debugging
- **Data**: Local test data
- **Updates**: Continuous during development
- **Access**: Individual developer only
- **Monitoring**: Developer's responsibility
- **Backup**: Not required
- **Testing**: Unit tests, local testing

## Configuration Files

| Environment | Nginx Config | Backend Config | Workflow File |
|------------|--------------|----------------|---------------|
| Production | `nginx.prod.conf` | `backend/.env` | `.github/workflows/deploy-sftp.yml` |
| Staging | `nginx.staging.conf` | `backend/.env.staging` | `.github/workflows/deploy-staging.yml` |
| Beta | `nginx.beta.conf` | `backend/.env.beta` | `.github/workflows/deploy-beta.yml` |
| Development | none | `backend/.env` | none |

## Deployment Process

### Production
1. Merge to `main` branch
2. GitHub Actions builds and deploys automatically
3. Monitor for issues
4. Rollback if necessary

### Staging
1. Merge to `staging` branch
2. GitHub Actions builds and deploys automatically
3. Run database migrations
4. Test thoroughly
5. Promote to production if successful

### Beta
1. Merge to `beta` branch
2. GitHub Actions builds and deploys automatically
3. Test new features
4. Gather feedback
5. Fix issues and promote to staging

### Development
1. Make changes locally
2. Run `npm run dev`
3. Test locally
4. Commit and push to feature branch
5. Create PR for review

## Feature Flags by Environment

| Feature | Production | Staging | Beta | Development |
|---------|-----------|---------|------|-------------|
| Debug Logging | ❌ | ✅ | ✅ | ✅ |
| Error Stack Traces | ❌ | ✅ | ✅ | ✅ |
| Test Data Seeds | ❌ | ⚠️ Optional | ✅ | ✅ |
| Analytics | ✅ | ❌ | ❌ | ❌ |
| Email Sending | ✅ Real | ✅ Real/Test | ⚠️ Test Only | ❌ |
| Rate Limiting | ✅ Strict | ⚠️ Relaxed | ⚠️ Relaxed | ❌ |
| CORS Origins | Production only | Staging only | Beta only | localhost |

## Database Management

### Production
- **Migrations**: Carefully planned, tested in staging first
- **Backups**: Automated daily + before deployments
- **Seeds**: Never
- **Reset**: Never
- **Access**: Restricted to admins only

### Staging
- **Migrations**: Test here before production
- **Backups**: Before migrations and major changes
- **Seeds**: Optional for testing
- **Reset**: Rare, when needed to mirror production
- **Access**: Database admins + developers

### Beta
- **Migrations**: Test new migration scripts
- **Backups**: Optional
- **Seeds**: Frequently for testing
- **Reset**: As needed for testing
- **Access**: Developers

### Development
- **Migrations**: Test locally first
- **Backups**: Not required
- **Seeds**: Frequently
- **Reset**: Frequently
- **Access**: Local developer

## When to Use Each Environment

### Use Production When:
- Deploying a stable, tested release
- Making critical hotfixes
- Serving real users
- Working with real data

### Use Staging When:
- Testing database migrations before production
- Validating release candidates
- Preparing databases for production release
- Final QA before production deployment
- Testing with production-like data
- Rehearsing deployment procedures

### Use Beta When:
- Testing new features before staging
- Running integration tests
- Conducting user acceptance testing (UAT)
- Demonstrating features to stakeholders
- Testing breaking changes
- Early feedback gathering

### Use Development When:
- Writing new code
- Debugging issues
- Testing locally
- Rapid iteration
- Experimenting with changes

## Promotion Path

```
Development (local)
       ↓
   Feature Branch
       ↓
      Beta ← Testing & Feedback
       ↓
    Staging ← Database Prep & Final QA
       ↓
  Production ← Stable Release
```

## Environment-Specific URLs

### Frontend
- Production: `https://hypnose-stammtisch.de`
- Staging: `https://staging.hypnose-stammtisch.de`
- Beta: `https://beta.hypnose-stammtisch.de`
- Development: `http://localhost:5173`

### Backend API
- Production: `https://hypnose-stammtisch.de/api`
- Staging: `https://staging.hypnose-stammtisch.de/api`
- Beta: `https://beta.hypnose-stammtisch.de/api`
- Development: `http://localhost:8000/api`

### Health Check Endpoints
```bash
# Production
curl https://hypnose-stammtisch.de/api/health

# Staging
curl https://staging.hypnose-stammtisch.de/api/health

# Beta
curl https://beta.hypnose-stammtisch.de/api/health

# Development
curl http://localhost:8000/api/health
```

## Security Considerations

| Security Aspect | Production | Staging | Beta | Development |
|----------------|-----------|---------|------|-------------|
| SSL/TLS | Required | Required | Required | Optional |
| Debug Mode | Disabled | Enabled | Enabled | Enabled |
| Error Details | Hidden | Shown | Shown | Shown |
| IP Restrictions | Optional | Recommended | Recommended | Not needed |
| Secrets | Unique | Unique | Unique | Can be generic |
| Password Strength | Maximum | High | High | Flexible |
| Session Timeout | 1 hour | 1 hour | 1 hour | 8 hours |

## Monitoring & Alerts

| Metric | Production | Staging | Beta | Development |
|--------|-----------|---------|------|-------------|
| Uptime Monitoring | ✅ Critical | ⚠️ Important | ℹ️ Nice to have | ❌ Not needed |
| Error Tracking | ✅ Critical | ✅ Important | ⚠️ Helpful | ❌ Not needed |
| Performance Metrics | ✅ Critical | ⚠️ Helpful | ℹ️ Optional | ❌ Not needed |
| Log Aggregation | ✅ Required | ✅ Important | ⚠️ Helpful | ❌ Not needed |
| Alert Notifications | ✅ Immediate | ⚠️ Delayed | ℹ️ Optional | ❌ Not needed |

## Resource Allocation

### Server Resources (Recommended)

| Resource | Production | Staging | Beta | Development |
|----------|-----------|---------|------|-------------|
| CPU | 2+ cores | 1-2 cores | 1 core | Local |
| RAM | 4+ GB | 2 GB | 1 GB | Local |
| Disk | 50+ GB | 20 GB | 10 GB | Local |
| Bandwidth | High | Medium | Low | Local |

## Support & Maintenance

| Aspect | Production | Staging | Beta | Development |
|--------|-----------|---------|------|-------------|
| Support Hours | 24/7 | Business hours | Best effort | Self-service |
| SLA | Critical | Important | None | None |
| Update Window | Scheduled | Flexible | Anytime | Anytime |
| Rollback Plan | Required | Recommended | Optional | Not needed |

## Common Commands by Environment

### Check Status
```bash
# Production
sudo systemctl status hypnose-production.service

# Staging
sudo systemctl status hypnose-staging.service

# Beta
sudo systemctl status hypnose-beta.service

# Development
npm run dev
```

### View Logs
```bash
# Production
sudo journalctl -u hypnose-production.service -f

# Staging
sudo journalctl -u hypnose-staging.service -f

# Beta
sudo journalctl -u hypnose-beta.service -f

# Development
# Logs appear in terminal
```

### Database Management
```bash
# Production
./scripts/setup-environment-db.sh production backup
./scripts/setup-environment-db.sh production status

# Staging
./scripts/setup-environment-db.sh staging backup
./scripts/setup-environment-db.sh staging migrate

# Beta
./scripts/setup-environment-db.sh beta reset
./scripts/setup-environment-db.sh beta seed

# Development
npm run backend:migrate
npm run backend:seed
```

## Summary

- **Production**: Live environment, maximum stability, real data
- **Staging**: Pre-production testing, database preparation, final validation
- **Beta**: Feature testing, integration testing, early feedback
- **Development**: Local development, rapid iteration, experimentation

Choose the right environment for your task to maintain proper separation of concerns and ensure safe, reliable deployments.

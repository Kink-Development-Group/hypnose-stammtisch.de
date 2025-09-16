# Security Implementation: Account Lockout & IP Blocking

This document describes the implementation of account lockout and IP blocking security features to protect against brute-force attacks.

## Overview

The security system provides protection against brute-force login attacks by:

1. **Account Lockout**: Temporarily locking user accounts after repeated failed login attempts
2. **IP Blocking**: Banning source IP addresses that generate excessive failed login attempts
3. **Head Admin Protection**: Special handling for head admin accounts (IP ban only, no account lock)
4. **Comprehensive Auditing**: Complete logging of all security events
5. **Admin Management**: Tools for administrators to manage locks and bans

## Features

### Core Security Features

- **Configurable Thresholds**: Failed attempt limits, time windows, and ban durations
- **Time-Window Based**: Only counts attempts within a configurable time window
- **Role-Based Protection**: Head admin accounts cannot be locked (IP ban only)
- **Automatic Cleanup**: Expired bans are automatically cleaned up
- **Race Condition Safe**: Uses atomic database operations to prevent inconsistencies

### Admin Management Features

- View failed login history
- View active and historical IP bans
- View locked accounts
- Unlock accounts manually
- Remove IP bans manually
- Ban IP addresses manually
- Security statistics dashboard
- Cleanup expired bans

## Configuration

Add these environment variables to your `.env` file:

```bash
# Failed Login Protection
MAX_FAILED_ATTEMPTS=5
TIME_WINDOW_SECONDS=900
IP_BAN_DURATION_SECONDS=3600
ACCOUNT_LOCK_DURATION_SECONDS=3600
HEAD_ADMIN_ROLE_NAME=head
```

### Configuration Options

| Variable                        | Default | Description                                            |
| ------------------------------- | ------- | ------------------------------------------------------ |
| `MAX_FAILED_ATTEMPTS`           | 5       | Number of failed attempts before triggering protection |
| `TIME_WINDOW_SECONDS`           | 900     | Time window (15 min) for counting failed attempts      |
| `IP_BAN_DURATION_SECONDS`       | 3600    | IP ban duration (1 hour), 0 = permanent                |
| `ACCOUNT_LOCK_DURATION_SECONDS` | 3600    | Account lock duration (1 hour), 0 = manual unlock only |
| `HEAD_ADMIN_ROLE_NAME`          | head    | Role name for head administrators                      |

## Database Schema

### New Tables

The implementation adds three new database structures:

#### `failed_logins` Table

```sql
CREATE TABLE failed_logins (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  account_id INT NULL,
  username_entered VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_account_id (account_id),
  INDEX idx_ip_address (ip_address),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `ip_bans` Table

```sql
CREATE TABLE ip_bans (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255) NOT NULL,
  banned_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_ip_address (ip_address),
  INDEX idx_expires_at (expires_at),
  INDEX idx_is_active (is_active),
  FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `users` Table Extensions

```sql
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN locked_reason VARCHAR(255) NULL;
ALTER TABLE users ADD INDEX idx_locked_until (locked_until);
```

## Implementation Details

### Core Classes

#### `FailedLoginTracker`

- Records failed login attempts
- Tracks attempt counts per account and IP
- Handles account locking logic
- Manages lock expiration
- Provides admin functions for account management

#### `IpBanManager`

- Manages IP address bans
- Checks ban status with expiration handling
- Provides proper IP detection with proxy support
- Handles ban cleanup and removal
- Provides admin functions for IP management

#### `AdminSecurityController`

- REST API endpoints for security management
- Provides admin interface for viewing and managing security events
- Handles authentication and authorization for security functions

### Security Flow

#### Login Process

1. **IP Ban Check**: Before any authentication, check if source IP is banned
2. **Rate Limiting**: Apply standard rate limiting (existing functionality)
3. **Authentication**: Attempt user authentication
4. **Failed Login Handling**: If authentication fails:
   - Record failed attempt with IP and account details
   - Count recent attempts for both account and IP
   - If threshold exceeded:
     - For normal accounts: Lock account AND ban IP
     - For head admin accounts: Ban IP only (account remains active)
5. **Success Handling**: If authentication succeeds:
   - Clear failed attempt history for the account
   - Continue with normal 2FA flow

#### Security Checks

- **Account Lock Check**: Verify account is not locked before authentication
- **IP Ban Check**: Block requests from banned IPs at the earliest stage
- **Time Window**: Only count attempts within the configured time window
- **Expiration**: Automatically handle expired locks and bans

### API Endpoints

All security endpoints require admin authentication (`admin` or `head` role):

```
GET  /admin/security/failed-logins     - Get failed login history
GET  /admin/security/ip-bans           - Get IP ban list
GET  /admin/security/locked-accounts   - Get locked accounts
GET  /admin/security/stats             - Get security statistics
POST /admin/security/unlock-account    - Unlock an account manually
POST /admin/security/remove-ip-ban     - Remove an IP ban manually
POST /admin/security/ban-ip            - Ban an IP address manually
POST /admin/security/cleanup-expired-bans - Clean up expired bans (head admin only)
```

### Example API Usage

#### Unlock Account

```bash
curl -X POST /admin/security/unlock-account \
  -H "Content-Type: application/json" \
  -d '{"account_id": 123}'
```

#### Remove IP Ban

```bash
curl -X POST /admin/security/remove-ip-ban \
  -H "Content-Type: application/json" \
  -d '{"ip_address": "192.168.1.100"}'
```

#### Get Security Stats

```bash
curl -X GET /admin/security/stats
```

## Deployment

### 1. Apply Database Migration

Run the migration to create the required tables:

```bash
php backend/migrations/migrate.php
```

Or apply manually:

```bash
php /path/to/apply_security_migration.php
```

### 2. Update Configuration

Add the security configuration variables to your `.env` file.

### 3. Update Environment

Ensure your web server/application has the updated code deployed.

### 4. Test Implementation

Run the security tests to verify proper functionality:

```bash
php backend/vendor/bin/phpunit tests/Utils/FailedLoginTrackerTest.php
php backend/vendor/bin/phpunit tests/Utils/IpBanManagerTest.php
php backend/vendor/bin/phpunit tests/Integration/SecurityFlowTest.php
```

## Monitoring and Maintenance

### Regular Maintenance

1. **Monitor Security Stats**: Regularly check the security statistics dashboard
2. **Review Failed Logins**: Investigate patterns in failed login attempts
3. **Clean Up Expired Bans**: Use the cleanup endpoint or set up a cron job
4. **Audit Log Review**: Monitor audit logs for security events

### Recommended Cron Jobs

```bash
# Clean up expired bans daily
0 2 * * * curl -X POST https://your-domain.com/admin/security/cleanup-expired-bans

# Clean up old failed login records (older than 30 days)
0 3 * * * mysql -e "DELETE FROM failed_logins WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### Alerts and Monitoring

Consider setting up alerts for:

- High number of failed login attempts
- Multiple IP bans in a short time period
- Repeated attempts from banned IPs
- Head admin account targeted attacks

## Security Considerations

### Privacy and GDPR Compliance

- **IP Address Storage**: IP addresses are stored for security purposes with limited retention
- **Data Retention**: Consider implementing automatic cleanup of old security logs
- **Purpose Limitation**: Data is only used for security and not for other purposes

### Performance Considerations

- **Database Indexes**: Proper indexes on time-based and IP-based queries
- **Cleanup**: Regular cleanup of old records to prevent table bloat
- **Caching**: Consider caching ban status for high-traffic scenarios

### Additional Recommendations

1. **Rate Limiting**: The existing rate limiting provides additional protection
2. **Monitoring**: Implement monitoring for security events
3. **Backup Strategy**: Ensure security tables are included in backups
4. **Testing**: Regularly test security functionality
5. **Documentation**: Keep security procedures documented

## Troubleshooting

### Common Issues

1. **Migration Fails**: Check database permissions and existing table structure
2. **IP Detection**: Verify proxy headers are properly configured
3. **False Positives**: Review time windows and thresholds
4. **Admin Access**: Ensure admin users have proper roles

### Debug Mode

Enable debug mode in your configuration to get detailed error messages during development.

### Log Locations

Security events are logged to:

- **Audit Logs**: `audit_logs` table in database
- **Failed Logins**: `failed_logins` table in database
- **Application Logs**: PHP error logs for system errors

## Testing

The implementation includes comprehensive tests:

- **Unit Tests**: Test individual components and functions
- **Integration Tests**: Test complete security flows
- **Edge Case Tests**: Test time windows, race conditions, and edge cases

Run tests with:

```bash
php backend/vendor/bin/phpunit tests/
```

## Support

For issues or questions regarding the security implementation:

1. Check the troubleshooting section
2. Review audit logs for security events
3. Check database tables for data consistency
4. Verify configuration settings

## Security Advisory

This implementation provides robust protection against brute-force attacks but should be part of a comprehensive security strategy including:

- Strong password policies
- Regular security updates
- Network-level protection (firewalls, DDoS protection)
- Regular security audits
- User security education

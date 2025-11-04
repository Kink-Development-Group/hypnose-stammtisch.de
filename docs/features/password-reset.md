# Password Reset via Email

This document describes the password reset functionality for admin users, allowing them to securely reset their password via email.

## Overview

The password reset feature provides a secure, token-based flow for admin users who have forgotten their password. The implementation follows security best practices including:

- Token-based authentication with expiration
- Rate limiting to prevent abuse
- IP-based tracking and audit logging
- Protection against user enumeration attacks
- Comprehensive security measures

## User Flow

1. **Request Password Reset**
   - User visits the admin login page
   - Clicks "Forgot Password" link
   - Enters their email address
   - Receives a generic success message (prevents email enumeration)

2. **Receive Email**
   - If the email exists in the system and the account is active, an email is sent
   - Email contains a secure reset link with a unique token
   - Token is valid for 60 minutes

3. **Reset Password**
   - User clicks the link in the email
   - Redirected to password reset page with pre-filled token
   - Enters new password (minimum 8 characters)
   - Submits the form
   - Receives confirmation and can log in with new password

## API Endpoints

### Request Password Reset

**Endpoint:** `POST /api/admin/auth/password-reset/request`

**Request Body:**
```json
{
  "email": "admin@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail mit Anweisungen zum Zurücksetzen des Passworts gesendet."
}
```

**Rate Limiting:** 3 requests per IP per 15 minutes

### Verify Token

**Endpoint:** `GET /api/admin/auth/password-reset/verify?token={token}`

**Response (Valid Token):**
```json
{
  "success": true,
  "data": {
    "valid": true
  },
  "message": "Token ist gültig"
}
```

**Response (Invalid/Expired Token):**
```json
{
  "success": false,
  "message": "Token ist abgelaufen",
  "code": 400
}
```

### Reset Password

**Endpoint:** `POST /api/admin/auth/password-reset/reset`

**Request Body:**
```json
{
  "token": "64-character-hex-token",
  "password": "newSecurePassword123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "success": true
  },
  "message": "Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden."
}
```

**Response (Invalid Token):**
```json
{
  "success": false,
  "message": "Token nicht gefunden oder ungültig",
  "code": 404
}
```

## Security Features

### Token Security

- **Format:** 64-character hexadecimal string (256 bits of entropy)
- **Storage:** Stored hashed in database
- **Expiration:** 60 minutes from creation
- **Single Use:** Token is marked as used after successful password reset
- **Automatic Invalidation:** Previous unused tokens are invalidated when a new request is made

### Rate Limiting

- **Request Reset:** 3 attempts per IP per 15 minutes
- **Purpose:** Prevents brute force attacks and email flooding

### User Enumeration Protection

- Always returns the same success message regardless of whether the email exists
- No indication whether the account is active or inactive
- Prevents attackers from discovering valid email addresses

### Audit Logging

All password reset events are logged:
- `password_reset.requested` - Password reset was requested
- `password_reset.unknown_email` - Request for non-existent email
- `password_reset.inactive_account` - Request for inactive account
- `password_reset.email_failed` - Email sending failed
- `password_reset.invalid_token` - Invalid token used
- `password_reset.token_reuse` - Attempt to reuse token
- `password_reset.token_expired` - Expired token used
- `password_reset.completed` - Password successfully reset
- `password_reset.rate_limited` - Rate limit exceeded

### IP Tracking

- IP address is recorded with each token
- IP is logged in audit trail
- Helps identify suspicious patterns

## Database Schema

### Table: `password_reset_tokens`

```sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_token (token),
  INDEX idx_user_id (user_id),
  INDEX idx_expires_at (expires_at),
  INDEX idx_used_at (used_at),
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Email Template

The password reset email is sent in both HTML and plain text formats and includes:

- Personalized greeting with username
- Clear explanation of the password reset request
- Prominent call-to-action button with reset link
- Plain text link as alternative
- Expiration time warning (60 minutes)
- Security notice for users who didn't request the reset
- Professional branding

### Email Configuration

Configure email settings in `.env`:

```env
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@hypnose-stammtisch.de
MAIL_FROM_NAME='Hypnose Stammtisch'
MAIL_SMTP_ENABLED=true
```

## Maintenance

### Cleanup Expired Tokens

A maintenance method is provided to clean up old tokens:

```php
PasswordResetController::cleanupExpiredTokens();
```

This method:
- Removes tokens expired more than 24 hours ago
- Removes used tokens older than 24 hours
- Returns the number of deleted tokens
- Logs cleanup action to audit log

**Recommended Schedule:** Daily via cron job

Example cron configuration:
```bash
# Daily at 3 AM
0 3 * * * cd /path/to/backend && php -r "require 'vendor/autoload.php'; \HypnoseStammtisch\Controllers\PasswordResetController::cleanupExpiredTokens();"
```

## Implementation Details

### File Structure

```
backend/
├── src/
│   ├── Controllers/
│   │   └── PasswordResetController.php  # Main controller
│   └── Utils/
│       └── EmailService.php             # Email service (DRY)
├── migrations/
│   └── 007_password_reset_tokens.sql    # Database migration
└── api/
    └── admin.php                         # API routes
```

### Dependencies

- **PHPMailer:** Email sending
- **Database:** Token storage
- **RateLimiter:** Request throttling
- **AuditLogger:** Security event logging
- **Validator:** Input validation
- **Config:** Configuration management

### Design Principles

- **OOP:** Object-oriented design with clear separation of concerns
- **DRY:** EmailService utility for reusable email functionality
- **TSDoc:** Comprehensive documentation for all methods
- **i18n:** German language support in all user-facing messages
- **Security:** Multiple layers of protection against attacks

## Testing

### Manual Testing Checklist

1. **Happy Path:**
   - [ ] Request password reset with valid email
   - [ ] Receive email with reset link
   - [ ] Click link and verify token
   - [ ] Reset password successfully
   - [ ] Login with new password

2. **Error Cases:**
   - [ ] Request with non-existent email (should still show success)
   - [ ] Request with inactive account (should still show success)
   - [ ] Use expired token (should fail)
   - [ ] Reuse token (should fail)
   - [ ] Use invalid token format (should fail)
   - [ ] Exceed rate limit (should fail with 429)
   - [ ] Set password less than 8 characters (should fail)

3. **Security:**
   - [ ] Verify IP is logged in audit trail
   - [ ] Verify old tokens are invalidated on new request
   - [ ] Verify token expiration works correctly
   - [ ] Verify email enumeration protection

## Troubleshooting

### Email Not Received

1. Check SMTP configuration in `.env`
2. Verify `MAIL_SMTP_ENABLED=true`
3. Check server logs for PHPMailer errors
4. Verify firewall allows outbound SMTP connections

### Token Invalid/Expired

- Tokens expire after 60 minutes
- Check system time is correct
- Verify token wasn't already used
- Request a new reset link

### Rate Limit Issues

- Rate limit: 3 requests per 15 minutes per IP
- Wait for cooldown period
- Contact administrator if legitimate use is blocked

## Future Enhancements

Potential improvements for future versions:

- [ ] Configurable token expiration time
- [ ] Email templates with custom branding
- [ ] SMS-based password reset option
- [ ] Multi-language support beyond German
- [ ] Admin panel for viewing reset requests
- [ ] Suspicious activity detection and blocking

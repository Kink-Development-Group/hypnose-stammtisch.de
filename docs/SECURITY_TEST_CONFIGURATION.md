# Security Test Configuration

This document explains the security improvements made to the test suite to eliminate hardcoded passwords.

## Problem

The original test files contained hardcoded passwords like `'password123'` and `'admin123'`, which is a security anti-pattern even in test environments.

## Solution

The test suite now implements a secure credential management system:

### 1. Environment Variable Support

Test passwords can be configured via environment variables:

- `TEST_USER_PASSWORD`: Password for test user accounts
- `TEST_ADMIN_PASSWORD`: Password for test admin accounts

### 2. Automatic Random Generation

If environment variables are not set, the system automatically generates secure random passwords using `random_bytes()`.

### 3. Implementation Details

```php
// Generate secure test passwords using environment variables or random generation
self::$testUserPassword = $_ENV['TEST_USER_PASSWORD'] ?? bin2hex(random_bytes(16));
self::$testAdminPassword = $_ENV['TEST_ADMIN_PASSWORD'] ?? bin2hex(random_bytes(16));
```

## Configuration

### Option 1: Environment Variables (Recommended for CI/CD)

Set environment variables in your test environment:

```bash
export TEST_USER_PASSWORD="SecureTestPassword123!"
export TEST_ADMIN_PASSWORD="SecureAdminTestPassword456!"
```

### Option 2: .env.test File (For local development)

1. Copy `.env.test.example` to `.env.test`
2. Configure your test credentials
3. Ensure `.env.test` is in your `.gitignore`

### Option 3: Automatic Generation (Default)

If no configuration is provided, secure random passwords are automatically generated.

## Security Benefits

1. **No Hardcoded Secrets**: Eliminates hardcoded passwords from source code
2. **Environment-Specific**: Different passwords can be used in different environments
3. **Automatic Fallback**: Secure random generation when no configuration is provided
4. **CI/CD Friendly**: Works seamlessly in automated testing environments

## Best Practices

- Never commit test passwords to version control
- Use different passwords for test and production environments
- Regularly rotate test credentials
- Use the automatic generation feature in CI/CD pipelines
- Keep test environment variables secure and properly scoped

## Migration Guide

Existing tests that referenced hardcoded passwords have been updated to use the new secure credential system. No changes are required for test execution, but you may want to configure specific test credentials for your environment.

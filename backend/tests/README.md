# Test Suite Security Guidelines

This document outlines the security practices implemented in our test suite to ensure secure testing without compromising sensitive information.

## Security Principles

### 1. No Hardcoded Credentials

All test credentials are generated dynamically or loaded from secure environment variables. Hardcoded passwords are strictly prohibited.

### 2. Environment-Based Configuration

Test credentials can be configured via environment variables for different testing environments while maintaining security.

### 3. Automatic Secure Generation

When no explicit credentials are provided, the system automatically generates cryptographically secure random passwords.

## Implementation

### Test Credential Management

The `SecureTestCredentials` utility class provides secure credential management:

```php
use HypnoseStammtisch\Tests\Utils\SecureTestCredentials;

// Get secure password for user type
$userPassword = SecureTestCredentials::getPassword('user');
$adminPassword = SecureTestCredentials::getPassword('admin');

// Generate password with specific requirements
$complexPassword = SecureTestCredentials::generatePasswordWithRequirements([
    'length' => 20,
    'special_chars' => true,
    'numbers' => true,
    'uppercase' => true
]);
```

### Environment Variables

Configure test credentials using environment variables:

```bash
# User credentials
export TEST_USER_PASSWORD="SecureUserPassword123!"
export TEST_ADMIN_PASSWORD="SecureAdminPassword456!"

# Or load from .env.test file (not committed to version control)
```

### Best Practices for Test Development

1. **Use SecureTestCredentials class** for all credential management
2. **Never hardcode passwords** in test files
3. **Use environment variables** for configuration-specific credentials
4. **Generate random credentials** for isolated tests
5. **Clean up credentials** after test completion

### Example Test Implementation

```php
class MySecurityTest extends TestCase
{
    private static $testPassword;

    public static function setUpBeforeClass(): void
    {
        // Use secure credential management
        self::$testPassword = SecureTestCredentials::getPassword('user');
    }

    public function testUserAuthentication(): void
    {
        $user = $this->createTestUser(self::$testPassword);
        $result = AuthService::authenticate('test@example.com', self::$testPassword);
        $this->assertTrue($result['success']);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up credentials
        SecureTestCredentials::clearCredentials();
    }
}
```

## Security Benefits

- **No Credential Exposure**: Eliminates risk of hardcoded credentials in source code
- **Environment Flexibility**: Different credentials for different environments
- **Audit Trail**: Clear separation between test and production credentials
- **Automated Security**: Automatic secure password generation
- **Compliance**: Meets security best practices for test environments

## Migration from Legacy Tests

When updating existing tests that use hardcoded passwords:

1. Replace hardcoded passwords with `SecureTestCredentials::getPassword()`
2. Add environment variable configuration if needed
3. Update test setup to use secure credential management
4. Verify tests still pass with new credential system

## Environment Setup

### Local Development

1. Copy `.env.test.example` to `.env.test`
2. Configure your test credentials (optional)
3. Ensure `.env.test` is in `.gitignore`

### CI/CD Environments

Set environment variables in your CI/CD pipeline:

- `TEST_USER_PASSWORD`
- `TEST_ADMIN_PASSWORD`
- Other test-specific variables as needed

## Security Checklist

- [ ] No hardcoded passwords in test files
- [ ] Environment variables properly configured
- [ ] `.env.test` files excluded from version control
- [ ] Secure random generation as fallback
- [ ] Credential cleanup in test teardown
- [ ] Documentation updated for new security practices

## Troubleshooting

### Tests Failing After Migration

- Verify environment variables are set correctly
- Check that the `SecureTestCredentials` class is available
- Ensure test database is properly configured
- Verify credential generation is working

### Environment Variable Issues

- Check `.env.test` file is properly loaded
- Verify environment variable names match expectations
- Ensure proper escaping of special characters in passwords

For more information, see the main security documentation in `/docs/SECURITY_TEST_CONFIGURATION.md`.

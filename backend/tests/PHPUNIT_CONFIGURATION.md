# PHPUnit Configuration for Hypnose Stammtisch Backend

## Overview

This document explains the PHPUnit test configuration setup for the backend application.

## Configuration Files

### phpunit.xml

The main PHPUnit configuration file located in the backend root directory contains:

- **Test Suites**: Organized into Unit and Integration test directories
- **Environment Variables**: Security test configuration and credentials
- **Coverage Settings**: Source code coverage configuration
- **Logging**: Test reports in multiple formats

### .vscode/settings.json

VS Code specific settings that help Intelephense PHP language server recognize:

- PHPUnit classes and methods
- Proper PHP version and extensions
- Vendor autoload paths

## Running Tests

### All Tests

```bash
cd backend
vendor/bin/phpunit
```

### Specific Test Suite

```bash
# Integration tests only
vendor/bin/phpunit --testsuite=Integration

# Unit tests only
vendor/bin/phpunit --testsuite=Unit
```

### Specific Test File

```bash
vendor/bin/phpunit tests/Integration/SecurityLockoutIntegrationTest.php
```

### With Coverage Report

```bash
vendor/bin/phpunit --coverage-html tests/reports/coverage
```

## Test Environment Configuration

### Security Test Settings

The following environment variables are automatically set for tests:

- `MAX_FAILED_ATTEMPTS=3`
- `TIME_WINDOW_SECONDS=300`
- `IP_BAN_DURATION_SECONDS=600`
- `ACCOUNT_LOCK_DURATION_SECONDS=600`
- `HEAD_ADMIN_ROLE_NAME=head`

### Test Credentials

Optional environment variables for test user passwords:

- `TEST_USER_PASSWORD` - If not set, secure random password is generated
- `TEST_ADMIN_PASSWORD` - If not set, secure random password is generated

## IDE Integration

### VS Code Setup

The project includes VS Code settings for optimal PHP development:

1. **Intelephense Configuration**: Properly configured for PHP 8.3+ with all necessary stubs
2. **PHPUnit Recognition**: Vendor paths included for proper class resolution
3. **File Exclusions**: Performance optimized by excluding unnecessary directories

### Troubleshooting Intelephense Errors

If you see "Undefined type 'PHPUnit\Framework\TestCase'" errors:

1. **Ensure PHPUnit is installed**:

   ```bash
   composer install --dev
   ```

2. **Regenerate autoloader**:

   ```bash
   composer dump-autoload
   ```

3. **Restart PHP Language Server**:
   - Open VS Code Command Palette (Ctrl/Cmd + Shift + P)
   - Run "PHP Intelephense: Restart"

4. **Check VS Code Settings**:
   - Ensure `.vscode/settings.json` is properly configured
   - Verify `intelephense.environment.includePaths` includes `backend/vendor`

## Test Structure

### Directory Layout

```
backend/tests/
├── Integration/          # Integration tests
├── Unit/                # Unit tests
├── Utils/               # Test utility classes
├── reports/             # Generated test reports (gitignored)
└── .env.test.example    # Example test environment file
```

### Base Test Classes

- **Integration Tests**: Extend `PHPUnit\Framework\TestCase`
- **Security Tests**: Use `SecureTestCredentials` for credential management
- **Database Tests**: Include database setup/teardown methods

## Security Considerations

### Test Isolation

- Each test method runs in isolation
- Database state is reset between tests
- Temporary test data is cleaned up automatically

### Credential Security

- No hardcoded passwords in test files
- Environment-based credential configuration
- Automatic secure password generation as fallback

### Test Data Management

- Test users are created with unique identifiers
- All test data is properly cleaned up after test execution
- Test database is separate from development/production

## CI/CD Integration

### GitHub Actions

The test configuration is designed to work seamlessly with CI/CD:

- Environment variables can be set in GitHub Actions
- Tests run in isolated containers
- Coverage reports can be generated and stored

### Local Development

For local development:

1. Copy `.env.test.example` to `.env.test` (optional)
2. Configure test-specific environment variables if needed
3. Run tests using composer scripts or directly with PHPUnit

## Performance Optimization

### Autoloader Optimization

- Optimized autoloader for faster class loading
- Proper PSR-4 namespace configuration
- Excluded unnecessary files and directories

### Test Execution

- Parallel test execution support
- Memory-efficient test isolation
- Fast test database setup using SQLite in-memory

## Debugging Tests

### Running Single Tests

```bash
vendor/bin/phpunit --filter testNormalUserFailedLoginFlow
```

### Debug Output

```bash
vendor/bin/phpunit --testdox --verbose
```

### Generate Test Documentation

```bash
vendor/bin/phpunit --testdox-html tests/reports/testdox.html
```

This configuration ensures reliable, secure, and maintainable testing for the Hypnose Stammtisch backend application.

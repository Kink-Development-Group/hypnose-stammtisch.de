<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use ReflectionClass;

/**
 * Test IP validation security improvements in IpBanManager
 */
class IpBanManagerSecurityTest extends TestCase
{
    private array $originalServer;
    private array $originalEnv;
    private string|false $originalErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        // Redirect error_log to null device to prevent "unexpected output" in PHPUnit.
        // Database::connect() calls error_log() before throwing, which gets captured.
        $this->originalErrorLog = ini_get('error_log');
        ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');

        // Backup original superglobals
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;

        // Reset $_SERVER for clean test state
        $_SERVER = [];

        // Reset Config cache so that $_ENV changes in tests take effect
        Config::reset();

        // Reset Database static connection to prevent stale connections
        $dbRef = new ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setValue(null, null);

        // Disable AuditLogger to prevent any DB access during unit tests.
        // Setting isLogging=true makes AuditLogger::log() return immediately.
        $alRef = new ReflectionClass(AuditLogger::class);
        $alProp = $alRef->getProperty('isLogging');
        $alProp->setValue(null, true);
    }

    protected function tearDown(): void
    {
        // Restore original superglobals
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;

        // Restore error_log setting
        ini_set('error_log', $this->originalErrorLog ?: '');

        // Reset Config and Database back to original state
        Config::reset();
        $dbRef = new ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setValue(null, null);

        // Re-enable AuditLogger
        $alRef = new ReflectionClass(AuditLogger::class);
        $alProp = $alRef->getProperty('isLogging');
        $alProp->setValue(null, false);

        parent::tearDown();
    }

    /**
     * Test that private IPs are rejected by default (production mode)
     */
    public function testPrivateIPsRejectedByDefault(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '';

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Private IPv4 should be rejected');

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Private IPv4 10.x should be rejected');

        $_SERVER['REMOTE_ADDR'] = '172.16.0.1';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Private IPv4 172.16.x should be rejected');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Localhost should be rejected in production');
    }

    /**
     * Test that private IPs are accepted when explicitly allowed
     */
    public function testPrivateIPsAcceptedWhenConfigured(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'true';
        $_ENV['TRUSTED_PROXIES'] = '';

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('192.168.1.100', $ip, 'Private IP should be accepted when configured');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('127.0.0.1', $ip, 'Localhost should be accepted when configured');
    }

    /**
     * Test that forwarded headers are ignored from untrusted sources
     */
    public function testUntrustedForwardedHeadersIgnored(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.1.0/24';

        $_SERVER['REMOTE_ADDR'] = '203.0.113.1'; // Public IP, not in trusted proxies
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip, 'Should use REMOTE_ADDR when proxy is not trusted');
    }

    /**
     * Test that forwarded headers are accepted from trusted sources
     */
    public function testTrustedForwardedHeadersAccepted(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.1.0/24';

        $_SERVER['REMOTE_ADDR'] = '10.0.1.5'; // IP within trusted proxy range
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('198.51.100.1', $ip, 'Should use forwarded IP from trusted proxy');
    }

    /**
     * Test CIDR range validation
     */
    public function testCIDRRangeValidation(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '192.168.1.0/24,10.0.0.0/16';

        // Test within CIDR range: trusted proxy forwards a public IP
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip, 'IP within CIDR range should be trusted');

        // Test outside CIDR range: untrusted proxy, forwarded header ignored.
        // 192.168.2.1 is private and ALLOW_PRIVATE_IPS is false, so it gets rejected to 0.0.0.0
        $_SERVER['REMOTE_ADDR'] = '192.168.2.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.2';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Private REMOTE_ADDR outside CIDR should be rejected when private IPs disallowed');
    }

    /**
     * Test multiple forwarded IPs (comma-separated)
     */
    public function testMultipleForwardedIPs(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.1.0/24';

        $_SERVER['REMOTE_ADDR'] = '10.0.1.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 192.168.1.1, 203.0.113.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('198.51.100.1', $ip, 'Should use first valid public IP from forwarded list');
    }

    /**
     * Test invalid IP addresses are rejected
     */
    public function testInvalidIPsRejected(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'true';
        $_ENV['TRUSTED_PROXIES'] = '';

        $_SERVER['REMOTE_ADDR'] = 'invalid-ip';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Invalid IP should be rejected');

        $_SERVER['REMOTE_ADDR'] = '999.999.999.999';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Out of range IP should be rejected');
    }

    /**
     * Test IPv6 support
     */
    public function testIPv6Support(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '2001:db8::/32';

        $_SERVER['REMOTE_ADDR'] = '2001:db8::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8:85a3::1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('2001:db8:85a3::1', $ip, 'IPv6 should be handled correctly');
    }

    /**
     * Test edge case: empty or malformed headers
     */
    public function testMalformedHeaders(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.1.0/24';

        $_SERVER['REMOTE_ADDR'] = '10.0.1.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';

        $ip = IpBanManager::getClientIP();
        // 10.0.1.5 is private and ALLOW_PRIVATE_IPS is false → rejected to 0.0.0.0
        $this->assertEquals('0.0.0.0', $ip, 'Should fallback to 0.0.0.0 when forwarded header is empty and REMOTE_ADDR is private');

        $_SERVER['HTTP_X_FORWARDED_FOR'] = ',,,';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Should fallback to 0.0.0.0 when forwarded header is malformed and REMOTE_ADDR is private');
    }

    /**
     * Test that configuration defaults are secure
     */
    public function testSecureDefaults(): void
    {
        unset($_ENV['ALLOW_PRIVATE_IPS']);
        unset($_ENV['TRUSTED_PROXIES']);

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $ip = IpBanManager::getClientIP();
        $this->assertEquals('0.0.0.0', $ip, 'Should reject private IPs by default');

        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip, 'Should not trust forwarded headers without explicit proxy configuration');
    }

    /**
     * Test Cloudflare header support
     */
    public function testCloudflareHeaderSupport(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '103.21.244.0/22,103.22.200.0/22';

        $_SERVER['REMOTE_ADDR'] = '103.21.244.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('198.51.100.1', $ip, 'Should use Cloudflare connecting IP from trusted Cloudflare proxy');
    }

    /**
     * Test that proxy validation prevents header spoofing
     */
    public function testProxyValidationPreventsHeaderSpoofing(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';

        $_SERVER['REMOTE_ADDR'] = '203.0.113.1'; // Public IP (not trusted)
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '10.0.0.1';

        $ip = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip, 'Should ignore spoofed headers from untrusted source');
    }

    /**
     * Test that untrusted proxy header attempts are rate-limited.
     * This prevents log spam during coordinated attacks with many spoofed headers.
     */
    public function testUntrustedProxyHeaderAttemptsAreRateLimited(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';
        $_ENV['AUDIT_UNTRUSTED_PROXY_MAX_LOGS'] = '3';
        $_ENV['AUDIT_UNTRUSTED_PROXY_PERIOD'] = '60';

        $attackIP = '203.0.113.1';

        for ($i = 1; $i <= 3; $i++) {
            $_SERVER['REMOTE_ADDR'] = $attackIP;
            $_SERVER['HTTP_X_FORWARDED_FOR'] = "198.51.100.{$i}";

            $ip = IpBanManager::getClientIP();
            $this->assertEquals($attackIP, $ip, "Attempt {$i}: Should use REMOTE_ADDR");
        }

        for ($i = 4; $i <= 20; $i++) {
            $_SERVER['REMOTE_ADDR'] = $attackIP;
            $_SERVER['HTTP_X_FORWARDED_FOR'] = "198.51.100.{$i}";

            $ip = IpBanManager::getClientIP();
            $this->assertEquals($attackIP, $ip, "Attempt {$i}: Should continue to use REMOTE_ADDR even when rate-limited");
        }
    }

    /**
     * Test that rate limiting is per-source-IP and per-header.
     * Different source IPs should have independent rate limits.
     */
    public function testRateLimitingIsPerSourceIPAndHeader(): void
    {
        $_ENV['ALLOW_PRIVATE_IPS'] = 'false';
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';
        $_ENV['AUDIT_UNTRUSTED_PROXY_MAX_LOGS'] = '2';
        $_ENV['AUDIT_UNTRUSTED_PROXY_PERIOD'] = '60';

        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';
        $ip1 = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip1);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.2';
        $ip2 = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.1', $ip2);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.3';
        $ip3 = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.2', $ip3, 'Different source IP should have independent rate limit');

        $_SERVER['REMOTE_ADDR'] = '203.0.113.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.4';
        $ip4 = IpBanManager::getClientIP();
        $this->assertEquals('203.0.113.2', $ip4, 'Different source IP should have independent rate limit');
    }
}

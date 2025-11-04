#!/usr/bin/env php
<?php
/**
 * Password Reset Feature - Code Validation Test
 * 
 * This script validates the password reset implementation without requiring
 * a database connection. It tests class loading, method signatures, and
 * basic code structure.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

echo "Password Reset Feature - Code Validation\n";
echo "=========================================\n\n";

$errors = [];
$warnings = [];

// Test 1: Check if PasswordResetController exists and is loadable
echo "✓ Testing PasswordResetController class...\n";
try {
    $reflection = new ReflectionClass('HypnoseStammtisch\Controllers\PasswordResetController');
    echo "  ✓ Class exists and is loadable\n";
    
    // Check required methods
    $requiredMethods = ['requestReset', 'verifyToken', 'resetPassword', 'cleanupExpiredTokens'];
    foreach ($requiredMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            $errors[] = "PasswordResetController missing method: $method";
        } else {
            $methodReflection = $reflection->getMethod($method);
            if (!$methodReflection->isPublic() || !$methodReflection->isStatic()) {
                $errors[] = "Method $method must be public static";
            }
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required methods exist and are public static\n";
    }
} catch (ReflectionException $e) {
    $errors[] = "Failed to load PasswordResetController: " . $e->getMessage();
}

// Test 2: Check if EmailService exists and is loadable
echo "\n✓ Testing EmailService class...\n";
try {
    $reflection = new ReflectionClass('HypnoseStammtisch\Utils\EmailService');
    echo "  ✓ Class exists and is loadable\n";
    
    // Check required methods
    $requiredMethods = ['sendPasswordResetEmail', 'sendEmailChangeConfirmation'];
    foreach ($requiredMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            $errors[] = "EmailService missing method: $method";
        } else {
            $methodReflection = $reflection->getMethod($method);
            if (!$methodReflection->isPublic() || !$methodReflection->isStatic()) {
                $errors[] = "Method $method must be public static";
            }
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required methods exist and are public static\n";
    }
} catch (ReflectionException $e) {
    $errors[] = "Failed to load EmailService: " . $e->getMessage();
}

// Test 3: Check if migration file exists
echo "\n✓ Testing database migration...\n";
$migrationFile = __DIR__ . '/../migrations/007_password_reset_tokens.sql';
if (file_exists($migrationFile)) {
    echo "  ✓ Migration file exists\n";
    
    $content = file_get_contents($migrationFile);
    
    // Check for required table structure
    if (strpos($content, 'CREATE TABLE IF NOT EXISTS password_reset_tokens') !== false) {
        echo "  ✓ Creates password_reset_tokens table\n";
    } else {
        $errors[] = "Migration does not create password_reset_tokens table";
    }
    
    // Check for required columns
    $requiredColumns = ['user_id', 'token', 'expires_at', 'used_at', 'ip_address'];
    foreach ($requiredColumns as $column) {
        if (strpos($content, $column) === false) {
            $errors[] = "Migration missing column: $column";
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required columns present\n";
    }
} else {
    $errors[] = "Migration file not found: $migrationFile";
}

// Test 4: Check API routes integration
echo "\n✓ Testing API route integration...\n";
$adminApiFile = __DIR__ . '/../api/admin.php';
if (file_exists($adminApiFile)) {
    echo "  ✓ API router file exists\n";
    
    $content = file_get_contents($adminApiFile);
    
    // Check for PasswordResetController import
    if (strpos($content, 'use HypnoseStammtisch\Controllers\PasswordResetController') !== false) {
        echo "  ✓ PasswordResetController is imported\n";
    } else {
        $errors[] = "PasswordResetController not imported in admin API router";
    }
    
    // Check for route definitions
    $requiredRoutes = [
        '/auth/password-reset/request',
        '/auth/password-reset/verify',
        '/auth/password-reset/reset'
    ];
    
    foreach ($requiredRoutes as $route) {
        if (strpos($content, $route) === false) {
            $errors[] = "Route not found in admin.php: $route";
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required routes are defined\n";
    }
} else {
    $errors[] = "API router file not found: $adminApiFile";
}

// Test 5: Check UserController refactoring
echo "\n✓ Testing UserController refactoring...\n";
$userControllerFile = __DIR__ . '/../src/Controllers/UserController.php';
if (file_exists($userControllerFile)) {
    echo "  ✓ UserController file exists\n";
    
    $content = file_get_contents($userControllerFile);
    
    // Check if EmailService is used
    if (strpos($content, 'use HypnoseStammtisch\Utils\EmailService') !== false) {
        echo "  ✓ EmailService is imported\n";
    } else {
        $warnings[] = "EmailService not imported in UserController (should be refactored)";
    }
    
    // Check if sendEmailChangeConfirmation uses EmailService
    if (strpos($content, 'EmailService::sendEmailChangeConfirmation') !== false) {
        echo "  ✓ sendEmailChangeConfirmation uses EmailService\n";
    } else {
        $warnings[] = "sendEmailChangeConfirmation may not be using EmailService";
    }
} else {
    $errors[] = "UserController file not found: $userControllerFile";
}

// Test 6: Check documentation
echo "\n✓ Testing documentation...\n";
$docsFile = __DIR__ . '/../../docs/features/password-reset.md';
if (file_exists($docsFile)) {
    echo "  ✓ Documentation file exists\n";
    
    $content = file_get_contents($docsFile);
    
    // Check for key sections
    $requiredSections = ['API Endpoints', 'Security Features', 'Database Schema'];
    foreach ($requiredSections as $section) {
        if (strpos($content, $section) === false) {
            $warnings[] = "Documentation missing section: $section";
        }
    }
    
    if (empty($warnings)) {
        echo "  ✓ All key sections present\n";
    }
} else {
    $warnings[] = "Documentation file not found: $docsFile";
}

// Test 7: Code style check (basic)
echo "\n✓ Testing code style...\n";
$files = [
    'src/Controllers/PasswordResetController.php',
    'src/Utils/EmailService.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        // Check for declare(strict_types=1)
        $content = file_get_contents($fullPath);
        if (strpos($content, 'declare(strict_types=1)') !== false) {
            echo "  ✓ $file uses strict types\n";
        } else {
            $warnings[] = "$file does not use strict types";
        }
        
        // Check for namespace
        if (strpos($content, 'namespace HypnoseStammtisch') !== false) {
            echo "  ✓ $file has correct namespace\n";
        } else {
            $errors[] = "$file missing or incorrect namespace";
        }
    }
}

// Print summary
echo "\n=========================================\n";
echo "VALIDATION SUMMARY\n";
echo "=========================================\n\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ All tests passed! Implementation looks good.\n\n";
    exit(0);
} else {
    if (!empty($errors)) {
        echo "❌ ERRORS:\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️  WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "  • $warning\n";
        }
        echo "\n";
    }
    
    if (empty($errors)) {
        echo "✅ All critical tests passed (warnings can be addressed later).\n\n";
        exit(0);
    } else {
        echo "❌ Please fix errors before proceeding.\n\n";
        exit(1);
    }
}

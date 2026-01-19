<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * HTTP Response utility class
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        // Add CORS headers
        self::addCorsHeaders();

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');

        echo $content;
        exit;
    }

    /**
     * Send plain text response
     */
    public static function text(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');

        echo $content;
        exit;
    }

    /**
     * Send file download response
     * @param string $filepath Absolute path to the file
     * @param string|null $filename Optional filename for the download
     * @param string|null $allowedBasePath Optional base path restriction (defaults to uploads folder)
     */
    public static function download(string $filepath, string $filename = null, string $allowedBasePath = null): void
    {
        // Resolve paths for security check
        $realPath = realpath($filepath);

        // Get allowed base path (default to uploads folder)
        if ($allowedBasePath === null) {
            $allowedBasePath = realpath(__DIR__ . '/../../uploads');
        } else {
            $allowedBasePath = realpath($allowedBasePath);
        }

        // Security: Prevent directory traversal attacks
        if ($realPath === false) {
            self::json(['error' => 'File not found'], 404);
            return;
        }

        // Ensure file is within allowed directory
        if ($allowedBasePath !== false && !str_starts_with($realPath, $allowedBasePath)) {
            self::json(['error' => 'Access denied'], 403);
            return;
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            self::json(['error' => 'File not found'], 404);
            return;
        }

        $filename = $filename ?: basename($realPath);

        // Sanitize filename to prevent header injection
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        $mimeType = mime_content_type($realPath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($realPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($realPath);
        exit;
    }

    /**
     * Send calendar/ICS response
     */
    public static function ics(string $content, string $filename = 'calendar.ics'): void
    {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        echo $content;
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Add CORS headers
     */
    public static function addCorsHeaders(): void
    {
        $allowedOrigins = \HypnoseStammtisch\Config\Config::get('cors.allowed_origins', []);
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        $allowedMethods = implode(', ', \HypnoseStammtisch\Config\Config::get('cors.allowed_methods', []));
        header('Access-Control-Allow-Methods: ' . $allowedMethods);

        $allowedHeaders = implode(', ', \HypnoseStammtisch\Config\Config::get('cors.allowed_headers', []));
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 hours
    }

    /**
     * Handle preflight OPTIONS request
     */
    public static function handlePreflight(): void
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::addCorsHeaders();
            http_response_code(204);
            exit;
        }
    }

    /**
     * Send error response with standard format
     */
    public static function error(string $message, int $statusCode = 400, array $details = []): void
    {
        $response = [
            'success' => false,
            'error' => $message,
            'status_code' => $statusCode
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send success response with standard format
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): void {
        $response = [
            'success' => true,
            'message' => $message,
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(array $data = []): void
    {
        $response = array_merge([
            'success' => false,
            'error' => 'Unauthorized',
            'status_code' => 401
        ], $data);

        self::json($response, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(array $data = []): void
    {
        $response = array_merge([
            'success' => false,
            'error' => 'Forbidden',
            'status_code' => 403
        ], $data);

        self::json($response, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound(array $data = []): void
    {
        $response = array_merge([
            'success' => false,
            'error' => 'Not Found',
            'status_code' => 404
        ], $data);

        self::json($response, 404);
    }
}

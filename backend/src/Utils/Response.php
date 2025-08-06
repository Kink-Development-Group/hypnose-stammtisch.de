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
     */
    public static function download(string $filepath, string $filename = null): void
    {
        if (!file_exists($filepath)) {
            self::json(['error' => 'File not found'], 404);
            return;
        }

        $filename = $filename ?: basename($filepath);
        $mimeType = mime_content_type($filepath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($filepath);
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
    public static function success(mixed $data = null, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response);
    }
}

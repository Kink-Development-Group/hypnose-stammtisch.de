<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use Exception;

/**
 * Admin messages management controller
 */
class AdminMessagesController
{
  /**
   * Get all contact form submissions
   */
  public static function index(): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $page = max(1, (int)($_GET['page'] ?? 1));
      $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
      $offset = ($page - 1) * $limit;

      $status = $_GET['status'] ?? null;
      $subject = $_GET['subject'] ?? null;

      // Build WHERE clause
      $whereClauses = [];
      $params = [];

      if ($status) {
        $whereClauses[] = "status = ?";
        $params[] = $status;
      }

      if ($subject) {
        $whereClauses[] = "subject = ?";
        $params[] = $subject;
      }

      $whereClause = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

      // Get total count
      $countSql = "SELECT COUNT(*) as total FROM contact_submissions {$whereClause}";
      $totalResult = Database::fetchOne($countSql, $params);
      $total = $totalResult['total'];

      // Get messages
      $sql = "SELECT id, name, email, subject, message, status, response_sent,
                           submitted_at, processed_at, assigned_to
                    FROM contact_submissions
                    {$whereClause}
                    ORDER BY submitted_at DESC
                    LIMIT {$limit} OFFSET {$offset}";

      $messages = Database::fetchAll($sql, $params);

      // Format timestamps for display
      foreach ($messages as &$message) {
        $message['submitted_at_formatted'] = date('d.m.Y H:i', strtotime($message['submitted_at']));
        $message['processed_at_formatted'] = $message['processed_at']
          ? date('d.m.Y H:i', strtotime($message['processed_at']))
          : null;
      }

      Response::success([
        'messages' => $messages,
        'pagination' => [
          'page' => $page,
          'limit' => $limit,
          'total' => $total,
          'pages' => ceil($total / $limit)
        ]
      ]);
    } catch (Exception $e) {
      Response::error('Failed to fetch messages: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get single message by ID
   */
  public static function show(int $id): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "SELECT * FROM contact_submissions WHERE id = ?";
      $message = Database::fetchOne($sql, [$id]);

      if (!$message) {
        Response::notFound(['message' => 'Message not found']);
        return;
      }

      // Format timestamps
      $message['submitted_at_formatted'] = date('d.m.Y H:i', strtotime($message['submitted_at']));
      $message['processed_at_formatted'] = $message['processed_at']
        ? date('d.m.Y H:i', strtotime($message['processed_at']))
        : null;

      Response::success($message);
    } catch (Exception $e) {
      Response::error('Failed to fetch message: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Update message status
   */
  public static function updateStatus(int $id): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!isset($input['status'])) {
      Response::error('Status is required', 400);
      return;
    }

    $validStatuses = ['new', 'in_progress', 'resolved', 'spam'];
    if (!in_array($input['status'], $validStatuses)) {
      Response::error('Invalid status', 400);
      return;
    }

    try {
      $currentUser = AdminAuth::getCurrentUser();

      $sql = "UPDATE contact_submissions
                    SET status = ?, assigned_to = ?, processed_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

      $affected = Database::execute($sql, [
        $input['status'],
        $currentUser['username'],
        $id
      ])->rowCount();

      if ($affected === 0) {
        Response::notFound(['message' => 'Message not found']);
        return;
      }

      Response::success(null, 'Message status updated successfully');
    } catch (Exception $e) {
      Response::error('Failed to update message status: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Mark message as responded
   */
  public static function markResponded(int $id): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "UPDATE contact_submissions
                    SET response_sent = 1, processed_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

      $affected = Database::execute($sql, [$id])->rowCount();

      if ($affected === 0) {
        Response::notFound(['message' => 'Message not found']);
        return;
      }

      Response::success(null, 'Message marked as responded');
    } catch (Exception $e) {
      Response::error('Failed to mark message as responded: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Delete message
   */
  public static function delete(int $id): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "DELETE FROM contact_submissions WHERE id = ?";
      $affected = Database::execute($sql, [$id])->rowCount();

      if ($affected === 0) {
        Response::notFound(['message' => 'Message not found']);
        return;
      }

      Response::success(null, 'Message deleted successfully');
    } catch (Exception $e) {
      Response::error('Failed to delete message: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get dashboard statistics
   */
  public static function stats(): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      // Get message counts by status
      $statusSql = "SELECT status, COUNT(*) as count
                         FROM contact_submissions
                         GROUP BY status";
      $statusCounts = Database::fetchAll($statusSql);

      // Get message counts by subject
      $subjectSql = "SELECT subject, COUNT(*) as count
                          FROM contact_submissions
                          GROUP BY subject
                          ORDER BY count DESC";
      $subjectCounts = Database::fetchAll($subjectSql);

      // Get recent messages (last 7 days)
      $recentSql = "SELECT COUNT(*) as count
                         FROM contact_submissions
                         WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
      $recentResult = Database::fetchOne($recentSql);

      // Get unread messages count
      $unreadSql = "SELECT COUNT(*) as count
                         FROM contact_submissions
                         WHERE status = 'new'";
      $unreadResult = Database::fetchOne($unreadSql);

      Response::success([
        'status_counts' => $statusCounts,
        'subject_counts' => $subjectCounts,
        'recent_count' => $recentResult['count'],
        'unread_count' => $unreadResult['count']
      ]);
    } catch (Exception $e) {
      Response::error('Failed to fetch statistics: ' . $e->getMessage(), 500);
    }
  }
}

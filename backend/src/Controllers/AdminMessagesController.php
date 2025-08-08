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
  public static function show(string $id): void
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
  public static function updateStatus(string $id): void
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
  public static function markResponded(string $id): void
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
  public static function delete(string $id): void
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

  /**
   * Get notes for a message
   */
  public static function getNotes(string $messageId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "SELECT n.*,
                     CASE
                       WHEN n.updated_at > n.created_at THEN n.updated_at
                       ELSE n.created_at
                     END as last_modified
              FROM message_notes n
              WHERE n.message_id = ?
              ORDER BY n.created_at ASC";

      $notes = Database::fetchAll($sql, [$messageId]);

      // Format timestamps
      foreach ($notes as &$note) {
        $note['created_at_formatted'] = date('d.m.Y H:i', strtotime($note['created_at']));
        $note['updated_at_formatted'] = $note['updated_at']
          ? date('d.m.Y H:i', strtotime($note['updated_at']))
          : null;
      }

      Response::success($notes);
    } catch (Exception $e) {
      Response::error('Failed to fetch notes: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Add note to message
   */
  public static function addNote(string $messageId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!isset($input['note']) || trim($input['note']) === '') {
      Response::error('Note text is required', 400);
      return;
    }

    $noteType = $input['note_type'] ?? 'general';
    $allowedTypes = ['processing', 'communication', 'general'];

    if (!in_array($noteType, $allowedTypes)) {
      Response::error('Invalid note type', 400);
      return;
    }

    try {
      $currentUser = AdminAuth::getCurrentUser();

      $sql = "INSERT INTO message_notes (message_id, admin_user, note, note_type)
              VALUES (?, ?, ?, ?)";

      $noteId = Database::insert($sql, [
        $messageId,
        $currentUser['username'],
        trim($input['note']),
        $noteType
      ]);

      if (!$noteId) {
        Response::error('Failed to add note', 500);
        return;
      }

      // Get the created note
      $createdNote = Database::fetchOne(
        "SELECT * FROM message_notes WHERE id = ?",
        [$noteId]
      );

      $createdNote['created_at_formatted'] = date('d.m.Y H:i', strtotime($createdNote['created_at']));

      Response::success($createdNote, 'Note added successfully');
    } catch (Exception $e) {
      Response::error('Failed to add note: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Update note
   */
  public static function updateNote(string $messageId, string $noteId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!isset($input['note']) || trim($input['note']) === '') {
      Response::error('Note text is required', 400);
      return;
    }

    try {
      $currentUser = AdminAuth::getCurrentUser();

      // Verify note belongs to message and user has permission to edit
      $existingNote = Database::fetchOne(
        "SELECT * FROM message_notes WHERE id = ? AND message_id = ?",
        [$noteId, $messageId]
      );

      if (!$existingNote) {
        Response::notFound(['message' => 'Note not found']);
        return;
      }

      // Only allow editing own notes or if admin
      if ($existingNote['admin_user'] !== $currentUser['username'] && $currentUser['role'] !== 'admin') {
        Response::error('Permission denied', 403);
        return;
      }

      $sql = "UPDATE message_notes
              SET note = ?, updated_at = CURRENT_TIMESTAMP
              WHERE id = ? AND message_id = ?";

      $affected = Database::execute($sql, [
        trim($input['note']),
        $noteId,
        $messageId
      ])->rowCount();

      if ($affected === 0) {
        Response::error('Failed to update note', 500);
        return;
      }

      Response::success(null, 'Note updated successfully');
    } catch (Exception $e) {
      Response::error('Failed to update note: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Delete note
   */
  public static function deleteNote(string $messageId, string $noteId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $currentUser = AdminAuth::getCurrentUser();

      // Verify note belongs to message and user has permission to delete
      $existingNote = Database::fetchOne(
        "SELECT * FROM message_notes WHERE id = ? AND message_id = ?",
        [$noteId, $messageId]
      );

      if (!$existingNote) {
        Response::notFound(['message' => 'Note not found']);
        return;
      }

      // Only allow deleting own notes or if admin
      if ($existingNote['admin_user'] !== $currentUser['username'] && $currentUser['role'] !== 'admin') {
        Response::error('Permission denied', 403);
        return;
      }

      $sql = "DELETE FROM message_notes WHERE id = ? AND message_id = ?";
      $affected = Database::execute($sql, [$noteId, $messageId])->rowCount();

      if ($affected === 0) {
        Response::error('Failed to delete note', 500);
        return;
      }

      Response::success(null, 'Note deleted successfully');
    } catch (Exception $e) {
      Response::error('Failed to delete note: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get available email addresses for responses
   */
  public static function getEmailAddresses(): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "SELECT id, email, display_name, department, is_default
              FROM admin_email_addresses
              WHERE is_active = 1
              ORDER BY is_default DESC, department ASC, display_name ASC";

      $addresses = Database::fetchAll($sql);

      Response::success($addresses);
    } catch (Exception $e) {
      Response::error('Failed to fetch email addresses: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Send email response
   */
  public static function sendResponse(string $messageId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Validation
    $required = ['from_email_id', 'subject', 'body'];
    foreach ($required as $field) {
      if (!isset($input[$field]) || trim($input[$field]) === '') {
        Response::error("Field '$field' is required", 400);
        return;
      }
    }

    try {
      $currentUser = AdminAuth::getCurrentUser();

      // Get the original message
      $message = Database::fetchOne(
        "SELECT * FROM contact_submissions WHERE id = ?",
        [$messageId]
      );

      if (!$message) {
        Response::notFound(['message' => 'Message not found']);
        return;
      }

      // Get the from email address
      $fromEmail = Database::fetchOne(
        "SELECT * FROM admin_email_addresses WHERE id = ? AND is_active = 1",
        [$input['from_email_id']]
      );

      if (!$fromEmail) {
        Response::error('Invalid from email address', 400);
        return;
      }

      // Store the response in database
      $responseId = Database::insert(
        "INSERT INTO message_responses
         (message_id, admin_user, from_email, to_email, subject, body, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
          $messageId,
          $currentUser['username'],
          $fromEmail['email'],
          $message['email'],
          trim($input['subject']),
          trim($input['body']),
          'draft' // Will be updated to 'sent' after successful email
        ]
      );

      // Here you would integrate with your email service (PHPMailer, etc.)
      // For now, we'll mark it as sent

      Database::execute(
        "UPDATE message_responses SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$responseId]
      );

      // Update the original message as responded
      Database::execute(
        "UPDATE contact_submissions SET response_sent = 1, processed_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$messageId]
      );

      Response::success([
        'response_id' => $responseId,
        'status' => 'sent'
      ], 'Response sent successfully');
    } catch (Exception $e) {
      Response::error('Failed to send response: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get response history for a message
   */
  public static function getResponses(string $messageId): void
  {
    AdminAuth::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    try {
      $sql = "SELECT r.*, a.display_name as from_display_name
              FROM message_responses r
              LEFT JOIN admin_email_addresses a ON r.from_email = a.email
              WHERE r.message_id = ?
              ORDER BY r.sent_at DESC";

      $responses = Database::fetchAll($sql, [$messageId]);

      // Format timestamps
      foreach ($responses as &$response) {
        $response['sent_at_formatted'] = $response['sent_at']
          ? date('d.m.Y H:i', strtotime($response['sent_at']))
          : null;
      }

      Response::success($responses);
    } catch (Exception $e) {
      Response::error('Failed to fetch responses: ' . $e->getMessage(), 500);
    }
  }
}

<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;

/**
 * Admin authentication controller
 */
class AdminAuthController
{
  /**
   * Handle login request
   */
  public static function login(): void
  {
    AdminAuth::startSession();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new Validator($input);
    $validator->required(['email', 'password']);
    $validator->email('email');

    if (!$validator->isValid()) {
      Response::error('Validation failed', 400, $validator->getErrors());
      return;
    }

    $result = AdminAuth::authenticate($input['email'], $input['password']);

    if ($result['success']) {
      Response::success($result['user'], 'Login successful');
    } else {
      Response::error($result['message'], 401);
    }
  }

  /**
   * Handle logout request
   */
  public static function logout(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    AdminAuth::logout();
    Response::success(null, 'Logout successful');
  }

  /**
   * Get current authentication status
   */
  public static function status(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    $user = AdminAuth::getCurrentUser();

    if ($user) {
      Response::success($user, 'Authenticated');
    } else {
      Response::unauthorized(['message' => 'Not authenticated']);
    }
  }

  /**
   * Get CSRF token
   */
  public static function csrf(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    $token = AdminAuth::generateCSRF();
    Response::success(['csrf_token' => $token]);
  }
}

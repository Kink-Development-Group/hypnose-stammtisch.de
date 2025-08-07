<?php
require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Controllers\AdminEventsController;
use HypnoseStammtisch\Middleware\AdminAuth;

// Load configuration
Config::load(__DIR__);

// Simulate login
AdminAuth::startSession();
$_SESSION['admin_user_id'] = 1;
$_SESSION['admin_user_email'] = 'admin@example.com';
$_SESSION['admin_user_role'] = 'admin';

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create test data
$testData = [
  'title' => 'Test Event',
  'start_datetime' => '2025-01-10T10:00:00',
  'end_datetime' => '2025-01-10T12:00:00',
  'category' => 'stammtisch',
  'description' => 'Test Event Description'
];

// Mock php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpInputStream");

class MockPhpInputStream
{
  protected $position;
  protected $data;

  public function __construct()
  {
    global $testData;
    $this->data = json_encode($testData);
    $this->position = 0;
  }

  public function stream_read($count)
  {
    $ret = substr($this->data, $this->position, $count);
    $this->position += strlen($ret);
    return $ret;
  }

  public function stream_eof()
  {
    return $this->position >= strlen($this->data);
  }

  public function stream_stat()
  {
    return array();
  }

  public function url_stat()
  {
    return array();
  }

  public function stream_open($path, $mode, $options, &$opened_path)
  {
    return true;
  }
}

try {
  echo "Testing event creation...\n";
  AdminEventsController::create();
  echo "Event creation completed successfully\n";
} catch (Throwable $e) {
  echo "Error: " . $e->getMessage() . "\n";
  echo "File: " . $e->getFile() . "\n";
  echo "Line: " . $e->getLine() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

<?php
require_once 'vendor/autoload.php';

echo "Autoload works\n";

try {
    $controller = new HypnoseStammtisch\Controllers\EventsController();
    echo "EventsController found\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $config = new HypnoseStammtisch\Config\Config();
    echo "Config class found\n";
} catch (Error $e) {
    echo "Config Error: " . $e->getMessage() . "\n";
}

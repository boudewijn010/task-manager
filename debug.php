<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$config = $app->make('config');
echo "config(logging.default): " . var_export($config->get('logging.default'), true) . PHP_EOL;
echo "config(logging.channels): " . (is_array($config->get('logging.channels')) ? 'array' : gettype($config->get('logging.channels'))) . PHP_EOL;
echo "env(LOG_CHANNEL): " . var_export(getenv('LOG_CHANNEL'), true) . PHP_EOL;
echo "env(APP_DEBUG): " . var_export(getenv('APP_DEBUG'), true) . PHP_EOL;
echo "config(database.connections.sqlite.database): " . var_export($config->get('database.connections.sqlite.database'), true) . PHP_EOL;

// try to get logger instance
try {
    $logger = $app->make('log');
    echo "logger resolved\n";
    echo "logger channel: " . get_class($logger) . PHP_EOL;
} catch (Throwable $e) {
    echo "logger error: " . $e->getMessage() . PHP_EOL;
}

// exit
return 0;

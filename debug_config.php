<?php
$config = require __DIR__ . '/bootstrap/cache/config.php';
echo "logging.default = " . ($config['logging']['default'] ?? 'NULL') . PHP_EOL;
echo "logging.single.path = " . ($config['logging']['channels']['single']['path'] ?? 'NULL') . PHP_EOL;
echo "database.sqlite = " . ($config['database']['connections']['sqlite']['database'] ?? 'NULL') . PHP_EOL;

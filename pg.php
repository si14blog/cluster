<?php
chdir(__DIR__);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

ini_set('memory_limit', '2048M'); // 1024 megabytes
echo 'Memory limit: ' . ini_get('memory_limit') . "\n";

date_default_timezone_set('Asia/Tehran'); // GMT+3:30

require_once 'libs/cache.php';
require_once 'libs/cluster.php';


//cache();
// Free all memory held by `cache()`
$collected = gc_collect_cycles();
echo "Garbage collected: $collected cycles\n";
groupSimilarNews();

<?php
// Test file to debug CSV export
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$service = app(\App\Services\CorrectionExportService::class);
$csv = $service->generateCsv();

echo 'CSV Length: ' . strlen($csv) . PHP_EOL;
echo 'First 300 chars: ' . PHP_EOL;
echo $csv . PHP_EOL;
echo '---END---' . PHP_EOL;

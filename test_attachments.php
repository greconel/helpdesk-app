<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$graph = app(\App\Services\GraphMailService::class);
$msgs = $graph->getLatestMessages(1);
if (empty($msgs)) {
    echo "No messages.\n";
    exit;
}
$id = $msgs[0]['id'];
$raw = $graph->getMessage($id);
$atts = $graph->getAttachments($id);

// Remove huge binary data for output readability
foreach ($atts as &$a) {
    if (isset($a['contentBytes'])) {
        $a['contentBytes'] = '<' . strlen($a['contentBytes']) . ' bytes>';
    }
}

echo "Message hasAttachments (from GET /message): " . ($raw['hasAttachments'] ?? 'MISSING') . "\n";
echo "Attachments count: " . count($atts) . "\n";
print_r($atts);

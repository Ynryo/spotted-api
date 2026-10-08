<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

echo "Fetching live markers...\n";
$response = $client->request('GET', 'https://bus-tracker.fr/api/vehicle-journeys/markers', [
    'query' => [
        'swLat' => 41.0,
        'swLon' => -5.5,
        'neLat' => 51.5,
        'neLon' => 9.5,
    ],
    'headers' => ['Accept' => 'application/json'],
    'timeout' => 15,
]);

$content = $response->getContent();
if (str_starts_with($content, "\x1f\x8b")) {
    $content = gzdecode($content) ?: $content;
}
$data = json_decode($content, true);
$items = $data['items'] ?? [];
echo "Total live vehicles found: " . count($items) . "\n";

// Batch query vehicle-journeys/{id}/paths
$batchSize = 25;
$found = [];

for ($i = 0; $i < count($items); $i += $batchSize) {
    $batch = array_slice($items, $i, $batchSize);
    $responses = [];
    foreach ($batch as $item) {
        $id = $item['id'];
        if (str_contains($id, 'ASTUCE')) continue;
        $responses[$id] = $client->request('GET', "https://bus-tracker.fr/api/vehicle-journeys/{$id}/paths", [
            'timeout' => 5,
        ]);
    }

    foreach ($responses as $id => $res) {
        try {
            if ($res->getStatusCode() !== 200) continue;
            $json = $res->toArray(false);
            if (isset($json['cancelled']) && !empty($json['cancelled']['segments'])) {
                echo "\nFOUND CANCELLED SEGMENTS on vehicle: $id\n";
                echo "URL: https://bus-tracker.fr/api/vehicle-journeys/{$id}/paths\n";
                echo "Cancelled: " . json_encode($json['cancelled']) . "\n";
                $found[] = $id;
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
    echo ".";
    flush();
    if ($i >= 50000) {
        echo "\nChecked 5000 vehicles without finding cancelled segment.\n";
        break;
    }
}

if (empty($found)) {
    echo "\nNone found in first sample.\n";
}

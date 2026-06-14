<?php
require_once __DIR__ . '/Global/Config.php';
require_once __DIR__ . '/Utils/InitTools.php';

require_once __DIR__ . '/vendor/autoload.php';

use PHPVector\VectorDatabase;

$query = (string) $argv[1];
$k = (int) $argv[2];

$embedding = getEmbedding($query, EMBEDDING_MODEL_URL);

$db = VectorDatabase::open(__DIR__ . '/data/knowledge_vault');

$results = $db->vectorSearch(
    vector: $embedding,
    k: $k
);

foreach ($results as $result) {
    $rank = $result->rank;
    $score = $result->score;
    $document = $result->document;

    echo '----------------------' . PHP_EOL;
    echo "Rank: $rank" . PHP_EOL;
    echo "Score: $score" . PHP_EOL;
    echo "Document: $document->text" . PHP_EOL;
    echo '----------------------' . PHP_EOL;
}
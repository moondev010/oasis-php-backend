<?php

require_once __DIR__ . '/Global/Config.php';
require_once __DIR__ . '/Utils/InitTools.php';

//

require_once __DIR__ . '/vendor/autoload.php';

//

if (!is_dir(__DIR__ . '/' . KNOWLEDGE_VAULT_DIR)) {
    echo 'Not valid knowledge vault directory, please create one.' . PHP_EOL;
    exit;
}

$document_paths = getDocumentPaths(KNOWLEDGE_VAULT_DIR);

if (sizeof($document_paths) === 0) {
    echo 'No markdown files found.' . PHP_EOL;
    exit;
}

echo 'Getting document chunks.' . PHP_EOL;
$documents = getDocumentChunks($document_paths);

$chunks = $documents['chunks'];
$metadatas = $documents['metadatas'];

echo 'Generating embeddings for each chunk.' . PHP_EOL;
$embeddings = getEmbeddings($chunks, EMBEDDING_MODEL_URL);

if (sizeof($embeddings) === 0) {
    echo 'Something went wrong while generating embeddings.' . PHP_EOL;
    exit;
}

echo 'Filling vector documents.' . PHP_EOL;
$vectorDocuments = getVectorDocuments($chunks, $metadatas, $embeddings, sizeof($chunks));

$vectorDbAbsoluteDir = __DIR__ . '/' . VECTOR_DB_DIR;

if (!is_dir($vectorDbAbsoluteDir)) {
    echo "No vector database directory. Creating one at $vectorDbAbsoluteDir" . PHP_EOL;
    // mkdir($vectorDbAbsoluteDir, 0777, true);
}

echo 'Creating the vector database.' . PHP_EOL;
$db = createDatabase(__DIR__ . '/' . VECTOR_DB_DIR);

echo 'Saving documents.' . PHP_EOL;
saveDocuments($db, $vectorDocuments);

$db->save();

echo 'Done.' . PHP_EOL;

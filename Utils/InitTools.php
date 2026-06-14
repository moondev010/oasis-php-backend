<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ecourty\TextChunker\TextChunker;
use Ecourty\TextChunker\Strategy\MarkdownChunkingStrategy;
use Ecourty\TextChunker\Strategy\FixedSizeChunkingStrategy;
use Ecourty\TextChunker\Strategy\RecursiveChunkingStrategy;
use PHPVector\Document;
use PHPVector\VectorDatabase;


function getDocumentPaths(string $dir): array
{
    $paths = [];

    $directory = new RecursiveDirectoryIterator(
        $dir,
        RecursiveDirectoryIterator::SKIP_DOTS
    );

    $iterator = new RecursiveIteratorIterator($directory);

    foreach ($iterator as $path) {
        if ($path->getExtension() === 'md') {
            $basename = $path->getBasename('.md');
            $absolutePath = $path->getRealPath();

            $paths[] = [
                'basename' => $basename,
                'absolutePath' => $absolutePath
            ];
        }
    }

    return $paths;
}

function getDocumentChunks(array $document_paths): array
{
    $chunks = [];
    $metadatas = [];

    foreach ($document_paths as $document_path_meta) {

        $content = file_get_contents($document_path_meta['absolutePath']);

        $chunker = (new TextChunker())
            ->setText($content);

        $strategy = new RecursiveChunkingStrategy(
            strategies: [
                new MarkdownChunkingStrategy(
                    minHeadingLevel: 1,
                    maxHeadingLevel: 1
                ),
                new FixedSizeChunkingStrategy(chunkSize: 1000)
            ],
            maxChunkSize: 1500
        );

        foreach ($chunker->chunk($strategy) as $index => $chunk) {
            $text = $chunk->getText();

            $chunks[] = $text;
            $metadatas[] = $document_path_meta;
        }
    }

    return [
        'chunks' => $chunks,
        'metadatas' => $metadatas
    ];
}

function getEmbeddings(array $chunks, string $model_url): array
{
    $ch = curl_init($model_url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "embeddinggemma",
        "input" => $chunks
    ]));

    $response = json_decode(curl_exec($ch), true);

    unset($ch);

    return $response['embeddings'] ?? [];
}

function getEmbedding(string $query, string $model_url): array
{
    $ch = curl_init($model_url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "embeddinggemma",
        "input" => $query
    ]));

    $response = json_decode(curl_exec($ch), true);

    unset($ch);

    return $response['embeddings'][0] ?? [];
}

function getVectorDocuments(array $chunks, array $metadatas, array $embeddings, int $size): array
{
    $vectorDocuments = [];

    $i = 0;

    while ($i < $size) {
        $embedding = $embeddings[$i];
        $chunk = $chunks[$i];
        $metadata = $metadatas[$i];

        $vectorDocuments[] = new Document(
            id: $i,
            vector: $embedding,
            text: $chunk,
            metadata: $metadata
        );

        $i++;
    }

    return $vectorDocuments;
}

function createDatabase(string $databaseAbsoluteDir): VectorDatabase
{
    $db = new VectorDatabase(path: $databaseAbsoluteDir);

    return $db;
}

function saveDocuments(VectorDatabase $db, array $documents)
{
    $db->addDocuments($documents);
}
<?php

$env = parse_ini_file(__DIR__ . '/../.env');

define('KNOWLEDGE_VAULT_DIR', $env['KNOWLEDGE_VAULT_DIR'] ?? 'knowledge_vault');
define('EMBEDDING_MODEL_URL', $env['EMBEDDING_MODEL_URL'] ?? 'http://localhost:11434/api/embed');
define('VECTOR_DB_DIR', $env['VECTOR_DB_DIR'] ?? 'data/knowledge_vault');

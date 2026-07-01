<?php

$payload = file_get_contents('php://input') ?: '';
$line = sprintf(
    "[%s] %s %s\n%s\n\n",
    date('c'),
    $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    $_SERVER['REQUEST_URI'] ?? '/',
    $payload
);

file_put_contents('/tmp/contrato-online-webhook.log', $line, FILE_APPEND);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);

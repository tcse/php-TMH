<?php
// sw-config.php — конфигурация для Service Worker
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$config = require_once __DIR__ . '/../data/config.php';

echo json_encode([
    'base_url' => $config['base_url'],
    'webapp_url' => $config['webapp_url'],
    'player_url' => $config['player_url']
], JSON_UNESCAPED_UNICODE);
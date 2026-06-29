<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Coin.php';
require_once __DIR__ . '/../../repositories/CoinRepository.php';
require_once __DIR__ . '/../../services/CoinService.php';
require_once __DIR__ . '/../../services/ApiService.php';

try {
    $coinService = new CoinService();
    $apiService = new ApiService();
    $coins = $coinService->getAllCoins();
    $prices = $apiService->getPricesForCoins($coins, 'eur');

    $payload = [
        'success' => true,
        'currency' => 'EUR',
        'updatedAt' => gmdate('c'),
        'prices' => $prices,
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Live koers kon niet worden geladen.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
<?php

require_once __DIR__ . '/../models/Coin.php';

// Haalt live koersdata op uit CoinGecko en zet die klaar voor de rest van de app.
class ApiService
{
    private string $baseUrl = 'https://api.coingecko.com/api/v3/simple/price';

    // Koppel live prijzen aan de coins uit de database.
    public function getPricesForCoins(array $coins, string $currency = 'eur'): array
    {
        // CoinGecko zoekt op symbolen, dus we halen eerst alleen de symbols uit de objects.
        $symbols = [];

        foreach ($coins as $coin) {
            if ($coin instanceof Coin) {
                $symbols[] = $coin->getSymbol();
            }
        }

        $pricesBySymbol = $this->getPricesBySymbols($symbols, $currency);
        $result = [];

        foreach ($coins as $coin) {
            if (!$coin instanceof Coin) {
                continue;
            }

            $symbol = strtolower($coin->getSymbol());
            $result[$coin->getId()] = $pricesBySymbol[$symbol] ?? [
                'price' => null,
                'change24h' => null,
                'updatedAt' => null,
                'currency' => strtoupper($currency),
            ];
        }

        return $result;
    }

    // Vraag CoinGecko in één keer aan voor alle symbolen.
    public function getPricesBySymbols(array $symbols, string $currency = 'eur'): array
    {
        // Maak de lijst schoon, zodat lege of rare waarden niet mee naar buiten gaan.
        $normalizedSymbols = [];

        foreach ($symbols as $symbol) {
            $symbol = strtolower(trim((string) $symbol));

            if ($symbol !== '' && preg_match('/^[a-z0-9]+$/', $symbol)) {
                $normalizedSymbols[$symbol] = true;
            }
        }

        if (empty($normalizedSymbols)) {
            // Geen geldige symbolen betekent: geen API-call nodig.
            return [];
        }

        // CoinGecko geeft per symbol een prijs, 24h wijziging en update-tijd terug.
        $query = http_build_query([
            'symbols' => implode(',', array_keys($normalizedSymbols)),
            'vs_currencies' => strtolower($currency),
            'include_24hr_change' => 'true',
            'include_last_updated_at' => 'true',
            'include_tokens' => 'top',
        ]);

        $response = $this->requestJson($this->baseUrl . '?' . $query);
        if ($response === null) {
            return [];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        $currencyKey = strtolower($currency);

        foreach ($decoded as $symbol => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $symbol = strtolower((string) $symbol);
            $result[$symbol] = [
                'price' => isset($payload[$currencyKey]) ? (float) $payload[$currencyKey] : null,
                'change24h' => isset($payload[$currencyKey . '_24h_change']) ? (float) $payload[$currencyKey . '_24h_change'] : null,
                'updatedAt' => isset($payload['last_updated_at']) ? (int) $payload['last_updated_at'] : null,
                'currency' => strtoupper($currency),
            ];
        }

        return $result;
    }

    private function requestJson(string $url): ?string
    {
        // Gebruik eerst cURL als die beschikbaar is.
        if (function_exists('curl_init')) {
            $handle = curl_init($url);

            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: CryptoApp/1.0',
                ],
            ]);

            $response = curl_exec($handle);
            $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            if ($response !== false && $statusCode >= 200 && $statusCode < 300) {
                return $response;
            }
        }

        // Als cURL er niet is, proberen we dezelfde request via file_get_contents.
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => "Accept: application/json\r\nUser-Agent: CryptoApp/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        return $response;
    }
}
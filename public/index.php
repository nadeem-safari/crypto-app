<?php

// Laad de klassen die deze pagina nodig heeft.
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Coin.php';
require_once __DIR__ . '/../repositories/CoinRepository.php';
require_once __DIR__ . '/../services/CoinService.php';
require_once __DIR__ . '/../services/ApiService.php';

$apiService = new ApiService();
$service = null;
$bootstrapError = null;

try {
    $service = new CoinService();
} catch (Throwable $exception) {
    $bootstrapError = $exception->getMessage();
}

// Hier bewaren we de melding die na een actie op het scherm komt.
$feedback = null;  // ['type' => 'success'|'error', 'message' => '...']

// Bepaal eerst welke actie de gebruiker wil uitvoeren.
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($service !== null) {
    // Verwerk alleen acties als de database-laag goed is opgestart.
    switch ($action) {
        case 'create':
            $result   = $service->createCoin(
                $_POST['name']        ?? '',
                $_POST['symbol']      ?? '',
                $_POST['description'] ?? ''
            );
            $feedback = ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']];
            break;

        case 'update':
            $result   = $service->updateCoin(
                (int) ($_POST['id'] ?? 0),
                $_POST['name']        ?? '',
                $_POST['symbol']      ?? '',
                $_POST['description'] ?? ''
            );
            $feedback = ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']];
            break;

        case 'delete':
            $result   = $service->deleteCoin($id);
            $feedback = ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']];
            break;
    }
}

// Haal de coins op voor de tabel en de edit-pagina.
$searchQuery = $_GET['search'] ?? '';
$coins       = [];
$editCoin    = null;
$livePrices  = [];

if ($service !== null) {
    // Eerst de coins uit de database, daarna de live prijs erbij zetten.
    $coins      = $searchQuery ? $service->searchCoins($searchQuery) : $service->getAllCoins();
    $editCoin   = ($action === 'edit' && $id) ? $service->getCoinById($id) : null;
    $livePrices = $apiService->getPricesForCoins($coins, 'eur');
} else {
    $feedback = ['type' => 'error', 'message' => 'Databaseverbinding niet beschikbaar. Start MySQL/MariaDB in XAMPP en controleer of database "cryptoapp" bestaat.'];
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header>
    <div class="logo">crypto<span>APP</span></div>
</header>

<div class="container">

    <?php if ($feedback): ?>
        <div class="alert alert-<?= $feedback['type'] ?>">
            <?= htmlspecialchars($feedback['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($bootstrapError): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($bootstrapError) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div class="stat-label">Totaal coins</div>
            <div class="stat-value"><?= count($coins) ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Database</div>
            <div class="stat-value" style="font-size:1rem; padding-top:0.4rem; color:<?= $bootstrapError ? 'var(--red)' : 'var(--green)' ?>;">● <?= $bootstrapError ? 'Offline' : 'Online' ?></div>
        </div>
    </div>

    <div class="grid">

        <!-- ==================== FORMULIER ==================== -->
        <div>
            <div class="panel">
                <?php if ($editCoin): ?>
                    <!-- EDIT formulier -->
                    <div class="panel-title">Coin aanpassen</div>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id"     value="<?= $editCoin->getId() ?>">
                        <div class="form-group">
                            <label for="name">Naam</label>
                            <input type="text" id="name" name="name" required
                                   value="<?= htmlspecialchars($editCoin->getName()) ?>">
                        </div>
                        <div class="form-group">
                            <label for="symbol">Symbool</label>
                            <input type="text" id="symbol" name="symbol" required maxlength="10"
                                   placeholder="bijv. BTC"
                                   value="<?= htmlspecialchars($editCoin->getSymbol()) ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Beschrijving</label>
                            <textarea id="description" name="description"><?= htmlspecialchars($editCoin->getDescription()) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">💾 Opslaan</button>
                        <a href="index.php" class="btn btn-cancel">Annuleren</a>
                    </form>

                <?php else: ?>
                    <!-- NIEUW formulier -->
                    <div class="panel-title">Coin toevoegen</div>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label for="name">Naam</label>
                            <input type="text" id="name" name="name" required placeholder="bijv. Bitcoin">
                        </div>
                        <div class="form-group">
                            <label for="symbol">Symbool</label>
                            <input type="text" id="symbol" name="symbol" required maxlength="10" placeholder="bijv. BTC">
                        </div>
                        <div class="form-group">
                            <label for="description">Beschrijving</label>
                            <textarea id="description" name="description" placeholder="Korte beschrijving..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">＋ Toevoegen</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== TABEL ==================== -->
        <div>
            <div class="panel">
                <div class="panel-title">Alle coins</div>

                <!-- Zoekbalk -->
                <form method="GET" action="index.php" class="search-bar">
                    <input type="text" name="search" placeholder="Zoek op naam of symbool..."
                           value="<?= htmlspecialchars($searchQuery) ?>">
                    <button type="submit" class="btn btn-edit">Zoeken</button>
                    <?php if ($searchQuery): ?>
                        <a href="index.php" class="btn btn-edit">✕</a>
                    <?php endif; ?>
                </form>

                <div class="table-wrap">
                    <?php if (empty($coins)): ?>
                        <div class="empty-state">
                            <div class="icon">₿</div>
                            <p>Geen coins gevonden.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Naam</th>
                                    <th>Symbool</th>
                                    <th>Live koers</th>
                                    <th>Beschrijving</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coins as $coin): ?>
                                    <?php // Voor elke coin pakken we de live prijs die bij het database-item hoort. ?>
                                    <?php $priceInfo = $livePrices[$coin->getId()] ?? ['price' => null, 'change24h' => null, 'updatedAt' => null, 'currency' => 'EUR']; ?>
                                    <tr data-coin-row data-coin-id="<?= $coin->getId() ?>">
                                        <td style="color:var(--muted); font-family:'Space Mono',monospace; font-size:0.75rem;">
                                            <?= $coin->getId() ?>
                                        </td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($coin->getName()) ?></td>
                                        <td><span class="symbol-badge"><?= htmlspecialchars($coin->getSymbol()) ?></span></td>
                                        <td>
                                            <div class="live-price" data-live-price>
                                                <?= isset($priceInfo['price']) && $priceInfo['price'] !== null ? '€ ' . number_format((float) $priceInfo['price'], $priceInfo['price'] < 1 ? 6 : 2, ',', '.') : 'n.v.t.' ?>
                                            </div>
                                            <div class="live-meta">
                                                <span class="live-change <?= isset($priceInfo['change24h']) && $priceInfo['change24h'] !== null && (float) $priceInfo['change24h'] >= 0 ? 'is-positive' : 'is-negative' ?>" data-live-change>
                                                    <?php if (isset($priceInfo['change24h']) && $priceInfo['change24h'] !== null): ?>
                                                        <?= ((float) $priceInfo['change24h'] >= 0 ? '+' : '') . number_format((float) $priceInfo['change24h'], 2, ',', '.') ?>%
                                                    <?php else: ?>
                                                        n.v.t.
                                                    <?php endif; ?>
                                                </span>
                                                <span class="live-updated" data-live-updated>
                                                    <?php if (!empty($priceInfo['updatedAt'])): ?>
                                                        <?= date('H:i:s', (int) $priceInfo['updatedAt']) ?>
                                                    <?php else: ?>
                                                        --:--:--
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="description-cell"><?= htmlspecialchars($coin->getDescription()) ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="index.php?action=edit&id=<?= $coin->getId() ?>"
                                                   class="btn btn-edit">✏️</a>
                                                <a href="index.php?action=delete&id=<?= $coin->getId() ?>"
                                                   class="btn btn-delete"
                                                   onclick="return confirm('Weet je zeker dat je <?= htmlspecialchars($coin->getName()) ?> wilt verwijderen?')">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /.grid -->
</div><!-- /.container -->

<script>
(function () {
    // Deze route wordt gebruikt voor de live prijzen in de tabel.
    const apiUrl = 'api/prices.php';
    // De tabel ververst automatisch zonder de pagina opnieuw te laden.
    const refreshIntervalMs = 30000;

    function formatPrice(value) {
        const digits = value < 1 ? 6 : 2;
        return new Intl.NumberFormat('nl-NL', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
        }).format(value);
    }

    function formatChange(value) {
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    }

    function formatTime(timestamp) {
        return new Intl.DateTimeFormat('nl-NL', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(new Date(timestamp * 1000));
    }

    async function refreshPrices() {
        try {
            // Vraag verse prijzen op en werk alleen de velden bij die live zijn.
            const response = await fetch(apiUrl, { cache: 'no-store' });
            const data = await response.json();

            if (!data.success || !data.prices) {
                return;
            }

            document.querySelectorAll('[data-coin-row]').forEach((row) => {
                const coinId = row.getAttribute('data-coin-id');
                const priceInfo = data.prices[coinId];

                if (!priceInfo) {
                    return;
                }

                const priceElement = row.querySelector('[data-live-price]');
                const changeElement = row.querySelector('[data-live-change]');
                const updatedElement = row.querySelector('[data-live-updated]');

                if (priceElement) {
                    priceElement.textContent = priceInfo.price !== null ? formatPrice(Number(priceInfo.price)) : 'n.v.t.';
                }

                if (changeElement) {
                    const change = priceInfo.change24h !== null ? Number(priceInfo.change24h) : null;
                    changeElement.textContent = change !== null ? formatChange(change) : 'n.v.t.';
                    changeElement.classList.toggle('is-positive', change !== null && change >= 0);
                    changeElement.classList.toggle('is-negative', change !== null && change < 0);
                }

                if (updatedElement) {
                    updatedElement.textContent = priceInfo.updatedAt ? formatTime(Number(priceInfo.updatedAt)) : '--:--:--';
                }
            });
        } catch (error) {
            console.error('Live koers kon niet worden ververst', error);
        }
    }

    refreshPrices();
    window.setInterval(refreshPrices, refreshIntervalMs);
})();
</script>

</body>
</html>

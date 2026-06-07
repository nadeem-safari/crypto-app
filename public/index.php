<?php

// =============================================
// Autoload alle klassen
// =============================================
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Coin.php';
require_once __DIR__ . '/../repositories/CoinRepository.php';
require_once __DIR__ . '/../services/CoinService.php';

$service = new CoinService();

// =============================================
// Controller: verwerk POST-acties
// =============================================
$feedback = null;  // ['type' => 'success'|'error', 'message' => '...']

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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

// Data ophalen voor de weergave
$searchQuery = $_GET['search'] ?? '';
$coins       = $searchQuery ? $service->searchCoins($searchQuery) : $service->getAllCoins();
$editCoin    = ($action === 'edit' && $id) ? $service->getCoinById($id) : null;

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

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div class="stat-label">Totaal coins</div>
            <div class="stat-value"><?= count($coins) ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Database</div>
            <div class="stat-value" style="font-size:1rem; padding-top:0.4rem; color:var(--green);">● Online</div>
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
                                    <th>Beschrijving</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coins as $coin): ?>
                                    <tr>
                                        <td style="color:var(--muted); font-family:'Space Mono',monospace; font-size:0.75rem;">
                                            <?= $coin->getId() ?>
                                        </td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($coin->getName()) ?></td>
                                        <td><span class="symbol-badge"><?= htmlspecialchars($coin->getSymbol()) ?></span></td>
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

</body>
</html>

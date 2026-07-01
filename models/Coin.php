<?php

/**
 * Coin Model
 * 
 * Vertegenwoordigt één rij uit de `coins` tabel.
 * Bevat alleen data en getters/setters — geen database-logica.
 */
class Coin
{
    private int    $id;
    private string $name;
    private string $symbol;
    private string $description;
    private string $createdAt;
    private ?float $livePrice;
    private string $liveCurrency;
    private ?float $liveChange24h;
    private ?int $liveUpdatedAt;

    public function __construct(
        int    $id,
        string $name,
        string $symbol,
        string $description = '',
        string $createdAt   = '',
        ?float $livePrice = null,
        string $liveCurrency = 'EUR',
        ?float $liveChange24h = null,
        ?int $liveUpdatedAt = null
    ) {
        $this->id          = $id;
        $this->name        = $name;
        $this->symbol      = strtoupper($symbol);
        $this->description = $description;
        $this->createdAt    = $createdAt;
        $this->livePrice    = $livePrice;
        $this->liveCurrency = strtoupper($liveCurrency);
        $this->liveChange24h = $liveChange24h;
        $this->liveUpdatedAt = $liveUpdatedAt;
    }

    // --- Getters ---
    public function getId():          int    { return $this->id; }
    public function getName():        string { return $this->name; }
    public function getSymbol():      string { return $this->symbol; }
    public function getDescription(): string { return $this->description; }
    public function getCreatedAt():   string { return $this->createdAt; }
    public function getLivePrice():   ?float  { return $this->livePrice; }
    public function getLiveCurrency(): string { return $this->liveCurrency; }
    public function getLiveChange24h(): ?float { return $this->liveChange24h; }
    public function getLiveUpdatedAt(): ?int   { return $this->liveUpdatedAt; }

    // --- Setters ---
    public function setName(string $name):               void { $this->name        = $name; }
    public function setSymbol(string $symbol):           void { $this->symbol      = strtoupper($symbol); }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setLivePrice(?float $price):         void { $this->livePrice    = $price; }
    public function setLiveCurrency(string $currency):   void { $this->liveCurrency = strtoupper($currency); }
    public function setLiveChange24h(?float $change):    void { $this->liveChange24h = $change; }
    public function setLiveUpdatedAt(?int $timestamp):   void { $this->liveUpdatedAt = $timestamp; }

    /**
     * Maakt een Coin-object van een database-rij (array).
     * Handig zodat je niet overal new Coin(...) met losse velden hoeft te schrijven.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (int)  $row['id'],
                   $row['name'],
                   $row['symbol'],
                   $row['description'] ?? '',
                   $row['created_at']  ?? '',
                   array_key_exists('live_price', $row) && $row['live_price'] !== null ? (float) $row['live_price'] : null,
                   $row['live_currency'] ?? 'EUR',
                   array_key_exists('live_change_24h', $row) && $row['live_change_24h'] !== null ? (float) $row['live_change_24h'] : null,
                   array_key_exists('live_updated_at', $row) && $row['live_updated_at'] !== null ? (int) $row['live_updated_at'] : null
        );
    }

    /**
     * Geeft de coin terug als array (handig voor formulieren of JSON).
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'symbol'      => $this->symbol,
            'description' => $this->description,
            'created_at'  => $this->createdAt,
            'live_price'  => $this->livePrice,
            'live_currency' => $this->liveCurrency,
            'live_change_24h' => $this->liveChange24h,
            'live_updated_at' => $this->liveUpdatedAt,
        ];
    }
}

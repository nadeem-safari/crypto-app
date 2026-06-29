<?php

/**
 * Database
 * 
 * Verantwoordelijk voor de verbinding met de MySQL database.
 * Gebruikt het Singleton-patroon zodat er maar één connectie bestaat.
 */
class Database
{
    // Database instellingen — pas dit aan naar jouw eigen omgeving
    private string $host     = '127.0.0.1';
    private int    $port     = 3306;
    private string $dbname   = 'cryptoapp';
    private string $username = 'root';
    private string $password = '';
    private string $charset  = 'utf8mb4';

    private static ?Database $instance = null;
    private PDO $connection;

    // Private constructor → Singleton patroon
    private function __construct()
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $portsToTry = array_values(array_unique([$this->port, 3306, 3307]));
        $lastException = null;

        foreach ($portsToTry as $port) {
            $dsn = "mysql:host={$this->host};port={$port};dbname={$this->dbname};charset={$this->charset}";

            try {
                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                $this->port = $port;
                return;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        throw new RuntimeException(
            "Databaseverbinding mislukt. Controleer of MySQL/MariaDB draait in XAMPP en of database 'cryptoapp' bestaat. " .
            "Getest op poorten " . implode(', ', $portsToTry) . ". Details: " . ($lastException?->getMessage() ?? 'onbekende fout')
        );
    }

    /**
     * Geeft de enige instantie van Database terug (Singleton).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Geeft de PDO-connectie terug voor gebruik in repositories.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
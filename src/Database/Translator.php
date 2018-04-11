<?php


namespace App\Database;


use PDO;

class Translator
{
    /** @var \PDO */
    private $connection;
    /** @var array */
    private $translations = [];

    /**
     * Anime constructor.
     *
     * @param SQLiteConnection $connection
     */
    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->connect();
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function translate(string $name): string
    {
        if (!isset($this->translations[$name])) {
            $stmt = $this->connection->prepare("SELECT main FROM anime WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->translations[$name] = $item['main'] ?: $name;
        }

        return $this->translations[$name];
    }
}

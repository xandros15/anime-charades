<?php


namespace App\Database;


use PDO;

class Anime
{
    /** @var \PDO */
    private $connection;

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
     * @param int $id
     * @param string $name
     */
    public function update(int $id, string $name)
    {
        $stmt = $this->connection->prepare("UPDATE anime SET main = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $stmt = $this->connection->prepare("UPDATE anime SET main = NULL WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function hint(string $name): array
    {
        $stmt = $this->connection->prepare("SELECT name FROM anime WHERE name LIKE ? ORDER BY name LIMIT 10");
        $stmt->execute(['%' . $name . '%']);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function related(string $name)
    {
        $sql = "SELECT * FROM anime WHERE `main` = (SELECT main FROM anime WHERE name = ? LIMIT 1) ORDER BY `name`";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$name]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $search
     * @param int $page
     *
     * @return array
     */
    public function search(string $search, int $page = 1)
    {
        $offset = (int) ($page - 1) * 50;
        $stmt = $this->connection->prepare("SELECT * FROM anime WHERE `name` LIKE ? ORDER BY `name` LIMIT 50 OFFSET " . $offset);
        $stmt->execute(['%' . $search . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $page
     *
     * @return \PDOStatement
     */
    public function list(int $page = 1)
    {
        $offset = (int) ($page - 1) * 500;

        return $this->connection->query("SELECT * FROM anime ORDER BY name LIMIT 500 OFFSET " . $offset,
            PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function allTitles(): array
    {
        $stmt = $this->connection->prepare("SELECT name FROM anime ORDER BY name");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

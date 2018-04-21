<?php


namespace App;


class AnimeCharades implements \JsonSerializable
{

    const GAMES_DIR = __DIR__ . '/../storage/games/%s.json';

    /** @var CharadeItem[] */
    private $done = [];
    /** @var CharadeItem[] */
    private $list;
    /** @var string */
    private $name;
    /** @var array */
    private $players;
    /** @var bool */
    private $started;
    /** @var string */
    private $currentPlayer;

    /**
     * AnimeCharades constructor.
     *
     * @param string $name
     * @param CharadeItem[] $list
     * @param CharadeItem[] $done
     * @param bool $started
     * @param null $currentPlayer
     * @param array $players
     */
    public function __construct(
        string $name,
        array $list,
        array $done = [],
        bool $started = false,
        $currentPlayer = null,
        array $players = []
    ) {
        $this->name = $name;
        $this->list = $list;
        $this->done = $done;
        $this->started = $started;
        $this->currentPlayer = $currentPlayer;
        $this->players = $players;
    }

    public function __destruct()
    {
        if ($this->name) {
            $filename = sprintf(self::GAMES_DIR, $this->name);
            $json = json_encode($this);
            file_put_contents($filename, $json);
            chmod($filename, 0777);
        }
    }

    public function start()
    {
        $this->started = true;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return (bool) $this->started;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function addPlayer(string $name)
    {
        if ($this->started) {
            throw new \RuntimeException('Game is close');
        }
        if (in_array($name, $this->players)) {
            throw new \RuntimeException('Player already exist');
        }
        $this->players[] = $name;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function chosePlayer()
    {
        if ($this->currentPlayer === null) {
            $this->currentPlayer = $this->players[array_rand($this->players)];
        } else {
            $index = array_search($this->currentPlayer, $this->players);
            $this->currentPlayer = $this->players[($index + 1) % count($this->players)];
        }
    }

    public function getCurrentPlayer(): string
    {
        return $this->currentPlayer;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->list);
    }

    /**
     * @return bool
     */
    public function hasItems(): bool
    {
        return !empty($this->list);
    }

    /**
     * @return CharadeItem
     */
    public function roll(): CharadeItem
    {
        $index = array_rand($this->list);
        $guessing = $this->list[$index];
        unset($this->list[$index]);
        $this->done[] = $guessing;

        return $guessing;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $game = [
            'started' => $this->started,
            'player' => $this->currentPlayer,
            'players' => $this->players,
            'list' => $this->list,
            'done' => $this->done,
        ];

        return $game;
    }
}

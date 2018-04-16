<?php


namespace App;


use App\Database\Translator;

class AnimeCharades implements \JsonSerializable
{
    const NUMBER_OF_POSITIONS = 200;

    private const GAMES_DIR = __DIR__ . '/../storage/games/%s.json';

    /** @var CharadeItem[] */
    private $done = [];
    /** @var CharadeItem[] */
    private $list;
    /** @var string */
    private $name;
    /** @var array */
    private $players = [];
    /** @var bool */
    private $close = false;
    /** @var string */
    private $player = null;

    public function close()
    {
        $this->close = true;
    }


    public static function delete(string $name)
    {
        $filename = sprintf(self::GAMES_DIR, $name);
        @unlink($filename);
    }

    /**
     * @param string $name
     *
     * @return AnimeCharades
     */
    public static function load(string $name)
    {
        $charades = new self();
        $filename = sprintf(self::GAMES_DIR, $name);
        if (!file_exists($filename)) {
            throw new \RuntimeException('Game file not found');
        }
        $json = file_get_contents($filename);
        $game = json_decode($json, true);
        unset($json);
        $charades->name = $name;
        $charades->players = $game['players'];
        $charades->close = $game['close'];
        $charades->player = $game['player'];
        $charades->loadDone($game['done']);
        $charades->loadList($game['list']);

        return $charades;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return (bool) $this->close;
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
        if ($this->close) {
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
        if ($this->player === null) {
            $this->player = $this->players[array_rand($this->players)];
        } else {
            $index = array_search($this->player, $this->players);
            if (!$index) {
                $this->player = $this->players[array_rand($this->players)];
            } else {
                $this->player = $this->players[($index + 1) % count($this->players)];
            }
        }
    }

    public function getCurrentPlayer(): string
    {
        return $this->player;
    }

    /**
     * @param array $list
     */
    private function loadList(array $list)
    {
        foreach ($list as $item) {
            $user = reset($item['users']);
            unset($item['users'][0]);
            $charadeItem = new CharadeItem($item['name'], $user);
            foreach ($item['users'] as $user) {
                $charadeItem->addUser($user);
            }
            $this->list[] = $charadeItem;
        }
    }

    /**
     * @param array $done
     */
    private function loadDone(array $done)
    {
        foreach ($done as $item) {
            $user = reset($item['users']);
            unset($item['users'][0]);
            $charadeItem = new CharadeItem($item['name'], $user);
            foreach ($item['users'] as $user) {
                $charadeItem->addUser($user);
            }
            $this->done[] = $charadeItem;
        }
    }

    /**
     * @param array $lists
     * @param Translator $translator
     *
     * @param string $name
     *
     * @return AnimeCharades
     */
    public static function newGame(array $lists, Translator $translator, string $name = '')
    {
        $charades = new self();
        $charades->generateList($lists, $translator);
        $json = json_encode($charades);
        $charades->name = $name ?: substr(sha1($json), 0, 8);

        return $charades;
    }


    public function __destruct()
    {
        if ($this->name) {
            $filename = sprintf(self::GAMES_DIR, $this->name);
            $json = json_encode($this);
            file_put_contents($filename, $json);
        }
    }

    /**
     * @param AnimeList[] $lists
     * @param Translator $translator
     */
    public function generateList(array $lists, Translator $translator)
    {
        /** @var $items CharadeItem[] */
        $items = [];
        foreach ($lists as $list) {
            if (!$list instanceof AnimeList) {
                throw new \InvalidArgumentException('List must be a ' . AnimeList::class . ' instance');
            }
            $arrayList = $list->toArray();
            foreach ($arrayList as $item) {
                $name = $translator->translate($item);
                if (!isset($items[$name])) {
                    $items[$name] = new CharadeItem($name, $list->getName());
                } elseif (!in_array($list->getName(), $items[$name]->getUsers())) {
                    $items[$name]->addUser($list->getName());
                }
            }
        }

        usort($items, function (CharadeItem $a, CharadeItem $b) {
            return $b->count() <=> $a->count();
        });

        $items = array_slice($items, 0, self::NUMBER_OF_POSITIONS, true);
        usort($items, function (CharadeItem $a, CharadeItem $b) {
            return $a->getName() <=> $b->getName();
        });

        $this->list = $items;
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
            'close' => $this->close,
            'player' => $this->player,
            'players' => $this->players,
            'list' => $this->list,
            'done' => $this->done,
        ];

        return $game;
    }
}

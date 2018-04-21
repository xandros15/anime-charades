<?php


namespace App;


use App\Database\Translator;

class AnimeCharadesManager
{
    const NUMBER_OF_POSITIONS = 100;

    /**
     * @param array $lists
     * @param Translator $translator
     * @param string $name
     *
     * @return AnimeCharades
     */
    public function createGame(array $lists, Translator $translator, string $name = ''): AnimeCharades
    {
        $list = $this->generateList($lists, $translator);
        if (!$name) {
            $json = json_encode($list);
            $name = substr(sha1($json), 0, 8);
        }

        return new AnimeCharades($name, $list);

    }

    public function delete(string $name)
    {
        $filename = sprintf(AnimeCharades::GAMES_DIR, $name);
        @unlink($filename);
    }

    /**
     * @param string $name
     *
     * @return AnimeCharades
     */
    public function load(string $name): AnimeCharades
    {
        $filename = sprintf(AnimeCharades::GAMES_DIR, $name);
        if (!file_exists($filename)) {
            throw new \RuntimeException('Game file not found');
        }
        $json = file_get_contents($filename);
        $game = json_decode($json, true);
        unset($json);

        return new AnimeCharades(
            $name,
            $this->listToCharadeItemList($game['list']),
            $this->listToCharadeItemList($game['done']),
            $game['started'],
            $game['player'],
            $game['players']
        );
    }

    /**
     * @param array $items
     *
     * @return CharadeItem[]
     */
    private function listToCharadeItemList(array $items): array
    {
        $list = [];
        foreach ($items as $item) {
            $user = reset($item['users']);
            unset($item['users'][0]);
            $charadeItem = new CharadeItem($item['name'], $user);
            foreach ($item['users'] as $user) {
                $charadeItem->addUser($user);
            }
            $list[] = $charadeItem;
        }

        return $list;
    }

    /**
     * @param array $lists
     * @param Translator $translator
     *
     * @return CharadeItem[]|array
     */
    private function generateList(array $lists, Translator $translator)
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

        shuffle($items);

        usort($items, function (CharadeItem $a, CharadeItem $b) {
            return $b->count() <=> $a->count();
        });

        $items = array_slice($items, 0, self::NUMBER_OF_POSITIONS, true);
        usort($items, function (CharadeItem $a, CharadeItem $b) {
            return $a->getName() <=> $b->getName();
        });

        return $items;
    }
}

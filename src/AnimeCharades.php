<?php


namespace App;


use App\Database\Translator;

class AnimeCharades
{
    const NUMBER_OF_POSITIONS = 400;

    /** @var Done */
    private $done;
    /** @var CharadeItem[] */
    private $list;

    /**
     * AnimeCharades constructor.
     */
    public function __construct()
    {
        if (!isset($_SESSION['charade.done'])) {
            $_SESSION['charade.done'] = [];
        }
        $this->done = new Done($_SESSION['charade.done']);
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
        $list = $this->filter($this->list);
        $guessing = $list[array_rand($list)];
        $this->done->add($guessing->getName());

        return $guessing;
    }

    /**
     * @param array $list
     *
     * @return array
     */
    public function filter(array $list): array
    {
        return array_filter($list, function (CharadeItem $item) {
            return !$this->done->has($item->getName());
        });
    }
}

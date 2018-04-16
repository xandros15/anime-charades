<?php


namespace App;


class AnimeListManager
{

    private const DIRECTORY = __DIR__ . '/../storage/lists/';

    /**
     * @return array
     */
    public function listAll(): array
    {
        $lists = [];
        foreach (glob(self::DIRECTORY . '*.json') as $filename) {
            $lists[] = basename($filename, '.json');
        }

        return $lists;
    }

    /**
     * @return AnimeList[]
     */
    public function loadAll(): array
    {
        $lists = [];
        foreach (glob(self::DIRECTORY . '*.json') as $filename) {
            $content = file_get_contents($filename);
            $list = json_decode($content);
            $name = basename($filename, '.json');
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \RuntimeException('JSON error: ' . json_last_error_msg());
            }

            $lists[] = new AnimeList($name, $list);
        }

        return $lists;
    }

    /**
     * @param string $name
     *
     * @return AnimeList|bool
     */
    public function load(string $name)
    {
        $filename = self::DIRECTORY . $name . '.json';
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $list = json_decode($content);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \RuntimeException('JSON error: ' . json_last_error_msg());
            }

            return new AnimeList($name, $list);
        }

        return false;
    }

    /**
     * save the list on disk
     *
     * @param AnimeList $list
     */
    public function save(AnimeList $list)
    {
        $filename = self::DIRECTORY . $list->getName() . '.json';
        file_put_contents($filename, (string) $list);
        chmod($filename, '0777');
    }
}

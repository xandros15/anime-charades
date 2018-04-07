<?php


namespace App;


class AnimeList implements \Countable, \JsonSerializable
{
    /** @var array */
    private $list;
    /** @var string */
    private $name;

    /**
     * AnimeList constructor.
     *
     * @param string $name
     * @param array $list
     */
    public function __construct(string $name, array $list = [])
    {
        $this->name = $name;
        $this->list = $list;
    }


    /**
     * @param string $name
     *
     * @return static|false
     */
    public static function load(string $name)
    {
        $filename = __DIR__ . '/../storage/' . $name . '.json';
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $list = json_decode($content);
            if (!$list) {
                throw new \RuntimeException('JSON ' . json_last_error_msg());
            }

            return new static($name, $list);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->list);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->list;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->list);
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
        return $this->list;
    }

    /**
     * save the list on disk
     */
    public function save()
    {
        $filename = __DIR__ . '/../storage/' . $this->getName() . '.json';
        file_put_contents($filename, json_encode($this->list));
        chmod($filename, '0777');
    }
}

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

}

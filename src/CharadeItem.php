<?php


namespace App;


class CharadeItem implements \Countable
{
    /** @var string */
    private $name;
    /** @var string[] */
    private $users = [];
    /** @var int */
    private $count = 1;

    /**
     * CharadeItem constructor.
     *
     * @param string $name
     * @param string $user
     */
    public function __construct(string $name, string $user)
    {
        $this->name = $name;
        $this->users[] = $user;
    }

    /**
     * @param string $user
     */
    public function addUser(string $user)
    {
        $this->users[] = $user;
        $this->count++;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->count;
    }
}

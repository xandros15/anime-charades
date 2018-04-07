<?php


namespace App;


class Done
{
    /** @var array */
    private $list;

    /**
     * Done constructor.
     *
     * @param array $list
     */
    public function __construct(array &$list)
    {
        $this->list = &$list;
    }

    /**
     * @param string $name
     */
    public function add(string $name)
    {
        if (!$this->has($name)) {
            $this->list[] = $name;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        foreach ($this->list as $item) {
            if ($item == $name) {
                return true;
            }
        }

        return false;
    }
}

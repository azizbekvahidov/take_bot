<?php


namespace App\Modules\Telegram\Updates;

/**
 * Class From
 * @package App\Modules\Telegram\Updates
 */
final class From
{
    /**
     * @var array
     */
    private $from;

    /**
     * From constructor.
     * @param array $from
     */
    public function __construct(array $from)
    {
        $this->from = $from;
    }


    public function __get($name)
    {
        return $this->from[$name] ?? null;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->from['id'];
    }

}

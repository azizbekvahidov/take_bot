<?php

namespace App\Modules\Telegram\Updates;

class Location
{
    /**
     * @var array
     */
    private $location;

    public function __construct(array $location)
    {
        $this->location = $location;
    }

    /**
     * @return float
     */
    public function latitude(): float
    {
        return $this->location['latitude'];
    }

    /**
     * @return float
     */
    public function longitude(): float
    {
        return $this->location['longitude'];
    }
}

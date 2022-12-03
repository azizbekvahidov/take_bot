<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MenuListEmptyException extends Exception
{
    protected array $data = [];

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return MenuListEmptyException
     */
    public static function make(string $message = '', int $code = 0, ?Throwable $previous = null): MenuListEmptyException
    {
        return new self($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array|null $data
     * @return MenuListEmptyException
     */
    public function setData(?array $data): MenuListEmptyException
    {
        if (is_array($data)) {
            $this->data = $data;
        }
        return $this;
    }

}

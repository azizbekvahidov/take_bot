<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiServerException extends Exception
{
    protected array $data = [];

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return ApiServerException
     */
    public static function make(string $message = '', int $code = 0, ?Throwable $previous = null): ApiServerException
    {
        return new self($message, $code, $previous);
    }

    /**
     * @param array|null $data
     * @return $this
     */
    public function setData(?array $data): ApiServerException
    {
        if (is_array($data)) {
            $this->data = $data;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

}

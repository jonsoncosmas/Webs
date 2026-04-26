<?php

namespace App\Exceptions;

use InvalidArgumentException;

class BusFieldException extends InvalidArgumentException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}

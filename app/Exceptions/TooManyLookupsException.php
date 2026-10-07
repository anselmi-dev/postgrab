<?php

namespace App\Exceptions;

use RuntimeException;

class TooManyLookupsException extends RuntimeException
{
    public function __construct(public bool $challenge = false)
    {
        parent::__construct('Too many lookups.');
    }
}

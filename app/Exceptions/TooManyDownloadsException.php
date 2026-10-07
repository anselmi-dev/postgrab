<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class TooManyDownloadsException extends RuntimeException
{
    public function __construct(
        public CarbonInterface $until,
        public string $reason = 'limit',
    ) {
        parent::__construct('Too many downloads.');
    }
}

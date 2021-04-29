<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Exceptions;

use Exception;

class ChannelErrorException extends Exception
{
    protected $raw;

    public function __construct($message, $code, $raw)
    {
        parent::__construct($message, $code);

        $this->raw = $raw;
    }

    public function getRawResult()
    {
        return $this->raw;
    }
}

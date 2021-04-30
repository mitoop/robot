<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Exceptions;

use Exception;

class ChannelErrorException extends Exception
{
    protected $rawResponse;

    public function __construct($message, $code, $rawResponse)
    {
        parent::__construct($message, $code);

        $this->rawResponse = $rawResponse;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}

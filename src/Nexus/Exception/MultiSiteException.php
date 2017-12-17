<?php

namespace Sztyup\Nexus\Exceptions;

use Exception;
use Throwable;

class MultiSiteException extends Exception implements Throwable
{
    public function __construct($message = "")
    {
        parent::__construct($message, 0, null);
    }
}
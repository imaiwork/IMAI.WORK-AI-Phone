<?php

namespace Smf\ConnectionPool;

class BorrowConnectionTimeoutException extends \Exception
{
    protected $timeout;

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function setTimeout(float $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
}
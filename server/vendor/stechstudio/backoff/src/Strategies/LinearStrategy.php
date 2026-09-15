<?php
namespace STS\Backoff\Strategies;

/**
 * Class LinearStrategy
 * @package STS\Backoff\Strategies
 */
class LinearStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime($attempt)
    {
        return $attempt * $this->base;
    }
}

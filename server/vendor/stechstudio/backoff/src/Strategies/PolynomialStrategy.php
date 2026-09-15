<?php
namespace STS\Backoff\Strategies;

/**
 * Class PolynomialStrategy
 * @package STS\Backoff\Strategies
 */
class PolynomialStrategy extends AbstractStrategy
{
    /**
     * @var int
     */
    protected $degree = 2;

    /**
     * PolynomialStrategy constructor.
     *
     * @param int $degree
     * @param int $base
     */
    public function __construct($base = null, $degree = null)
    {
        if(!is_null($degree)) {
            $this->degree = $degree;
        }

        parent::__construct($base);
    }

    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime($attempt)
    {
        return (int) pow($attempt, $this->degree) * $this->base;
    }

    /**
     * @return int|null
     */
    public function getDegree()
    {
        return $this->degree;
    }
}

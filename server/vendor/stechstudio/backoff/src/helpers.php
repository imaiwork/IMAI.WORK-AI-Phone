<?php
if(!function_exists('backoff')) {
    function backoff($callback, $maxAttempts = null, $strategy = null, $waitCap = null, $useJitter = null)
    {
        return (new \STS\Backoff\Backoff($maxAttempts, $strategy, $waitCap, $useJitter))->run($callback);
    }
}

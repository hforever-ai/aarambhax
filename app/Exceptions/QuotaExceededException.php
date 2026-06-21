<?php

namespace App\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $window,
        public readonly int $limit,
        public readonly int $used,
        public readonly ?int $resetSeconds = null,
        string $message = '',
    ) {
        if ($message === '') {
            $resetText = $resetSeconds !== null
                ? ' Try again in '.($resetSeconds < 60
                    ? $resetSeconds.' seconds.'
                    : ceil($resetSeconds / 60).' minutes.')
                : '';
            $message = "You've reached your {$window} usage limit ({$used} of {$limit} calls used).{$resetText}";
        }
        parent::__construct($message);
    }
}

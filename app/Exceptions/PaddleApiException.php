<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Paddle API call that came back non-2xx. getMessage() is Paddle's own human-readable
 * `error.detail` (safe to show an admin as-is); getPaddleCode() is the machine code,
 * e.g. subscription_update_when_canceled.
 */
class PaddleApiException extends RuntimeException
{
    public function __construct(
        string $message,
        protected ?string $paddleCode = null,
        protected int $httpStatus = 0
    ) {
        parent::__construct($message);
    }

    public function getPaddleCode(): ?string
    {
        return $this->paddleCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}

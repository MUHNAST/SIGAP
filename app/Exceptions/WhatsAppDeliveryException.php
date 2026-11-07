<?php

namespace App\Exceptions;

use RuntimeException;

class WhatsAppDeliveryException extends RuntimeException
{
    public function __construct(
        public readonly string $failureType,
        string $message,
        public readonly ?int $httpStatus = null
    ) {
        parent::__construct($message);
    }
}

<?php

declare(strict_types=1);

namespace Amodb\Exception;

/**
 * Raised when the connection fails before a response arrives (DNS, TLS, refused, reset).
 * The status is 0 because no response was received.
 */
class NetworkException extends AmodbException
{
}

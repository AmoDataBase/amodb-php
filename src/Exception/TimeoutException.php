<?php

declare(strict_types=1);

namespace Amodb\Exception;

/**
 * Raised when a request does not finish within the configured timeout.
 * The status is 0 because no response was received.
 */
class TimeoutException extends AmodbException
{
}

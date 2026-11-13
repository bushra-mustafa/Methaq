<?php

declare(strict_types=1);

namespace App\Domains\Users\Exceptions;

use RuntimeException;

/** An account or invitation cannot be suspended or reinstated; the message is shown to the administrator. */
final class ModerationException extends RuntimeException {}

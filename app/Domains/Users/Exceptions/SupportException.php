<?php

declare(strict_types=1);

namespace App\Domains\Users\Exceptions;

use DomainException;

/** A support-conversation request the current state refuses (the message is shown to the person who asked). */
final class SupportException extends DomainException {}

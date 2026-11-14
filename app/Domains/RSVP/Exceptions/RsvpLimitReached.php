<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Exceptions;

use DomainException;

/** An invitation already holds as many replies as one event may collect. */
final class RsvpLimitReached extends DomainException {}

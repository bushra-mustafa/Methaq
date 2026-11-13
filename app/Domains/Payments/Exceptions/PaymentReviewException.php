<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use DomainException;

/** A receipt decision that the order's current state does not allow (message is shown to the administrator). */
final class PaymentReviewException extends DomainException {}

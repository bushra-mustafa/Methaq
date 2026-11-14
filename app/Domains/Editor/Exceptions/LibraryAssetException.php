<?php

declare(strict_types=1);

namespace App\Domains\Editor\Exceptions;

use DomainException;

/** A library change the asset's current state refuses (the message is shown to the administrator). */
final class LibraryAssetException extends DomainException {}

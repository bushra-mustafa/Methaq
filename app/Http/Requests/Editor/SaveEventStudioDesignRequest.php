<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;

/** An administrator's studio save: the owner's document and revision rules, with the administrator's policy. */
final class SaveEventStudioDesignRequest extends SaveDesignRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();
        $event = $this->route('event');

        return $admin instanceof User && $event instanceof Event && $admin->can('administerDesign', $event);
    }
}

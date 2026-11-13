<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;

/** Same document and revision rules as an owner's save; only the actor check differs. */
final class SaveTemplateDesignRequest extends SaveDesignRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();
        $template = $this->route('template');

        return $admin instanceof User && $template instanceof Template && $admin->can('design', $template);
    }
}

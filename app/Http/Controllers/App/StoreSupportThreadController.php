<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\OpenSupportThreadAction;
use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Exceptions\SupportException;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\OpenSupportThreadRequest;
use Illuminate\Http\RedirectResponse;

final class StoreSupportThreadController extends Controller
{
    public function __invoke(OpenSupportThreadRequest $request, OpenSupportThreadAction $openThread): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $thread = $openThread->execute(
                $user,
                SupportTopic::from((string) $request->validated('topic')),
                (string) $request->validated('subject'),
                (string) $request->validated('body'),
            );
        } catch (SupportException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('app.support.show', $thread)->with('status', 'support-sent');
    }
}

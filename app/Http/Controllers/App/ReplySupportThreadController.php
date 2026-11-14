<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\ReplyToSupportThreadAction;
use App\Domains\Users\Exceptions\SupportException;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplySupportThreadRequest;
use Illuminate\Http\RedirectResponse;

final class ReplySupportThreadController extends Controller
{
    public function __invoke(ReplySupportThreadRequest $request, SupportThread $thread, ReplyToSupportThreadAction $reply): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($thread->user_id === $user->getKey(), 404);

        try {
            $reply->execute($user, (int) $thread->getKey(), (string) $request->validated('body'));
        } catch (SupportException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('app.support.show', $thread);
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Users\Models\CloudMsgWebhook;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Jobs\ProcessCloudMsgWebhook;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Console\Command;

final class DispatchWhatsAppWork extends Command
{
    protected $signature = 'methaq:dispatch-whatsapp';

    protected $description = 'Dispatch durable WhatsApp notifications and incoming commands';

    public function handle(): int
    {
        if (! CloudMsgSettings::get('enabled')) {
            return self::SUCCESS;
        }
        foreach (CloudMsgWebhook::query()->where('status', 'pending')->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))->orderBy('id')->limit(100)->pluck('id') as $id) {
            ProcessCloudMsgWebhook::dispatch((int) $id)->onConnection('database')->onQueue('default');
        }
        foreach (WhatsAppMessage::query()->where('status', 'pending')->where('next_attempt_at', '<=', now())->orderBy('id')->limit(100)->pluck('id') as $id) {
            SendWhatsAppMessage::dispatch((int) $id)->onConnection('database')->onQueue('default');
        }

        return self::SUCCESS;
    }
}

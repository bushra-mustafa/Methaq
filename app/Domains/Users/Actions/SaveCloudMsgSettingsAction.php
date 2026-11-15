<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveCloudMsgSettingsAction
{
    public function __construct(private readonly CloudMsgSettings $settings, private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $input */
    public function execute(User $actor, array $input): void
    {
        Gate::forUser($actor)->authorize('manage-whatsapp-settings');
        if (is_string($input['admin_phone'] ?? null)) {
            $input['admin_phone'] = CloudMsg::phone($input['admin_phone']) ?? $input['admin_phone'];
        }
        $validated = Validator::make($input, [
            'enabled' => ['required', 'boolean'],
            'base_url' => ['required', 'string', 'max:255', 'regex:~^https://cloudmsg\.impaact\.io/v1/?$~D'],
            'instance_id' => ['nullable', 'string', 'max:128', 'regex:/^inst_[A-Za-z0-9]+$/D'],
            'admin_phone' => ['nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/D'],
            'webhook_url' => ['nullable', 'string', 'url:https', 'max:2048'],
            'api_key' => ['nullable', 'string', 'max:4096'],
            'webhook_secret' => ['nullable', 'string', 'max:4096'],
        ], [
            'base_url.regex' => 'استخدمي رابط خدمة CloudMsg: https://cloudmsg.impaact.io/v1',
            'instance_id.regex' => 'معرّف الرقم يبدأ بـ inst_ كما يظهر في CloudMsg.',
            'admin_phone.regex' => 'اكتبي رقم واتساب بالصيغة الدولية مثل +21891XXXXXXX.',
            'webhook_url.url' => 'رابط استقبال الرسائل يجب أن يبدأ بـ https://.',
        ])->validate();
        $validated['enabled'] = (bool) $validated['enabled'];
        $url = $validated['webhook_url'] ?? null;
        if ($url) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $path = (string) parse_url($url, PHP_URL_PATH);
            if (! str_ends_with($path, '/hooks/cloudmsg') || $host === 'localhost' || str_ends_with($host, '.localhost')
                || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null
                || (filter_var($host, FILTER_VALIDATE_IP) && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
                throw ValidationException::withMessages(['webhook_url' => 'استخدمي رابط HTTPS عام ينتهي بـ /hooks/cloudmsg، وليس عنوان localhost.']);
            }
        }
        DB::transaction(function () use ($actor, $validated): void {
            DB::table('settings')->insertOrIgnore(['key' => CloudMsgSettings::KEY, 'value' => Crypt::encryptString('{}'), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('settings')->where('key', CloudMsgSettings::KEY)->lockForUpdate()->first();
            $this->settings->refresh();
            $stored = $this->settings->overrides();
            $next = $stored;
            $changed = [];
            foreach ($validated as $key => $value) {
                if (in_array($key, CloudMsgSettings::SECRETS, true) && ($value === null || trim((string) $value) === '')) {
                    continue;
                }
                $value = is_string($value) ? trim($value) : $value;
                if ($value !== $this->settings->value($key)) {
                    $changed[] = $key;
                }
                $next[$key] = $value;
            }
            if ($next['enabled']) {
                foreach (['api_key', 'instance_id', 'webhook_secret', 'admin_phone', 'webhook_url'] as $field) {
                    if (! (array_key_exists($field, $next) ? $next[$field] : $this->settings->value($field))) {
                        throw ValidationException::withMessages([$field => 'أكملي هذا الحقل قبل تفعيل رسائل واتساب.']);
                    }
                }
            }
            DB::table('settings')->where('key', CloudMsgSettings::KEY)->update(['value' => Crypt::encryptString(json_encode($next, JSON_THROW_ON_ERROR)), 'updated_at' => now()]);
            if ($changed !== []) {
                $this->audit->record($actor, AuditAction::WhatsAppSettingsUpdated, AuditSubjectType::Setting, 0, metadata: ['changedFields' => $changed]);
            }
        }, 3);
        $this->settings->refresh();
    }
}

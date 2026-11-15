<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CloudMsgSettings
{
    public const KEY = 'cloudmsg.integration';

    public const SECRETS = ['api_key', 'webhook_secret', 'previous_webhook_secret'];

    public const FIELDS = ['enabled', 'base_url', 'instance_id', 'admin_phone', 'webhook_url', 'api_key', 'webhook_secret', 'previous_webhook_secret', 'previous_secret_expires_at'];

    /** @var array<string, string|bool|null>|null */
    private ?array $overrides = null;

    public static function get(string $name): string|bool|null
    {
        return app(self::class)->value($name);
    }

    public function value(string $name): string|bool|null
    {
        $overrides = $this->overrides();

        return array_key_exists($name, $overrides) ? $overrides[$name] : config('cloudmsg.'.$name);
    }

    /** @return array<string, string|bool|null> */
    public function overrides(): array
    {
        if ($this->overrides !== null) {
            return $this->overrides;
        }
        // Allows install/migration commands to boot before the settings table exists.
        if (! Schema::hasTable('settings')) {
            return $this->overrides = [];
        }
        $stored = DB::table('settings')->where('key', self::KEY)->value('value');

        return $this->overrides = $stored === null ? [] : json_decode(Crypt::decryptString($stored), true, flags: JSON_THROW_ON_ERROR);
    }

    public function refresh(): void
    {
        $this->overrides = null;
    }

    /** Values safe to place in the admin form; secrets are never returned to the browser. */
    public function formValues(): array
    {
        return [
            'enabled' => (bool) $this->value('enabled'),
            'base_url' => $this->value('base_url'),
            'instance_id' => $this->value('instance_id'),
            'admin_phone' => $this->value('admin_phone'),
            'webhook_url' => $this->value('webhook_url'),
            'api_key' => '', 'webhook_secret' => '',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Users\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_type' => 'system',
            'action' => 'test.created',
            'subject_type' => 'event',
            'subject_id' => 1,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use App\Domains\Users\Enums\ContactIcon;
use Database\Factories\ContactChannelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A way to reach the team (email, phone, social account) shown on the contact page and footer. */
class ContactChannel extends Model
{
    /** @use HasFactory<ContactChannelFactory> */
    use HasFactory;

    /** Links must open a web page, a mail composer or a dialer; anything else (javascript:, data:) is refused. */
    public const URL_PATTERN = '#^(https://[^\s]+|mailto:[^\s@]+@[^\s@]+|tel:\+?[0-9]{5,20})$#i';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = ['name', 'url', 'icon', 'is_active', 'sort_order'];

    protected static function newFactory(): ContactChannelFactory
    {
        return ContactChannelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'icon' => ContactIcon::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<ContactChannel>  $query
     * @return Builder<ContactChannel>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}

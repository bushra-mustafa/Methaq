<?php

declare(strict_types=1);

namespace App\Http\Support;

/**
 * What a crawler or a chat app reads from the page head (title, description, share image).
 * It must be in the HTML the server sends: Inertia sets titles in the browser only, and
 * WhatsApp or Google's first pass never runs the page's JavaScript.
 */
final class PageMeta
{
    public const DEFAULT_DESCRIPTION = 'ميثاق منصة عربية لصناعة الدعوات الإلكترونية: صمّمي بطاقتك، وشاركيها برابط واحد يفتحه ضيوفك كظرف حقيقي.';

    /** @return array{title: string, description: string, url: string, image: string|null, noindex: bool} */
    public static function make(string $title, string $description = self::DEFAULT_DESCRIPTION, ?string $path = null, ?string $image = null, bool $noindex = false): array
    {
        return [
            'title' => $title === '' ? 'ميثاق' : $title.' — ميثاق',
            'description' => $description,
            'url' => $path === null ? url()->current() : url($path),
            'image' => $image,
            'noindex' => $noindex,
        ];
    }
}

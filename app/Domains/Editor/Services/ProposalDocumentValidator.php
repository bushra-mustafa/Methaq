<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use InvalidArgumentException;

final class ProposalDocumentValidator
{
    public const TEMPLATE = 'ivory-wedding';

    public const MAX_BYTES = 6291456;

    private const IMAGE_BYTES = 1048576;

    private const BASE_FIELDS = ['id', 'kind', 'visible', 'x', 'y', 'width', 'height', 'scale', 'rotation', 'opacity', 'gap', 'color'];

    /** @return array<string, mixed> */
    public function initialDocument(): array
    {
        return $this->validate(json_decode(file_get_contents(resource_path('design-proposals/ivory-wedding.json')), true, 16, JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $document @return array<string, mixed> */
    public function validate(array $document): array
    {
        $this->keys($document, ['schemaVersion', 'template', 'parts', 'layerOrder']);
        if (($document['schemaVersion'] ?? null) !== 1 || ($document['template'] ?? null) !== self::TEMPLATE || strlen(json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) > self::MAX_BYTES) {
            throw new InvalidArgumentException('إصدار الاقتراح أو حجمه غير صالح.');
        }
        $catalog = json_decode(file_get_contents(resource_path('design-proposals/ivory-wedding-catalog.json')), true, 16, JSON_THROW_ON_ERROR);
        $expected = array_column($catalog, 'kind', 'id');
        $parts = $document['parts'];
        if (! is_array($parts) || ! array_is_list($parts) || count($parts) !== count($expected)) {
            throw new InvalidArgumentException('عناصر الاقتراح غير مكتملة.');
        }
        $seen = [];
        $leaves = [];
        foreach ($parts as $part) {
            if (! is_array($part) || ! is_string($part['id'] ?? null) || ! isset($expected[$part['id']]) || isset($seen[$part['id']]) || ($part['kind'] ?? null) !== $expected[$part['id']]) {
                throw new InvalidArgumentException('نوع العنصر أو معرّفه غير صالح.');
            }
            $seen[$part['id']] = true;
            $kind = $part['kind'];
            $extra = match ($kind) {
                'text' => ['content', 'font', 'fontSize', 'direction', 'alignment'], 'image' => ['source'], default => []
            };
            $this->keys($part, [...self::BASE_FIELDS, ...$extra]);
            if (! is_bool($part['visible']) || ! is_string($part['color']) || ! preg_match('/^#[0-9a-f]{6}$/i', $part['color'])) {
                throw new InvalidArgumentException('لون أو إظهار العنصر غير صالح.');
            }
            foreach (['x' => [-1800, 1800], 'y' => [-5000, 5000], 'width' => [20, 1200], 'height' => [10, 1500], 'scale' => [.2, 3], 'rotation' => [-180, 180], 'opacity' => [0, 1], 'gap' => [0, 200]] as $key => [$min, $max]) {
                $this->number($part[$key], $min, $max);
            }
            if ($kind === 'text') {
                if (! is_string($part['content']) || mb_strlen($part['content']) > 2000 || preg_match('/[<>]/u', $part['content'])
                    || ! in_array($part['font'], ['amiri', 'pinyon', 'georgia', 'tajawal'], true)
                    || ! in_array($part['direction'], ['rtl', 'ltr', 'auto'], true)
                    || ! in_array($part['alignment'], ['left', 'center', 'right'], true)) {
                    throw new InvalidArgumentException('بيانات النص غير صالحة.');
                }
                $this->number($part['fontSize'], 14, 120);
            }
            if ($kind === 'image') {
                $this->image($part['source']);
            }
            if ($kind !== 'group') {
                $leaves[] = $part['id'];
            }
        }
        $order = $document['layerOrder'];
        if (! is_array($order) || ! array_is_list($order) || count($order) !== count($leaves) || array_filter($order, fn (mixed $id): bool => ! is_string($id)) !== [] || count(array_unique($order)) !== count($leaves) || array_diff($order, $leaves) !== []) {
            throw new InvalidArgumentException('ترتيب الطبقات غير صالح.');
        }

        return $document;
    }

    /** @param array<string, mixed> $value @param list<string> $keys */
    private function keys(array $value, array $keys): void
    {
        if (array_diff(array_keys($value), $keys) !== [] || array_diff($keys, array_keys($value)) !== []) {
            throw new InvalidArgumentException('خصائص العنصر غير مكتملة أو غير مسموحة.');
        }
    }

    private function number(mixed $value, float $min, float $max): void
    {
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value) || $value < $min || $value > $max) {
            throw new InvalidArgumentException('قيمة خارج الحدود المسموحة.');
        }
    }

    private function image(mixed $source): void
    {
        if (! is_string($source)) {
            throw new InvalidArgumentException('الصورة غير صالحة.');
        }
        if (in_array($source, ['architecture', 'ornament', 'swans', 'chandelier', 'clock'], true)) {
            return;
        }
        if (strlen($source) > (int) ceil(self::IMAGE_BYTES * 4 / 3) + 64 || ! preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/]+={0,2})$#D', $source, $matches)) {
            throw new InvalidArgumentException('استخدمي PNG أو JPG أو WebP حتى 1 ميغابايت.');
        }
        $bytes = base64_decode($matches[2], true);
        $info = $bytes === false ? false : @getimagesizefromstring($bytes);
        if ($bytes === false || strlen($bytes) > self::IMAGE_BYTES || $info === false || $info['mime'] !== 'image/'.$matches[1] || $info[0] > 4096 || $info[1] > 4096 || $info[0] * $info[1] > 16777216) {
            throw new InvalidArgumentException('ملف الصورة أو أبعاده غير صالحة.');
        }
    }
}

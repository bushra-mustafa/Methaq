<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Editor\Services\SvgAssetValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SvgAssetValidatorTest extends TestCase
{
    public function test_local_vector_content_is_accepted(): void
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
    <defs><linearGradient id="gold"><stop offset="0" stop-color="#b69a50" /></linearGradient></defs>
    <title>Safe frame</title>
    <path d="M10 10H110V110H10Z" fill="url(#gold)" />
</svg>
SVG;

        (new SvgAssetValidator)->assertSafe($svg);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeSvgProvider')]
    public function test_executable_or_external_svg_content_is_rejected(string $svg): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SvgAssetValidator)->assertSafe($svg);
    }

    /** @return array<string, array{string}> */
    public static function unsafeSvgProvider(): array
    {
        return [
            'script element' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'event attribute' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><path d="M0 0" /></svg>'],
            'style attribute' => ['<svg xmlns="http://www.w3.org/2000/svg"><path style="fill:red" d="M0 0" /></svg>'],
            'external url' => ['<svg xmlns="http://www.w3.org/2000/svg"><path fill="url(https://example.test/a.svg#x)" d="M0 0" /></svg>'],
            'document type' => ['<!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg" />'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

final class SvgAssetValidator
{
    /** @var list<string> */
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'title', 'desc', 'path', 'rect', 'circle', 'ellipse',
        'line', 'polyline', 'polygon', 'linearGradient', 'radialGradient', 'stop',
        'clipPath', 'mask', 'text', 'tspan',
    ];

    public function assertSafe(string $contents): void
    {
        if ($contents === '' || strlen($contents) > 1_048_576) {
            throw new InvalidArgumentException('SVG asset is empty or exceeds the size limit.');
        }

        if (preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $contents) === 1) {
            throw new InvalidArgumentException('SVG document declarations are not allowed.');
        }

        $document = new DOMDocument;
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (! $loaded || ! $document->documentElement instanceof DOMElement || $document->documentElement->localName !== 'svg') {
            throw new InvalidArgumentException('SVG asset is not a valid SVG document.');
        }

        $this->inspectNode($document->documentElement);
    }

    private function inspectNode(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            if (! in_array($node->localName, self::ALLOWED_ELEMENTS, true)) {
                throw new InvalidArgumentException("SVG element {$node->localName} is not allowed.");
            }

            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = trim($attribute->nodeValue ?? '');

                if (str_starts_with($name, 'on') || $name === 'style') {
                    throw new InvalidArgumentException('SVG event and style attributes are not allowed.');
                }
                if (in_array($name, ['href', 'xlink:href'], true) && ! str_starts_with($value, '#')) {
                    throw new InvalidArgumentException('SVG external references are not allowed.');
                }
                if (str_contains(strtolower($value), 'url(') && preg_match('/^url\(#[A-Za-z0-9_-]+\)$/', $value) !== 1) {
                    throw new InvalidArgumentException('SVG URL references must target a local definition.');
                }
            }
        }

        foreach ($node->childNodes as $child) {
            $this->inspectNode($child);
        }
    }
}

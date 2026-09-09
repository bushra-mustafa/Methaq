<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonException;

final class SaveDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $owner = $this->user();
        $event = $this->route('event');

        return $owner instanceof User && $event instanceof Event && $owner->can('update', $event);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'document' => ['required', 'array'],
            'expectedRevision' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $unexpected = array_diff(array_keys($this->all()), ['document', 'expectedRevision']);
            if ($unexpected !== []) {
                $validator->errors()->add('document', 'الطلب يحتوي على خصائص غير مسموحة.');

                return;
            }

            try {
                $document = $this->input('document');
                if (! is_array($document)) {
                    throw new InvalidArgumentException('The design document must be an object.');
                }
                $serialized = json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                if (strlen($serialized) > DesignSchema::MAXIMUM_JSON_BYTES) {
                    throw new InvalidArgumentException('The design document exceeds the maximum size.');
                }
                app(DesignDocumentHydrator::class)->hydrate($document);
            } catch (InvalidArgumentException|JsonException $exception) {
                $validator->errors()->add('document', $exception->getMessage());
            }
        }];
    }

    public function toData(DesignDocumentHydrator $hydrator): SaveDesignData
    {
        /** @var array{document: array<string, mixed>, expectedRevision: int} $validated */
        $validated = $this->validated();

        return new SaveDesignData(
            document: $hydrator->hydrate($validated['document']),
            expectedRevision: $validated['expectedRevision'],
        );
    }
}

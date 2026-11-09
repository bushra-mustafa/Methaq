<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Editor\DTOs\ProposalDraftData;
use App\Domains\Editor\Services\ProposalDocumentValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonException;

final class SaveProposalDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['document' => ['required', 'array'], 'expectedRevision' => ['required', 'integer', 'min:0', 'max:4294967294']];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                if (array_diff(array_keys($this->all()), ['document', 'expectedRevision']) !== []) {
                    throw new InvalidArgumentException('خصائص غير مسموحة.');
                }
                app(ProposalDocumentValidator::class)->validate($this->input('document'));
            } catch (InvalidArgumentException|JsonException $error) {
                $validator->errors()->add('document', $error->getMessage());
            }
        }];
    }

    public function toData(ProposalDocumentValidator $validator): ProposalDraftData
    {
        return new ProposalDraftData($validator->validate($this->validated('document')), (int) $this->validated('expectedRevision'));
    }
}

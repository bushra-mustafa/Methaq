<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\CreateManualPaymentAction;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class SubmitPaymentReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $owner = $this->user();
        $event = $this->route('event');

        return $owner instanceof User && $event instanceof Event && $owner->can('update', $event);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'receipt' => ['required', 'file', 'mimes:'.implode(',', [...CreateManualPaymentAction::RECEIPT_EXTENSIONS, 'jpeg']), 'max:'.CreateManualPaymentAction::MAX_RECEIPT_KILOBYTES],
            'amount' => ['prohibited'],
            'currency' => ['prohibited'],
            'gateway' => ['prohibited'],
            'status' => ['prohibited'],
            'entitlements' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'receipt.required' => 'اختاري صورة القسيمة أو ملف PDF.',
            'receipt.file' => 'تعذّر رفع الملف. حاولي مرة أخرى.',
            'receipt.uploaded' => 'تعذّر رفع الملف. تأكدي أن حجمه لا يتجاوز 5 ميغابايت.',
            'receipt.mimes' => 'الملف يجب أن يكون صورة JPG أو PNG أو WEBP، أو ملف PDF.',
            'receipt.max' => 'حجم الملف يجب ألا يتجاوز 5 ميغابايت.',
        ];
    }

    public function receipt(): UploadedFile
    {
        $receipt = $this->file('receipt');
        abort_unless($receipt instanceof UploadedFile, 422);

        return $receipt;
    }
}

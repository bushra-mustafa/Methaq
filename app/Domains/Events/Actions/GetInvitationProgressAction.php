<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\DTOs\EventPaymentData;
use App\Domains\Payments\Services\PaymentReviewSettings;
use App\Domains\Users\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class GetInvitationProgressAction
{
    public function __construct(private readonly GetEventPublishStateAction $publish, private readonly PaymentReviewSettings $review) {}

    /** @return array{state:string,title:string,detail:string,step:int,actionLabel:string,actionUrl:string,reviewDueAt:?string} */
    public function execute(User $owner, Event $event, EventPaymentData $payment): array
    {
        Gate::forUser($owner)->authorize('view', $event);
        $event->loadMissing('design');
        $paymentUrl = route('app.events.payment', $event);
        $presentationUrl = route('app.events.presentation', $event);
        $state = 'draft';
        $title = 'راجعي دعوتك قبل الدفع';
        $detail = 'تأكدي من الأسماء والموعد وتجربة فتح الدعوة، ثم ارفعي قسيمة التحويل.';
        $step = 0;
        $label = 'معاينة الدعوة والدفع';
        $url = $paymentUrl;
        $due = null;
        if ($event->suspended_at !== null) {
            $state = 'blocked';
            $title = 'الدعوة موقوفة حالياً';
            $detail = 'تواصلي مع الفريق لمراجعة حالة الدعوة.';
            $label = 'التواصل مع الفريق';
            $url = route('app.support.index');
        } elseif ($event->status === EventStatus::Expired || $event->expires_at->lessThanOrEqualTo(now())) {
            $state = 'expired';
            $title = 'انتهت صلاحية الدعوة';
            $detail = 'أُغلق رابط الضيوف. ما زالت ردود الحضور متاحة من حسابك.';
            $label = 'عرض ردود الضيوف';
            $url = route('app.events.rsvps', $event);
            $step = 4;
        } elseif ($event->status === EventStatus::Published) {
            $state = 'published';
            $title = 'دعوتك منشورة وجاهزة للمشاركة';
            $detail = 'شاركي الرابط مع ضيوفك وتابعي الردود من حسابك.';
            $label = 'عرض ردود الضيوف';
            $url = route('app.events.rsvps', $event);
            $step = 4;
        } elseif ($event->event_date->lessThanOrEqualTo(now())) {
            $state = 'blocked';
            $title = 'موعد المناسبة مضى';
            $detail = 'لا يمكن نشر دعوة جديدة لهذا الموعد. تواصلي مع الفريق لمراجعة المناسبة.';
            $label = 'التواصل مع الفريق';
            $url = route('app.support.index');
        } elseif ($payment->state === 'pending_review') {
            $state = 'pending_review';
            $title = 'القسيمة قيد المراجعة';
            $detail = 'وصلتنا القسيمة. نتحقق من وصول المبلغ؛ لا يلزم رفعها مرة ثانية.';
            $label = 'تفاصيل القسيمة';
            $step = 1;
            $submitted = $payment->latestOrder['submittedAt'] ?? null;
            $due = $submitted ? Carbon::parse($submitted)->addHours($this->review->hours())->toIso8601String() : null;
        } elseif ($payment->state === 'rejected') {
            $state = 'rejected';
            $title = 'القسيمة تحتاج تصحيحاً';
            $detail = $payment->rejectionMessage ?? 'راجعي القسيمة وارفعي نسخة جديدة.';
            $label = 'تصحيح القسيمة';
        } elseif ($payment->state === 'paid') {
            $publish = $this->publish->execute($owner, $event);
            if (! $publish['canPublish']) {
                $state = 'blocked';
                $title = 'الدفع معتمد، والنشر يحتاج مراجعة';
                $detail = $publish['blocker'] ?? 'راجعي تفاصيل الدعوة قبل النشر.';
                $label = 'مراجعة الدعوة';
                $url = $presentationUrl;
                $step = 2;
            } else {
                $design = $event->design;
                $prepared = $design?->prepared_render_path && $design->prepared_revision === $design->revision && Storage::disk('local')->exists($design->prepared_render_path);
                $state = $prepared ? 'ready_to_publish' : 'payment_approved';
                $step = $prepared ? 3 : 2;
                $title = $prepared ? 'دعوتك جاهزة للنشر' : 'تم اعتماد الدفع';
                $detail = $prepared ? 'انشري دعوتك من الموقع، أو ردي بكلمة «نشر» من رقم واتساب المؤكد إذا كان الربط مفعّلاً.' : 'راجعي المعاينة الحالية وانشري الدعوة. لا يلزم دفع جديد.';
                $label = 'معاينة ونشر الدعوة';
                $url = $presentationUrl;
            }
        }

        return ['state' => $state, 'title' => $title, 'detail' => $detail, 'step' => $step, 'actionLabel' => $label, 'actionUrl' => $url, 'reviewDueAt' => $due];
    }
}

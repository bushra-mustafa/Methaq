<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

final class TermsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Web/Terms', ['meta' => PageMeta::make('شروط الاستخدام', 'الشروط التي تحكم استعمال ميثاق: الحساب، القوالب، الدفع بالتحويل البنكي، النشر وانتهاء الدعوة.', '/terms')]);
    }
}

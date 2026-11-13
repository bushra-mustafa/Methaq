<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

final class PrivacyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Web/Privacy', ['meta' => PageMeta::make('سياسة الخصوصية', 'نوضّح ما نجمعه عند استخدامك لميثاق، ولماذا، ومن يراه.', '/privacy')]);
    }
}

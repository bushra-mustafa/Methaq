<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Web/Contact', ['meta' => PageMeta::make('تواصل معنا', 'قنوات التواصل الرسمية مع ميثاق ستُضاف عند اعتمادها.', '/contact')]);

    }
}

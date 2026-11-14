<?php

namespace App\Http\Controllers\Web;

use App\Domains\Users\Services\ContactChannels;
use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __invoke(ContactChannels $channels): Response
    {
        return Inertia::render('Web/Contact', [
            'channels' => $channels->active(),
            'meta' => PageMeta::make('تواصل معنا', 'راسلي فريق ميثاق أو تواصلي معنا عبر البريد وقنواتنا الرسمية.', '/contact'),
        ]);
    }
}
